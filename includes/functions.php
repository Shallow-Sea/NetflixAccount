<?php
require_once __DIR__ . '/../config/database.php';

// 用户认证函数
function login($username, $password, $is_admin = false) {
    $pdo = getConnection();
    
    $table = $is_admin ? 'admins' : 'users';
    $stmt = $pdo->prepare("SELECT id, username, password FROM {$table} WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        if ($is_admin) {
            $_SESSION['admin_id'] = $user['id'];
        } else {
            $_SESSION['user_id'] = $user['id'];
        }
        return true;
    }
    
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// 用户管理函数
function getUserById($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAdminById($id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function registerUser($username, $password, $email = null, $phone = null) {
    $pdo = getConnection();
    
    // 检查用户名是否已存在
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return false; // 用户名已存在
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $registration_ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, phone, registration_ip) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$username, $hashed_password, $email, $phone, $registration_ip]);
}

// Netflix账号管理函数
function addNetflixAccount($email, $password, $subscription_type = 'premium') {
    $pdo = getConnection();
    
    // 检查账号是否已存在
    $stmt = $pdo->prepare("SELECT id FROM netflix_accounts WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return false; // 账号已存在
    }
    
    $stmt = $pdo->prepare("INSERT INTO netflix_accounts (email, password, subscription_type) VALUES (?, ?, ?)");
    return $stmt->execute([$email, $password, $subscription_type]);
}

function getNetflixAccounts($status = null) {
    $pdo = getConnection();
    
    if ($status) {
        $stmt = $pdo->prepare("SELECT * FROM netflix_accounts WHERE status = ? ORDER BY created_at DESC");
        $stmt->execute([$status]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM netflix_accounts ORDER BY created_at DESC");
        $stmt->execute();
    }
    
    return $stmt->fetchAll();
}

function updateNetflixAccountStatus($id, $status) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("UPDATE netflix_accounts SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $id]);
}

// 分享页管理函数
function createSharePage($netflix_account_id, $card_type, $user_id = null) {
    $pdo = getConnection();
    
    // 如果账号ID为0，则随机选择一个活跃账号
    if ($netflix_account_id === 0) {
        $netflix_account_id = getRandomActiveAccount();
        if (!$netflix_account_id) {
            return false; // 没有可用的活跃账号
        }
    }
    
    // 生成唯一的分享码
    do {
        $share_code = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("SELECT id FROM share_pages WHERE share_code = ?");
        $stmt->execute([$share_code]);
    } while ($stmt->fetch());
    
    // 计算持续天数
    $duration_days = getCardTypeDays($card_type);
    
    $stmt = $pdo->prepare("INSERT INTO share_pages (share_code, netflix_account_id, user_id, card_type, duration_days) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$share_code, $netflix_account_id, $user_id, $card_type, $duration_days])) {
        return $share_code;
    }
    
    return false;
}

// 获取随机活跃账号ID (智能分发，避免同一账号被重复使用)
function getRandomActiveAccount() {
    $pdo = getConnection();
    
    // 获取所有活跃账号及其使用次数统计
    $stmt = $pdo->prepare("
        SELECT na.id, na.email, COUNT(sp.id) as usage_count 
        FROM netflix_accounts na 
        LEFT JOIN share_pages sp ON na.id = sp.netflix_account_id 
        WHERE na.status = 'active' 
        GROUP BY na.id, na.email 
        ORDER BY usage_count ASC, RAND()
    ");
    $stmt->execute();
    $accounts = $stmt->fetchAll();
    
    if (empty($accounts)) {
        return false;
    }
    
    // 如果有多个账号使用次数相同且最少，随机选择一个
    $min_usage = $accounts[0]['usage_count'];
    $candidates = [];
    
    foreach ($accounts as $account) {
        if ($account['usage_count'] == $min_usage) {
            $candidates[] = $account['id'];
        } else {
            break; // 因为已经按使用次数排序，后面的使用次数会更多
        }
    }
    
    // 从使用次数最少的候选账号中随机选择
    return $candidates[array_rand($candidates)];
}

function activateSharePage($share_code) {
    $pdo = getConnection();
    
    // 获取分享页信息
    $stmt = $pdo->prepare("SELECT * FROM share_pages WHERE share_code = ? AND is_activated = FALSE");
    $stmt->execute([$share_code]);
    $share_page = $stmt->fetch();
    
    if (!$share_page) {
        return false; // 分享页不存在或已激活
    }
    
    // 计算到期时间
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$share_page['duration_days']} days"));
    
    // 更新分享页状态（不再关联用户）
    $stmt = $pdo->prepare("UPDATE share_pages SET is_activated = TRUE, activated_at = NOW(), expires_at = ? WHERE id = ?");
    $result = $stmt->execute([$expires_at, $share_page['id']]);
    
    if ($result) {
        // 记录激活日志（不再记录用户ID）
        $activation_ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        $stmt = $pdo->prepare("INSERT INTO activation_logs (share_page_id, activation_ip, user_agent) VALUES (?, ?, ?)");
        $stmt->execute([$share_page['id'], $activation_ip, $user_agent]);
        
        return true;
    }
    
    return false;
}

function getSharePageByCode($share_code) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT sp.*, na.email as netflix_email, na.password as netflix_password, na.subscription_type,
               u.username
        FROM share_pages sp
        LEFT JOIN netflix_accounts na ON sp.netflix_account_id = na.id
        LEFT JOIN users u ON sp.user_id = u.id
        WHERE sp.share_code = ?
    ");
    $stmt->execute([$share_code]);
    return $stmt->fetch();
}

function getUserSharePages($user_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT sp.*, na.email as netflix_email, na.subscription_type
        FROM share_pages sp
        LEFT JOIN netflix_accounts na ON sp.netflix_account_id = na.id
        WHERE sp.user_id = ?
        ORDER BY sp.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// 批量导出功能
function exportSharePages($format = 'txt', $user_id = null, $card_type = null) {
    $pdo = getConnection();
    
    $where_conditions = [];
    $params = [];
    
    if ($user_id) {
        $where_conditions[] = "sp.user_id = ?";
        $params[] = $user_id;
    }
    
    if ($card_type) {
        $where_conditions[] = "sp.card_type = ?";
        $params[] = $card_type;
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    $stmt = $pdo->prepare("
        SELECT sp.share_code, sp.card_type, sp.is_activated, sp.created_at, sp.expires_at,
               na.email as netflix_email, u.username
        FROM share_pages sp
        LEFT JOIN netflix_accounts na ON sp.netflix_account_id = na.id
        LEFT JOIN users u ON sp.user_id = u.id
        {$where_clause}
        ORDER BY sp.created_at DESC
    ");
    $stmt->execute($params);
    $share_pages = $stmt->fetchAll();
    
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/share.php?code=';
    
    switch ($format) {
        case 'csv':
            return exportToCSV($share_pages, $base_url);
        case 'excel':
            return exportToExcel($share_pages, $base_url);
        case 'txt':
        default:
            return exportToTXT($share_pages, $base_url);
    }
}

function exportToTXT($data, $base_url) {
    $content = "奈飞账号分享链接导出\n";
    $content .= "导出时间：" . date('Y-m-d H:i:s') . "\n";
    $content .= str_repeat("=", 50) . "\n\n";
    
    foreach ($data as $row) {
        $content .= "分享码：{$row['share_code']}\n";
        $content .= "分享链接：{$base_url}{$row['share_code']}\n";
        $content .= "卡类型：" . getCardTypeName($row['card_type']) . "\n";
        $content .= "创建时间：{$row['created_at']}\n";
        $content .= "激活状态：" . ($row['is_activated'] ? '已激活' : '未激活') . "\n";
        if ($row['is_activated'] && $row['expires_at']) {
            $content .= "到期时间：{$row['expires_at']}\n";
        }
        $content .= str_repeat("-", 30) . "\n\n";
    }
    
    return $content;
}

function exportToCSV($data, $base_url) {
    $csv = "分享码,分享链接,卡类型,创建时间,激活状态,到期时间,用户名\n";
    
    foreach ($data as $row) {
        $csv .= '"' . $row['share_code'] . '",';
        $csv .= '"' . $base_url . $row['share_code'] . '",';
        $csv .= '"' . getCardTypeName($row['card_type']) . '",';
        $csv .= '"' . $row['created_at'] . '",';
        $csv .= '"' . ($row['is_activated'] ? '已激活' : '未激活') . '",';
        $csv .= '"' . ($row['expires_at'] ?? '') . '",';
        $csv .= '"' . ($row['username'] ?? '') . '"' . "\n";
    }
    
    return $csv;
}

// 统计函数
function getActiveAccountsCount() {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM netflix_accounts WHERE status = 'active'");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getTotalUsersCount() {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getActiveSharePagesCount() {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM share_pages WHERE is_activated = TRUE AND expires_at > NOW()");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getTodayActivationsCount() {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activation_logs WHERE DATE(activated_at) = CURDATE()");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getRecentActivations($limit = 10) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT sp.share_code, sp.card_type, sp.activated_at, sp.expires_at,
               al.activation_ip, u.username
        FROM activation_logs al
        LEFT JOIN share_pages sp ON al.share_page_id = sp.id
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.activated_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// 公告管理函数
function getActiveAnnouncements() {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE is_active = TRUE ORDER BY priority DESC, created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function addAnnouncement($title, $content, $content_type = 'html', $is_popup = false, $popup_duration = 5000) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("INSERT INTO announcements (title, content, content_type, is_popup, popup_duration) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$title, $content, $content_type, $is_popup, $popup_duration]);
}

// 工具函数
function getCardTypeName($card_type) {
    $names = [
        'day' => '天卡',
        'week' => '周卡',
        'month' => '月卡',
        'quarter' => '季度卡',
        'halfyear' => '半年卡',
        'year' => '年卡'
    ];
    
    return $names[$card_type] ?? $card_type;
}

function getCardTypeDays($card_type) {
    $days = [
        'day' => 1,
        'week' => 7,
        'month' => 30,
        'quarter' => 90,
        'halfyear' => 180,
        'year' => 365
    ];
    
    return $days[$card_type] ?? 30;
}

function generateShareUrl($share_code) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // 检查当前是否在Jahre目录下，如果是则需要跳到上级目录
    $current_dir = dirname($_SERVER['REQUEST_URI']);
    if (strpos($current_dir, '/Jahre') !== false) {
        // 在Jahre目录下，需要返回根目录
        $base_path = str_replace('/Jahre', '', $current_dir);
        if ($base_path === '') {
            $base_path = '';
        }
    } else {
        $base_path = $current_dir;
    }
    
    // 确保路径格式正确
    $base_path = rtrim($base_path, '/');
    
    return "{$protocol}://{$host}{$base_path}/share.php?code={$share_code}";
}

// 简单的Markdown解析器
function parseMarkdown($text) {
    // 这是一个基础的Markdown解析器，可以根据需要扩展
    $text = htmlspecialchars($text);
    
    // 标题
    $text = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $text);
    
    // 粗体和斜体
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
    
    // 链接
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);
    
    // 换行
    $text = nl2br($text);
    
    return $text;
}

// 安全函数
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) || isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function checkAdminAccess() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}
?>
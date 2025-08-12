<?php
require_once __DIR__ . '/../config/database.php';

// =================
// 认证相关函数
// =================

function login($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        
        logOperation($admin['id'], 'login', 'admin', $admin['id'], '管理员登录');
        return true;
    }
    
    return false;
}

function logout() {
    if (isset($_SESSION['admin_id'])) {
        logOperation($_SESSION['admin_id'], 'logout', 'admin', $_SESSION['admin_id'], '管理员退出');
    }
    
    session_destroy();
    return true;
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// =================
// 奈飞账号管理函数
// =================

function addNetflixAccount($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO netflix_accounts 
            (email, password, subscription_type, status, 
             slot1_enabled, slot1_pin, slot2_enabled, slot2_pin, 
             slot3_enabled, slot3_pin, slot4_enabled, slot4_pin, 
             slot5_enabled, slot5_pin) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['email'],
            $data['password'],
            $data['subscription_type'] ?? 'premium',
            $data['status'] ?? 'active',
            $data['slot1_enabled'] ?? true,
            $data['slot1_pin'] ?? null,
            $data['slot2_enabled'] ?? true,
            $data['slot2_pin'] ?? null,
            $data['slot3_enabled'] ?? true,
            $data['slot3_pin'] ?? null,
            $data['slot4_enabled'] ?? true,
            $data['slot4_pin'] ?? null,
            $data['slot5_enabled'] ?? true,
            $data['slot5_pin'] ?? null
        ]);
        
        if ($result) {
            $accountId = $pdo->lastInsertId();
            logOperation($_SESSION['admin_id'], 'create', 'account', $accountId, '添加奈飞账号: ' . $data['email']);
            return $accountId;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("添加奈飞账号失败: " . $e->getMessage());
        return false;
    }
}

function batchAddNetflixAccounts($accounts) {
    $success_count = 0;
    $failed_accounts = [];
    
    foreach ($accounts as $account) {
        $result = addNetflixAccount($account);
        if ($result) {
            $success_count++;
        } else {
            $failed_accounts[] = $account['email'];
        }
    }
    
    return ['success_count' => $success_count, 'failed_accounts' => $failed_accounts];
}

function updateNetflixAccount($id, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE netflix_accounts 
            SET email = ?, password = ?, subscription_type = ?, status = ?,
                slot1_enabled = ?, slot1_pin = ?, slot2_enabled = ?, slot2_pin = ?,
                slot3_enabled = ?, slot3_pin = ?, slot4_enabled = ?, slot4_pin = ?,
                slot5_enabled = ?, slot5_pin = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $data['email'],
            $data['password'],
            $data['subscription_type'],
            $data['status'],
            $data['slot1_enabled'],
            $data['slot1_pin'],
            $data['slot2_enabled'],
            $data['slot2_pin'],
            $data['slot3_enabled'],
            $data['slot3_pin'],
            $data['slot4_enabled'],
            $data['slot4_pin'],
            $data['slot5_enabled'],
            $data['slot5_pin'],
            $id
        ]);
        
        if ($result) {
            logOperation($_SESSION['admin_id'], 'update', 'account', $id, '更新奈飞账号: ' . $data['email']);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("更新奈飞账号失败: " . $e->getMessage());
        return false;
    }
}

function deleteNetflixAccount($id) {
    global $pdo;
    
    try {
        // 先获取账号信息用于日志
        $stmt = $pdo->prepare("SELECT email FROM netflix_accounts WHERE id = ?");
        $stmt->execute([$id]);
        $account = $stmt->fetch();
        
        if (!$account) return false;
        
        $stmt = $pdo->prepare("DELETE FROM netflix_accounts WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            logOperation($_SESSION['admin_id'], 'delete', 'account', $id, '删除奈飞账号: ' . $account['email']);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("删除奈飞账号失败: " . $e->getMessage());
        return false;
    }
}

function batchUpdateNetflixAccountStatus($ids, $status) {
    global $pdo;
    
    try {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("UPDATE netflix_accounts SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
        
        $params = array_merge([$status], $ids);
        $result = $stmt->execute($params);
        
        if ($result) {
            foreach ($ids as $id) {
                logOperation($_SESSION['admin_id'], 'batch_update', 'account', $id, '批量修改状态: ' . $status);
            }
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("批量更新账号状态失败: " . $e->getMessage());
        return false;
    }
}

function getNetflixAccounts($page = 1, $limit = 20, $filters = []) {
    global $pdo;
    
    $offset = ($page - 1) * $limit;
    $where_conditions = [];
    $params = [];
    
    if (!empty($filters['status'])) {
        $where_conditions[] = "status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['subscription_type'])) {
        $where_conditions[] = "subscription_type = ?";
        $params[] = $filters['subscription_type'];
    }
    
    if (!empty($filters['email'])) {
        $where_conditions[] = "email LIKE ?";
        $params[] = '%' . $filters['email'] . '%';
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // 获取总数
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM netflix_accounts $where_clause");
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();
    
    // 获取数据
    $stmt = $pdo->prepare("
        SELECT *, 
               (SELECT COUNT(*) FROM share_pages WHERE netflix_account_id = netflix_accounts.id AND is_activated = 1) as active_shares
        FROM netflix_accounts 
        $where_clause 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    
    return [
        'data' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'pages' => ceil($total / $limit)
    ];
}

function getAvailableNetflixAccount() {
    global $pdo;
    
    // 获取负载最低的可用账号
    $stmt = $pdo->prepare("
        SELECT * FROM netflix_accounts 
        WHERE status = 'active' 
        ORDER BY usage_count ASC, last_used_at ASC 
        LIMIT 1
    ");
    $stmt->execute();
    
    return $stmt->fetch();
}

// =================
// 分享页管理函数
// =================

function generateShareCode() {
    return bin2hex(random_bytes(16));
}

function addSharePage($data) {
    global $pdo;
    
    try {
        $share_code = generateShareCode();
        
        // 如果没有指定账号，自动选择负载最低的账号
        if (empty($data['netflix_account_id'])) {
            $account = getAvailableNetflixAccount();
            if (!$account) {
                return false; // 没有可用账号
            }
            $data['netflix_account_id'] = $account['id'];
        }
        
        // 根据卡类型计算天数
        $card_types = [
            'day' => 1,
            'week' => 7,
            'month' => 30,
            'quarter' => 90,
            'half_year' => 180,
            'year' => 365
        ];
        
        $duration_days = $card_types[$data['card_type']] ?? 30;
        
        $stmt = $pdo->prepare("
            INSERT INTO share_pages 
            (share_code, netflix_account_id, card_type, duration_days, title, description)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $share_code,
            $data['netflix_account_id'],
            $data['card_type'],
            $duration_days,
            $data['title'] ?? '奈飞高级账号分享',
            $data['description'] ?? null
        ]);
        
        if ($result) {
            $sharePageId = $pdo->lastInsertId();
            logOperation($_SESSION['admin_id'], 'create', 'share_page', $sharePageId, '创建分享页: ' . $share_code);
            return $sharePageId;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("添加分享页失败: " . $e->getMessage());
        return false;
    }
}

function batchAddSharePages($count, $card_type, $title = null) {
    $success_count = 0;
    $share_codes = [];
    
    for ($i = 0; $i < $count; $i++) {
        $data = [
            'card_type' => $card_type,
            'title' => $title
        ];
        
        $result = addSharePage($data);
        if ($result) {
            $success_count++;
            // 获取生成的分享码
            global $pdo;
            $stmt = $pdo->prepare("SELECT share_code FROM share_pages WHERE id = ?");
            $stmt->execute([$result]);
            $page = $stmt->fetch();
            if ($page) {
                $share_codes[] = $page['share_code'];
            }
        }
    }
    
    return ['success_count' => $success_count, 'share_codes' => $share_codes];
}

function activateSharePage($share_code) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE share_pages 
            SET is_activated = 1, 
                activated_at = CURRENT_TIMESTAMP,
                expires_at = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL duration_days DAY)
            WHERE share_code = ? AND is_activated = 0
        ");
        
        $result = $stmt->execute([$share_code]);
        
        if ($result && $stmt->rowCount() > 0) {
            // 更新对应账号的使用统计
            $pdo->prepare("
                UPDATE netflix_accounts 
                SET usage_count = usage_count + 1, last_used_at = CURRENT_TIMESTAMP 
                WHERE id = (SELECT netflix_account_id FROM share_pages WHERE share_code = ?)
            ")->execute([$share_code]);
            
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("激活分享页失败: " . $e->getMessage());
        return false;
    }
}

function getSharePageByCode($share_code) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT sp.*, na.email as netflix_email, na.password as netflix_password, 
               na.subscription_type, na.status as account_status
        FROM share_pages sp
        JOIN netflix_accounts na ON sp.netflix_account_id = na.id
        WHERE sp.share_code = ?
    ");
    
    $stmt->execute([$share_code]);
    return $stmt->fetch();
}

function getSharePages($page = 1, $limit = 20, $filters = []) {
    global $pdo;
    
    $offset = ($page - 1) * $limit;
    $where_conditions = [];
    $params = [];
    
    if (!empty($filters['is_activated'])) {
        $where_conditions[] = "sp.is_activated = ?";
        $params[] = $filters['is_activated'] === 'true' ? 1 : 0;
    }
    
    if (!empty($filters['card_type'])) {
        $where_conditions[] = "sp.card_type = ?";
        $params[] = $filters['card_type'];
    }
    
    if (!empty($filters['share_code'])) {
        $where_conditions[] = "sp.share_code LIKE ?";
        $params[] = '%' . $filters['share_code'] . '%';
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // 获取总数
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM share_pages sp
        JOIN netflix_accounts na ON sp.netflix_account_id = na.id
        $where_clause
    ");
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();
    
    // 获取数据
    $stmt = $pdo->prepare("
        SELECT sp.*, na.email as netflix_email, na.status as account_status
        FROM share_pages sp
        JOIN netflix_accounts na ON sp.netflix_account_id = na.id
        $where_clause 
        ORDER BY sp.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    
    return [
        'data' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'pages' => ceil($total / $limit)
    ];
}

function exportSharePages($ids, $format = 'txt') {
    global $pdo;
    
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT share_code FROM share_pages WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $share_pages = $stmt->fetchAll();
    
    $base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/share.php?code=';
    
    if ($format === 'txt') {
        $content = "奈飞分享页导出 - " . date('Y-m-d H:i:s') . "\n\n";
        foreach ($share_pages as $page) {
            $content .= $base_url . $page['share_code'] . "\n";
        }
        return $content;
    } elseif ($format === 'excel') {
        // 这里可以集成 PhpSpreadsheet 库来生成 Excel 文件
        // 简化版本，返回 CSV 格式
        $content = "分享码,链接\n";
        foreach ($share_pages as $page) {
            $content .= $page['share_code'] . ',' . $base_url . $page['share_code'] . "\n";
        }
        return $content;
    }
    
    return false;
}

// =================
// 公告管理函数
// =================

function addAnnouncement($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO announcements 
            (title, content, content_type, is_popup, popup_duration, is_active, start_date, end_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['title'],
            $data['content'],
            $data['content_type'] ?? 'html',
            $data['is_popup'] ?? false,
            $data['popup_duration'] ?? 5000,
            $data['is_active'] ?? true,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null
        ]);
        
        if ($result) {
            $announcementId = $pdo->lastInsertId();
            logOperation($_SESSION['admin_id'], 'create', 'announcement', $announcementId, '创建公告: ' . $data['title']);
            return $announcementId;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("添加公告失败: " . $e->getMessage());
        return false;
    }
}

function updateAnnouncement($id, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE announcements 
            SET title = ?, content = ?, content_type = ?, is_popup = ?, 
                popup_duration = ?, is_active = ?, start_date = ?, end_date = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $data['title'],
            $data['content'],
            $data['content_type'],
            $data['is_popup'],
            $data['popup_duration'],
            $data['is_active'],
            $data['start_date'],
            $data['end_date'],
            $id
        ]);
        
        if ($result) {
            logOperation($_SESSION['admin_id'], 'update', 'announcement', $id, '更新公告: ' . $data['title']);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("更新公告失败: " . $e->getMessage());
        return false;
    }
}

function deleteAnnouncement($id) {
    global $pdo;
    
    try {
        // 先获取公告信息用于日志
        $stmt = $pdo->prepare("SELECT title FROM announcements WHERE id = ?");
        $stmt->execute([$id]);
        $announcement = $stmt->fetch();
        
        if (!$announcement) return false;
        
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            logOperation($_SESSION['admin_id'], 'delete', 'announcement', $id, '删除公告: ' . $announcement['title']);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("删除公告失败: " . $e->getMessage());
        return false;
    }
}

function getAnnouncements($page = 1, $limit = 20, $filters = []) {
    global $pdo;
    
    $offset = ($page - 1) * $limit;
    $where_conditions = [];
    $params = [];
    
    if (isset($filters['is_active'])) {
        $where_conditions[] = "is_active = ?";
        $params[] = $filters['is_active'] ? 1 : 0;
    }
    
    if (isset($filters['is_popup'])) {
        $where_conditions[] = "is_popup = ?";
        $params[] = $filters['is_popup'] ? 1 : 0;
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // 获取总数
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM announcements $where_clause");
    $count_stmt->execute($params);
    $total = $count_stmt->fetchColumn();
    
    // 获取数据
    $stmt = $pdo->prepare("
        SELECT * FROM announcements 
        $where_clause 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    
    return [
        'data' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'pages' => ceil($total / $limit)
    ];
}

function getActiveAnnouncements() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM announcements 
        WHERE is_active = 1 
        AND (start_date IS NULL OR start_date <= CURRENT_TIMESTAMP)
        AND (end_date IS NULL OR end_date >= CURRENT_TIMESTAMP)
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    return $stmt->fetchAll();
}

// =================
// 管理员管理函数
// =================

function addAdmin($data) {
    global $pdo;
    
    try {
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO admins (username, password, email, is_active)
            VALUES (?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['username'],
            $hashed_password,
            $data['email'] ?? null,
            $data['is_active'] ?? true
        ]);
        
        if ($result) {
            $adminId = $pdo->lastInsertId();
            logOperation($_SESSION['admin_id'], 'create', 'admin', $adminId, '创建管理员: ' . $data['username']);
            return $adminId;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("添加管理员失败: " . $e->getMessage());
        return false;
    }
}

function updateAdminPassword($id, $new_password) {
    global $pdo;
    
    try {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            UPDATE admins 
            SET password = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$hashed_password, $id]);
        
        if ($result) {
            logOperation($_SESSION['admin_id'], 'update', 'admin', $id, '修改管理员密码');
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("更新管理员密码失败: " . $e->getMessage());
        return false;
    }
}

function getAdmins() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id, username, email, created_at, updated_at, is_active
        FROM admins 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    return $stmt->fetchAll();
}

// =================
// 工具函数
// =================

function getCardTypeName($card_type) {
    $names = [
        'day' => '天卡',
        'week' => '周卡',
        'month' => '月卡',
        'quarter' => '季度卡',
        'half_year' => '半年卡',
        'year' => '年卡'
    ];
    
    return $names[$card_type] ?? '未知';
}

function parseMarkdown($text) {
    // 简单的 Markdown 解析，可以根据需要集成更完善的 Markdown 解析库
    $text = preg_replace('/^### (.*$)/im', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.*$)/im', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.*$)/im', '<h1>$1</h1>', $text);
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/\n/', '<br>', $text);
    
    return $text;
}

function logOperation($admin_id, $action, $target_type, $target_id, $details = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO operation_logs 
            (admin_id, action, target_type, target_id, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $admin_id,
            $action,
            $target_type,
            $target_id,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("记录操作日志失败: " . $e->getMessage());
    }
}

function getDashboardStats() {
    global $pdo;
    
    $stats = [];
    
    // 账号统计
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM netflix_accounts GROUP BY status");
    $account_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $stats['accounts'] = [
        'total' => array_sum($account_stats),
        'active' => $account_stats['active'] ?? 0,
        'inactive' => $account_stats['inactive'] ?? 0,
        'suspended' => $account_stats['suspended'] ?? 0
    ];
    
    // 分享页统计
    $stmt = $pdo->query("SELECT is_activated, COUNT(*) as count FROM share_pages GROUP BY is_activated");
    $share_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $stats['shares'] = [
        'total' => array_sum($share_stats),
        'activated' => $share_stats[1] ?? 0,
        'pending' => $share_stats[0] ?? 0
    ];
    
    // 公告统计
    $stmt = $pdo->query("SELECT COUNT(*) FROM announcements WHERE is_active = 1");
    $stats['announcements'] = ['active' => $stmt->fetchColumn()];
    
    // 今日激活统计
    $stmt = $pdo->query("SELECT COUNT(*) FROM share_pages WHERE DATE(activated_at) = CURRENT_DATE");
    $stats['today_activations'] = $stmt->fetchColumn();
    
    return $stats;
}

// 设置默认时区
date_default_timezone_set('Asia/Shanghai');
?>
<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

checkAdminAccess();

$error = '';
$success = '';

// 处理URL中的错误参数
if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}

// 处理创建分享页
if ($_POST['action'] ?? '' === 'create_share') {
    $netflix_account_id_raw = $_POST['netflix_account_id'] ?? '';
    $netflix_account_id = is_numeric($netflix_account_id_raw) ? (int)$netflix_account_id_raw : -1;
    $card_type = sanitizeInput($_POST['card_type'] ?? 'month');
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if ($netflix_account_id < 0 || $netflix_account_id_raw === '') {
        $error = '请选择Netflix账号分配方式';
    } elseif ($quantity <= 0 || $quantity > 50) {
        $error = '生成数量必须在1-50之间';
    } else {
        // 检查是否有活跃账号（当选择随机分配或指定账号时）
        if ($netflix_account_id === 0) {
            // 随机分配，检查是否有活跃账号
            $active_accounts_check = getNetflixAccounts('active');
            if (empty($active_accounts_check)) {
                $error = '没有可用的活跃Netflix账号，请先添加账号';
            }
        } else {
            // 指定账号，检查账号是否存在且活跃
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT id FROM netflix_accounts WHERE id = ? AND status = 'active'");
            $stmt->execute([$netflix_account_id]);
            if (!$stmt->fetch()) {
                $error = '选择的Netflix账号不存在或不可用';
            }
        }
        
        if (!$error) {
            $generated_codes = [];
            $failed_count = 0;
            
            for ($i = 0; $i < $quantity; $i++) {
                $share_code = createSharePage($netflix_account_id, $card_type);
                if ($share_code) {
                    $generated_codes[] = $share_code;
                } else {
                    $failed_count++;
                }
            }
            
            if (count($generated_codes) > 0) {
                $_SESSION['generated_codes'] = $generated_codes;
                if ($netflix_account_id === 0) {
                    $success = "成功生成 " . count($generated_codes) . " 个分享页 (智能随机分配)";
                } else {
                    $success = "成功生成 " . count($generated_codes) . " 个分享页";
                }
                if ($failed_count > 0) {
                    $success .= "，失败 {$failed_count} 个";
                }
            } else {
                $error = '分享页生成失败，请检查是否有可用的活跃账号';
            }
        }
    }
}

// 处理批量重新分配账号
if ($_POST['action'] ?? '' === 'batch_reassign') {
    $share_page_ids = $_POST['share_page_ids'] ?? [];
    if (is_string($share_page_ids)) {
        $share_page_ids = explode(',', $share_page_ids);
    }
    $new_account_id = (int)($_POST['new_account_id'] ?? 0);
    
    if (empty($share_page_ids)) {
        $error = '请选择要重新分配的分享页';
    } elseif ($new_account_id === 0) {
        // 随机分配
        $pdo = getConnection();
        $success_count = 0;
        $failed_count = 0;
        
        foreach ($share_page_ids as $id) {
            $random_account_id = getRandomActiveAccount();
            if ($random_account_id) {
                $stmt = $pdo->prepare("UPDATE share_pages SET netflix_account_id = ? WHERE id = ?");
                if ($stmt->execute([$random_account_id, (int)$id])) {
                    $success_count++;
                } else {
                    $failed_count++;
                }
            } else {
                $failed_count++;
            }
        }
        
        if ($success_count > 0) {
            $success = "成功重新分配 {$success_count} 个分享页";
            if ($failed_count > 0) {
                $success .= "，失败 {$failed_count} 个";
            }
        } else {
            $error = '重新分配失败，请检查是否有可用账号';
        }
    } elseif ($new_account_id > 0) {
        // 指定账号分配
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id FROM netflix_accounts WHERE id = ? AND status = 'active'");
        $stmt->execute([$new_account_id]);
        
        if (!$stmt->fetch()) {
            $error = '选择的账号不存在或不可用';
        } else {
            $success_count = 0;
            $failed_count = 0;
            
            foreach ($share_page_ids as $id) {
                $stmt = $pdo->prepare("UPDATE share_pages SET netflix_account_id = ? WHERE id = ?");
                if ($stmt->execute([$new_account_id, (int)$id])) {
                    $success_count++;
                } else {
                    $failed_count++;
                }
            }
            
            if ($success_count > 0) {
                $success = "成功重新分配 {$success_count} 个分享页到指定账号";
                if ($failed_count > 0) {
                    $success .= "，失败 {$failed_count} 个";
                }
            } else {
                $error = '重新分配失败';
            }
        }
    } else {
        $error = '请选择分配方式';
    }
}

// 处理批量删除
if ($_POST['action'] ?? '' === 'batch_delete') {
    $share_page_ids = $_POST['share_page_ids'] ?? [];
    if (is_string($share_page_ids)) {
        $share_page_ids = explode(',', $share_page_ids);
    }
    
    if (empty($share_page_ids)) {
        $error = '请选择要删除的分享页';
    } else {
        $pdo = getConnection();
        $success_count = 0;
        $failed_count = 0;
        
        foreach ($share_page_ids as $id) {
            $stmt = $pdo->prepare("DELETE FROM share_pages WHERE id = ?");
            if ($stmt->execute([(int)$id])) {
                $success_count++;
            } else {
                $failed_count++;
            }
        }
        
        if ($success_count > 0) {
            $success = "成功删除 {$success_count} 个分享页";
            if ($failed_count > 0) {
                $success .= "，失败 {$failed_count} 个";
            }
        } else {
            $error = '删除失败';
        }
    }
}

// 处理生成后导出
if ($_GET['action'] ?? '' === 'export_generated') {
    $codes = $_SESSION['generated_codes'] ?? [];
    $format = $_GET['format'] ?? 'txt';
    
    if (empty($codes)) {
        // 重定向回页面并显示错误
        header('Location: share-pages.php?error=' . urlencode('没有可导出的分享页'));
        exit;
    }
    
    // 获取分享页详细信息
    $pdo = getConnection();
    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $stmt = $pdo->prepare("
        SELECT sp.share_code, sp.card_type, sp.created_at, na.email as netflix_email
        FROM share_pages sp
        LEFT JOIN netflix_accounts na ON sp.netflix_account_id = na.id
        WHERE sp.share_code IN ($placeholders)
        ORDER BY sp.created_at DESC
    ");
    $stmt->execute($codes);
    $share_pages = $stmt->fetchAll();
    
    $filename = 'generated_shares_' . date('Y-m-d_H-i-s');
    
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo exportGeneratedToCSV($share_pages);
            break;
        case 'txt':
        default:
            header('Content-Type: text/plain; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$filename}.txt\"");
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo exportGeneratedToTXT($share_pages);
    }
    exit;
}

// 处理批量导出
if ($_GET['action'] ?? '' === 'export') {
    $format = $_GET['format'] ?? 'txt';
    $card_type = $_GET['card_type'] ?? '';
    
    $export_data = exportSharePages($format, null, $card_type ?: null);
    
    $filename = 'share_pages_' . date('Y-m-d_H-i-s');
    
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
            break;
        case 'excel':
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
            break;
        default:
            header('Content-Type: text/plain; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$filename}.txt\"");
    }
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo $export_data;
    exit;
}

// 获取分享页列表
$pdo = getConnection();

// 分页参数
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 过滤参数
$card_type_filter = $_GET['card_type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// 构建查询条件
$where_conditions = [];
$params = [];

if ($card_type_filter) {
    $where_conditions[] = "sp.card_type = ?";
    $params[] = $card_type_filter;
}

if ($status_filter) {
    if ($status_filter === 'active') {
        $where_conditions[] = "sp.is_activated = TRUE AND sp.expires_at > NOW()";
    } elseif ($status_filter === 'expired') {
        $where_conditions[] = "sp.is_activated = TRUE AND sp.expires_at <= NOW()";
    } elseif ($status_filter === 'inactive') {
        $where_conditions[] = "sp.is_activated = FALSE";
    }
}

if ($search) {
    $where_conditions[] = "(sp.share_code LIKE ? OR na.email LIKE ? OR u.username LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// 获取总数
$count_sql = "
    SELECT COUNT(*) 
    FROM share_pages sp 
    LEFT JOIN netflix_accounts na ON sp.netflix_account_id = na.id
    LEFT JOIN users u ON sp.user_id = u.id
    {$where_clause}
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetchColumn();

// 获取分享页列表
$sql = "
    SELECT sp.*, na.email as netflix_email, na.subscription_type,
           u.username, u.email as user_email
    FROM share_pages sp 
    LEFT JOIN netflix_accounts na ON sp.netflix_account_id = na.id
    LEFT JOIN users u ON sp.user_id = u.id
    {$where_clause}
    ORDER BY sp.created_at DESC
    LIMIT {$per_page} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$share_pages = $stmt->fetchAll();

$total_pages = ceil($total_count / $per_page);

// 获取Netflix账号列表用于创建分享页
try {
    $active_accounts = getNetflixAccounts('active');
} catch (Exception $e) {
    $active_accounts = [];
    if (empty($error)) {
        $error = '获取Netflix账号失败: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分享页管理 - 奈飞分享系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-tv"></i> 奈飞分享系统
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin-dashboard.php">
                            <i class="bi bi-speedometer2"></i> 管理仪表板
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="accounts.php">
                            <i class="bi bi-person-lines-fill"></i> 账号管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i> 用户管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="share-pages.php">
                            <i class="bi bi-share"></i> 分享页管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="announcements.php">
                            <i class="bi bi-megaphone"></i> 公告管理
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="bi bi-house"></i> 前台首页
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right"></i> 退出登录
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-share"></i> 分享页管理</h2>
                    <div>
                        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#createShareModal">
                            <i class="bi bi-plus-circle"></i> 创建分享页
                        </button>
                        <button class="btn btn-warning me-2" onclick="showBatchActions()" id="batchActionsBtn" style="display: none;">
                            <i class="bi bi-gear"></i> 批量操作
                        </button>
                        <div class="btn-group">
                            <button class="btn btn-info dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-download"></i> 导出全部
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?action=export&format=txt<?php echo $card_type_filter ? "&card_type={$card_type_filter}" : ''; ?>">TXT格式</a></li>
                                <li><a class="dropdown-item" href="?action=export&format=csv<?php echo $card_type_filter ? "&card_type={$card_type_filter}" : ''; ?>">CSV格式</a></li>
                                <li><a class="dropdown-item" href="?action=export&format=excel<?php echo $card_type_filter ? "&card_type={$card_type_filter}" : ''; ?>">Excel格式</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- 显示生成的分享码 -->
                <?php if (isset($_SESSION['generated_codes'])): ?>
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">新生成的分享链接</h5>
                            <div class="btn-group btn-group-sm">
                                <a href="?action=export_generated&format=txt" class="btn btn-outline-success">
                                    <i class="bi bi-download"></i> 导出TXT
                                </a>
                                <a href="?action=export_generated&format=csv" class="btn btn-outline-info">
                                    <i class="bi bi-download"></i> 导出CSV
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($_SESSION['generated_codes'] as $code): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="input-group">
                                            <?php $generated_url = generateShareUrl($code); ?>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($generated_url); ?>" readonly>
                                            <button class="btn btn-outline-primary" onclick="copyToClipboard('<?php echo htmlspecialchars($generated_url, ENT_QUOTES); ?>')">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['generated_codes']); ?>
                <?php endif; ?>

                <!-- 搜索和过滤器 -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">搜索</label>
                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="分享码/邮箱/用户名">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">卡类型</label>
                                <select class="form-control" name="card_type">
                                    <option value="">全部</option>
                                    <option value="day" <?php echo $card_type_filter === 'day' ? 'selected' : ''; ?>>天卡</option>
                                    <option value="week" <?php echo $card_type_filter === 'week' ? 'selected' : ''; ?>>周卡</option>
                                    <option value="month" <?php echo $card_type_filter === 'month' ? 'selected' : ''; ?>>月卡</option>
                                    <option value="quarter" <?php echo $card_type_filter === 'quarter' ? 'selected' : ''; ?>>季度卡</option>
                                    <option value="halfyear" <?php echo $card_type_filter === 'halfyear' ? 'selected' : ''; ?>>半年卡</option>
                                    <option value="year" <?php echo $card_type_filter === 'year' ? 'selected' : ''; ?>>年卡</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">状态</label>
                                <select class="form-control" name="status">
                                    <option value="">全部</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>未激活</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>已激活</option>
                                    <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>已过期</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> 搜索
                                </button>
                                <a href="?" class="btn btn-secondary ms-2">重置</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 批量操作面板 -->
                <div class="card mb-4" id="batchActionsPanel" style="display: none;">
                    <div class="card-header">
                        <h6 class="mb-0">批量操作</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <button class="btn btn-outline-warning w-100" onclick="showReassignModal()">
                                    <i class="bi bi-arrow-repeat"></i> 批量重新分配账号
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-outline-danger w-100" onclick="batchDelete()">
                                    <i class="bi bi-trash"></i> 批量删除
                                </button>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-outline-secondary w-100" onclick="hideBatchActions()">
                                    <i class="bi bi-x"></i> 取消操作
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 分享页列表 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">分享页列表 (共 <?php echo $total_count; ?> 个)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        </th>
                                        <th>分享码</th>
                                        <th>分享链接</th>
                                        <th>Netflix账号</th>
                                        <th>卡类型</th>
                                        <th>使用用户</th>
                                        <th>状态</th>
                                        <th>创建时间</th>
                                        <th>激活时间</th>
                                        <th>到期时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($share_pages as $page): ?>
                                        <?php
                                        $is_active = $page['is_activated'] && $page['expires_at'] && strtotime($page['expires_at']) > time();
                                        $is_expired = $page['is_activated'] && $page['expires_at'] && strtotime($page['expires_at']) <= time();
                                        ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="share-checkbox" value="<?php echo $page['id']; ?>" onchange="updateBatchButtons()">
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($page['share_code']); ?></code>
                                        </td>
                                        <td>
                                            <?php $share_url = generateShareUrl($page['share_code']); ?>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($share_url); ?>" readonly>
                                                <button class="btn btn-outline-primary btn-sm" onclick="copyToClipboard('<?php echo htmlspecialchars($share_url, ENT_QUOTES); ?>')">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($page['netflix_email']); ?>
                                                <br>
                                                <span class="badge badge-sm bg-info"><?php echo ucfirst($page['subscription_type']); ?></span>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo getCardTypeName($page['card_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($page['username']): ?>
                                                <small>
                                                    <?php echo htmlspecialchars($page['username']); ?>
                                                    <?php if ($page['user_email']): ?>
                                                        <br><span class="text-muted"><?php echo htmlspecialchars($page['user_email']); ?></span>
                                                    <?php endif; ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">未使用</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$page['is_activated']): ?>
                                                <span class="badge bg-warning">未激活</span>
                                            <?php elseif ($is_expired): ?>
                                                <span class="badge bg-danger">已过期</span>
                                            <?php elseif ($is_active): ?>
                                                <span class="badge bg-success">使用中</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">未知状态</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo $page['created_at']; ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo $page['activated_at'] ?? '未激活'; ?></small>
                                        </td>
                                        <td>
                                            <?php if ($page['expires_at']): ?>
                                                <small class="<?php echo $is_expired ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo $page['expires_at']; ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">未激活</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo generateShareUrl($page['share_code']); ?>" 
                                                   target="_blank" class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteSharePage(<?php echo (int)$page['id']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 分页 -->
                <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($_GET); ?>">上一页</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => null])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($_GET); ?>">下一页</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 创建分享页模态框 -->
    <div class="modal fade" id="createShareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">批量创建分享页</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_share">
                        
                        <div class="mb-3">
                            <label for="netflix_account_id" class="form-label">选择Netflix账号</label>
                            <select class="form-control" id="netflix_account_id" name="netflix_account_id" required>
                                <option value="">请选择账号</option>
                                <option value="0" selected>🎲 智能随机分配 (推荐)</option>
                                <optgroup label="手动选择特定账号">
                                    <?php foreach ($active_accounts as $account): ?>
                                        <option value="<?php echo $account['id']; ?>">
                                            <?php echo htmlspecialchars($account['email']); ?> 
                                            (<?php echo ucfirst($account['subscription_type']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> 
                                推荐使用智能随机分配，系统将自动选择使用次数最少的账号，确保负载均衡
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="card_type" class="form-label">卡类型</label>
                            <select class="form-control" id="card_type" name="card_type" required>
                                <option value="day">天卡 (1天) - 适合试用</option>
                                <option value="week">周卡 (7天) - 短期使用</option>
                                <option value="month" selected>月卡 (30天) - 推荐</option>
                                <option value="quarter">季度卡 (90天) - 长期使用</option>
                                <option value="halfyear">半年卡 (180天) - 超值</option>
                                <option value="year">年卡 (365天) - 最优惠</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="quantity" class="form-label">生成数量</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   value="1" min="1" max="50" required>
                            <div class="form-text">一次最多生成50个分享页</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> 创建分享页
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 批量重新分配模态框 -->
    <div class="modal fade" id="batchReassignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">批量重新分配账号</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="batchReassignForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="batch_reassign">
                        <input type="hidden" name="share_page_ids" id="reassign_page_ids">
                        
                        <div class="mb-3">
                            <label for="new_account_id" class="form-label">选择新的Netflix账号</label>
                            <select class="form-control" id="new_account_id" name="new_account_id" required>
                                <option value="">请选择分配方式</option>
                                <option value="0">🎲 智能随机分配 (推荐)</option>
                                <optgroup label="手动选择特定账号">
                                    <?php foreach ($active_accounts as $account): ?>
                                        <option value="<?php echo $account['id']; ?>">
                                            <?php echo htmlspecialchars($account['email']); ?> 
                                            (<?php echo ucfirst($account['subscription_type']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            将重新为选中的分享页分配Netflix账号。智能随机分配会自动选择使用次数最少的账号。
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-arrow-repeat"></i> 重新分配
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 批量删除确认表单 -->
    <form id="batchDeleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="batch_delete">
        <input type="hidden" name="share_page_ids" id="delete_page_ids">
    </form>

    <!-- 删除确认表单 -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_share">
        <input type="hidden" name="share_id" id="delete_share_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast('链接已复制到剪贴板');
                });
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('链接已复制到剪贴板');
            }
        }

        function deleteSharePage(shareId) {
            if (confirm('确定要删除此分享页吗？此操作不可撤销！')) {
                document.getElementById('delete_share_id').value = shareId;
                document.getElementById('deleteForm').submit();
            }
        }

        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast show position-fixed top-0 start-50 translate-middle-x mt-3';
            toast.innerHTML = `
                <div class="toast-body bg-success text-white rounded">
                    <i class="bi bi-check-circle me-2"></i>${message}
                </div>
            `;
            
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // 批量操作相关函数
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.share-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateBatchButtons();
        }

        function updateBatchButtons() {
            const checkboxes = document.querySelectorAll('.share-checkbox:checked');
            const batchBtn = document.getElementById('batchActionsBtn');
            
            if (checkboxes.length > 0) {
                batchBtn.style.display = 'inline-block';
            } else {
                batchBtn.style.display = 'none';
                hideBatchActions();
            }
        }

        function showBatchActions() {
            document.getElementById('batchActionsPanel').style.display = 'block';
        }

        function hideBatchActions() {
            document.getElementById('batchActionsPanel').style.display = 'none';
            
            // 取消所有选择
            document.getElementById('selectAll').checked = false;
            document.querySelectorAll('.share-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            updateBatchButtons();
        }

        function showReassignModal() {
            const checkboxes = document.querySelectorAll('.share-checkbox:checked');
            const pageIds = Array.from(checkboxes).map(cb => cb.value);
            
            if (pageIds.length === 0) {
                alert('请先选择要重新分配的分享页');
                return;
            }
            
            document.getElementById('reassign_page_ids').value = pageIds.join(',');
            new bootstrap.Modal(document.getElementById('batchReassignModal')).show();
        }

        function batchDelete() {
            const checkboxes = document.querySelectorAll('.share-checkbox:checked');
            const pageIds = Array.from(checkboxes).map(cb => cb.value);
            
            if (pageIds.length === 0) {
                alert('请先选择要删除的分享页');
                return;
            }
            
            if (confirm(`确定要删除选中的 ${pageIds.length} 个分享页吗？此操作不可撤销！`)) {
                document.getElementById('delete_page_ids').value = pageIds.join(',');
                document.getElementById('batchDeleteForm').submit();
            }
        }
    </script>
</body>
</html>
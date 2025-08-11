<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

checkAdminAccess();

$error = '';
$success = '';

// 处理用户状态更新
if ($_POST['action'] ?? '' === 'update_user_status') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    
    if ($user_id > 0 && in_array($status, ['active', 'inactive', 'banned'])) {
        $pdo = getConnection();
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $user_id])) {
            $success = '用户状态更新成功';
        } else {
            $error = '用户状态更新失败';
        }
    }
}

// 获取用户列表
$pdo = getConnection();
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// 获取总数
$count_sql = "SELECT COUNT(*) FROM users {$where_clause}";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetchColumn();

// 获取用户列表及其分享页统计
$sql = "
    SELECT u.*, 
           COUNT(sp.id) as share_count,
           COUNT(CASE WHEN sp.is_activated = 1 THEN 1 END) as activated_count
    FROM users u
    LEFT JOIN share_pages sp ON u.id = sp.user_id
    {$where_clause}
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT {$per_page} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$total_pages = ceil($total_count / $per_page);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 奈飞分享系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-tv"></i> 奈飞分享系统
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">
                    <i class="bi bi-house"></i> 返回首页
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-people"></i> 用户管理</h2>
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

                <!-- 用户统计卡片 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <h5 class="card-title text-success">活跃用户</h5>
                                <h3 class="card-text">
                                    <?php echo count(array_filter($users, fn($u) => $u['status'] === 'active')); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-warning">
                            <div class="card-body">
                                <h5 class="card-title text-warning">未激活</h5>
                                <h3 class="card-text">
                                    <?php echo count(array_filter($users, fn($u) => $u['status'] === 'inactive')); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-danger">
                            <div class="card-body">
                                <h5 class="card-title text-danger">已封禁</h5>
                                <h3 class="card-text">
                                    <?php echo count(array_filter($users, fn($u) => $u['status'] === 'banned')); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-info">
                            <div class="card-body">
                                <h5 class="card-title text-info">今日注册</h5>
                                <h3 class="card-text">
                                    <?php 
                                    $today_users = array_filter($users, fn($u) => date('Y-m-d', strtotime($u['created_at'])) === date('Y-m-d'));
                                    echo count($today_users);
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 搜索和过滤器 -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">搜索用户</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="用户名或邮箱">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">状态过滤</label>
                                <select class="form-control" name="status">
                                    <option value="">全部状态</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>活跃</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>未激活</option>
                                    <option value="banned" <?php echo $status_filter === 'banned' ? 'selected' : ''; ?>>已封禁</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search"></i> 搜索
                                </button>
                                <a href="?" class="btn btn-secondary">重置</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 用户列表 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">用户列表 (共 <?php echo $total_count; ?> 个用户)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>用户名</th>
                                        <th>邮箱</th>
                                        <th>手机</th>
                                        <th>状态</th>
                                        <th>分享页数量</th>
                                        <th>注册IP</th>
                                        <th>注册时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($user['email']): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>">
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">未填写</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $user['phone'] ? htmlspecialchars($user['phone']) : '<span class="text-muted">未填写</span>'; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_colors = [
                                                'active' => 'success',
                                                'inactive' => 'warning',
                                                'banned' => 'danger'
                                            ];
                                            $status_names = [
                                                'active' => '活跃',
                                                'inactive' => '未激活',
                                                'banned' => '已封禁'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $status_colors[$user['status']]; ?>">
                                                <?php echo $status_names[$user['status']]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info me-1">总计: <?php echo $user['share_count']; ?></span>
                                            <span class="badge bg-success">已激活: <?php echo $user['activated_count']; ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($user['registration_ip']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small><?php echo $user['created_at']; ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="bi bi-gear"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><h6 class="dropdown-header">用户操作</h6></li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="updateUserStatus(<?php echo $user['id']; ?>, 'active')">
                                                                <i class="bi bi-check-circle text-success"></i> 设为活跃
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="updateUserStatus(<?php echo $user['id']; ?>, 'inactive')">
                                                                <i class="bi bi-pause-circle text-warning"></i> 设为未激活
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="updateUserStatus(<?php echo $user['id']; ?>, 'banned')">
                                                                <i class="bi bi-shield-x text-danger"></i> 封禁用户
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item" href="share-pages.php?search=<?php echo urlencode($user['username']); ?>">
                                                                <i class="bi bi-share"></i> 查看分享页
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">上一页</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">下一页</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 隐藏表单 -->
    <form id="userStatusForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_user_status">
        <input type="hidden" name="user_id" id="status_user_id">
        <input type="hidden" name="status" id="status_user_value">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateUserStatus(userId, status) {
            const statusNames = {
                'active': '活跃',
                'inactive': '未激活',
                'banned': '封禁'
            };
            
            if (confirm(`确定要将用户状态设为"${statusNames[status]}"吗？`)) {
                document.getElementById('status_user_id').value = userId;
                document.getElementById('status_user_value').value = status;
                document.getElementById('userStatusForm').submit();
            }
        }
    </script>
</body>
</html>
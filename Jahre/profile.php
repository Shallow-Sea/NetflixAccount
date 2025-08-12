<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查管理员权限
checkAdminAccess();

$error = '';
$success = '';

// 获取当前管理员信息
$user = getAdminById($_SESSION['admin_id']);

// 处理密码修改
if ($_POST['action'] ?? '' === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password)) {
        $error = '请填写所有密码字段';
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = '当前密码错误';
    } elseif ($new_password !== $confirm_password) {
        $error = '新密码与确认密码不匹配';
    } elseif (strlen($new_password) < 6) {
        $error = '新密码长度至少6位';
    } else {
        $pdo = getConnection();
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user['id']])) {
            $success = '密码修改成功';
        } else {
            $error = '密码修改失败';
        }
    }
}

// 处理管理员添加
if ($_POST['action'] ?? '' === 'add_admin') {
    $admin_username = sanitizeInput($_POST['admin_username'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';
    
    if (empty($admin_username) || empty($admin_password)) {
        $error = '请填写管理员用户名和密码';
    } elseif (strlen($admin_password) < 6) {
        $error = '管理员密码长度至少6位';
    } else {
        $pdo = getConnection();
        
        // 检查管理员用户名是否已存在
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$admin_username]);
        if ($stmt->fetch()) {
            $error = '管理员用户名已存在';
        } else {
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            if ($stmt->execute([$admin_username, $hashed_password])) {
                $success = '管理员添加成功';
            } else {
                $error = '管理员添加失败';
            }
        }
    }
}

// 处理删除管理员
if ($_POST['action'] ?? '' === 'delete_admin') {
    $admin_id = (int)($_POST['admin_id'] ?? 0);
    
    if ($admin_id > 0 && $admin_id != $_SESSION['admin_id']) {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        if ($stmt->execute([$admin_id])) {
            $success = '管理员删除成功';
        } else {
            $error = '管理员删除失败';
        }
    } else {
        $error = '不能删除当前登录的管理员';
    }
}

// 获取管理员列表
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT id, username, created_at FROM admins ORDER BY created_at DESC");
$stmt->execute();
$admins = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员设置 - 奈飞分享系统</title>
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
                        <a class="nav-link" href="share-pages.php">
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
                        <a class="nav-link active" href="profile.php">
                            <i class="bi bi-gear"></i> 管理员设置
                        </a>
                    </li>
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

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <h2>
                    <i class="bi bi-gear-fill"></i> 管理员设置
                </h2>

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

                <!-- 基本信息 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">基本信息</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-3"><strong>管理员用户名:</strong></div>
                            <div class="col-sm-9"><?php echo htmlspecialchars($user['username']); ?></div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3"><strong>创建时间:</strong></div>
                            <div class="col-sm-9"><?php echo $user['created_at']; ?></div>
                        </div>
                    </div>
                </div>

                <!-- 修改密码 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">修改密码</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">当前密码</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">新密码</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">确认新密码</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-key"></i> 修改密码
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 管理员管理 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">管理员管理</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="action" value="add_admin">
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="admin_username" class="form-label">管理员用户名</label>
                                    <input type="text" class="form-control" id="admin_username" name="admin_username" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="admin_password" class="form-label">管理员密码</label>
                                    <input type="password" class="form-control" id="admin_password" name="admin_password" minlength="6" required>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-person-plus"></i> 添加管理员
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <h6>现有管理员列表</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>用户名</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($admin['username']); ?>
                                            <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                                <span class="badge bg-primary ms-1">当前</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo $admin['created_at']; ?></small></td>
                                        <td>
                                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteAdmin(<?php echo $admin['id']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 删除管理员确认表单 -->
    <form id="deleteAdminForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_admin">
        <input type="hidden" name="admin_id" id="delete_admin_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 密码确认验证
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('密码不匹配');
            } else {
                this.setCustomValidity('');
            }
        });
        
        function deleteAdmin(adminId) {
            if (confirm('确定要删除此管理员吗？此操作不可撤销！')) {
                document.getElementById('delete_admin_id').value = adminId;
                document.getElementById('deleteAdminForm').submit();
            }
        }
    </script>
</body>
</html>
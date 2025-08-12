<?php
session_start();
require_once '../includes/functions.php';

requireLogin();

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// 处理各种操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add') {
        $data = [
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? '',
            'email' => $_POST['email'] ?? '',
            'is_active' => isset($_POST['is_active'])
        ];
        
        if (empty($data['username']) || empty($data['password'])) {
            $error = '用户名和密码不能为空';
        } elseif (strlen($data['password']) < 6) {
            $error = '密码长度至少6位';
        } else {
            $result = addAdmin($data);
            if ($result) {
                $success = '管理员添加成功';
                $action = 'list';
            } else {
                $error = '添加失败，用户名可能已存在';
            }
        }
    } elseif ($_POST['action'] === 'change_password') {
        $admin_id = $_POST['admin_id'] ?? 0;
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = '所有密码字段都不能为空';
        } elseif ($new_password !== $confirm_password) {
            $error = '新密码和确认密码不匹配';
        } elseif (strlen($new_password) < 6) {
            $error = '新密码长度至少6位';
        } else {
            // 验证当前密码
            global $pdo;
            $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch();
            
            if (!$admin || !password_verify($current_password, $admin['password'])) {
                $error = '当前密码错误';
            } else {
                if (updateAdminPassword($admin_id, $new_password)) {
                    $success = '密码修改成功';
                } else {
                    $error = '密码修改失败';
                }
            }
        }
    } elseif ($_POST['action'] === 'toggle_status') {
        $id = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? 0;
        
        // 防止停用自己的账号
        if ($id == $_SESSION['admin_id'] && $status == 0) {
            $error = '不能停用自己的账号';
        } else {
            global $pdo;
            $stmt = $pdo->prepare("UPDATE admins SET is_active = ? WHERE id = ?");
            if ($stmt->execute([$status, $id])) {
                $success = '状态更新成功';
                logOperation($_SESSION['admin_id'], 'toggle_status', 'admin', $id, '切换管理员状态: ' . ($status ? '启用' : '停用'));
            } else {
                $error = '状态更新失败';
            }
        }
    }
}

// 获取管理员列表
$admins = getAdmins();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员管理 - 奈飞账号管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            margin: 0.25rem 0;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateX(5px);
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .brand-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        .current-admin {
            background: #e7f3ff;
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body>
    <!-- 侧边栏 -->
    <nav class="sidebar">
        <div class="p-3">
            <a href="dashboard.php" class="brand-title text-decoration-none">
                Netflix 管理
            </a>
        </div>
        
        <ul class="nav flex-column px-3">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    仪表板
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="netflix-accounts.php">
                    <i class="bi bi-tv me-2"></i>
                    奈飞账号管理
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="share-pages.php">
                    <i class="bi bi-share me-2"></i>
                    分享页管理
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="announcements.php">
                    <i class="bi bi-megaphone me-2"></i>
                    公告管理
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="admins.php">
                    <i class="bi bi-people me-2"></i>
                    管理员管理
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    退出登录
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- 主要内容区域 -->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">管理员管理</h1>
            <div>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    <i class="bi bi-plus-circle me-1"></i>添加管理员
                </button>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    <i class="bi bi-key me-1"></i>修改密码
                </button>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- 管理员列表 -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">管理员列表</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>用户名</th>
                                <th>邮箱</th>
                                <th>状态</th>
                                <th>创建时间</th>
                                <th>最后更新</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <tr class="<?php echo $admin['id'] == $_SESSION['admin_id'] ? 'current-admin' : ''; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                        <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                            <span class="badge bg-primary ms-2">当前用户</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($admin['email']): ?>
                                            <?php echo htmlspecialchars($admin['email']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">未设置</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $admin['is_active'] ? '0' : '1'; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-<?php echo $admin['is_active'] ? 'success' : 'secondary'; ?>"
                                                    <?php echo $admin['id'] == $_SESSION['admin_id'] ? 'disabled' : ''; ?>>
                                                <i class="bi bi-<?php echo $admin['is_active'] ? 'check-circle' : 'pause-circle'; ?>"></i>
                                                <?php echo $admin['is_active'] ? '活跃' : '停用'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <small><?php echo date('Y-m-d H:i', strtotime($admin['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo date('Y-m-d H:i', strtotime($admin['updated_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                                <i class="bi bi-key"></i>
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
    </main>
    
    <!-- 添加管理员模态框 -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">添加管理员</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">用户名 *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">密码 *</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   minlength="6" required>
                            <div class="form-text">密码长度至少6位</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">邮箱（可选）</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                立即启用
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">添加管理员</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- 修改密码模态框 -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">修改密码</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="admin_id" value="<?php echo $_SESSION['admin_id']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">当前密码 *</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">新密码 *</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   minlength="6" required>
                            <div class="form-text">密码长度至少6位</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">确认新密码 *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   minlength="6" required>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            修改密码后需要重新登录
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-warning">修改密码</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 验证密码匹配
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('密码不匹配');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // 清空表单当模态框关闭时
        document.getElementById('addAdminModal').addEventListener('hidden.bs.modal', function() {
            this.querySelector('form').reset();
        });
        
        document.getElementById('changePasswordModal').addEventListener('hidden.bs.modal', function() {
            this.querySelector('form').reset();
        });
        
        // 状态切换确认
        document.querySelectorAll('button[name="id"]').forEach(button => {
            if (button.closest('form').querySelector('input[name="action"][value="toggle_status"]')) {
                button.addEventListener('click', function(e) {
                    const status = this.closest('form').querySelector('input[name="status"]').value;
                    const action = status === '1' ? '启用' : '停用';
                    
                    if (!confirm(`确定要${action}此管理员吗？`)) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>
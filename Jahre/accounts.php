<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查管理员权限
checkAdminAccess();

$error = '';
$success = '';

// 处理添加账号
if ($_POST['action'] ?? '' === 'add_account') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $subscription_type = sanitizeInput($_POST['subscription_type'] ?? 'premium');
    
    if (empty($email) || empty($password)) {
        $error = '请填写完整的账号信息';
    } elseif (!validateEmail($email)) {
        $error = '请输入正确的邮箱格式';
    } else {
        if (addNetflixAccount($email, $password, $subscription_type)) {
            $success = '账号添加成功';
        } else {
            $error = '账号添加失败，可能已存在';
        }
    }
}

// 处理状态更新
if ($_POST['action'] ?? '' === 'update_status') {
    $account_id = (int)($_POST['account_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    
    // 验证状态值是否有效
    $valid_statuses = ['active', 'inactive', 'expired', 'banned'];
    if (!in_array($status, $valid_statuses)) {
        $error = '无效的状态值';
    } elseif ($account_id <= 0) {
        $error = '无效的账号ID';
    } elseif (updateNetflixAccountStatus($account_id, $status)) {
        $success = '状态更新成功';
    } else {
        $error = '状态更新失败';
    }
}

// 处理删除账号
if ($_POST['action'] ?? '' === 'delete_account') {
    $account_id = (int)($_POST['account_id'] ?? 0);
    
    if ($account_id > 0) {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM netflix_accounts WHERE id = ?");
        if ($stmt->execute([$account_id])) {
            $success = '账号删除成功';
        } else {
            $error = '账号删除失败';
        }
    }
}

// 获取账号列表
$accounts = getNetflixAccounts();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>账号管理 - 奈飞分享系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
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
                        <a class="nav-link active" href="accounts.php">
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
                    <h2><i class="bi bi-tv"></i> Netflix 账号管理</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                        <i class="bi bi-plus-circle"></i> 添加账号
                    </button>
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

                <!-- 账号统计卡片 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <h5 class="card-title text-success">活跃账号</h5>
                                <h3 class="card-text">
                                    <?php echo count(array_filter($accounts, fn($a) => $a['status'] === 'active')); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-warning">
                            <div class="card-body">
                                <h5 class="card-title text-warning">未激活</h5>
                                <h3 class="card-text">
                                    <?php echo count(array_filter($accounts, fn($a) => $a['status'] === 'inactive')); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-danger">
                            <div class="card-body">
                                <h5 class="card-title text-danger">已过期</h5>
                                <h3 class="card-text">
                                    <?php echo count(array_filter($accounts, fn($a) => $a['status'] === 'expired')); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-dark">
                            <div class="card-body">
                                <h5 class="card-title text-dark">已封禁</h5>
                                <h3 class="card-text">
                                    <?php echo count(array_filter($accounts, fn($a) => $a['status'] === 'banned')); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 账号列表 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">账号列表 (共 <?php echo count($accounts); ?> 个账号)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>邮箱账号</th>
                                        <th>密码</th>
                                        <th>套餐类型</th>
                                        <th>状态</th>
                                        <th>配置使用情况</th>
                                        <th>到期时间</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($accounts as $account): ?>
                                    <tr>
                                        <td><?php echo $account['id']; ?></td>
                                        <td>
                                            <code><?php echo htmlspecialchars($account['email']); ?></code>
                                            <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyToClipboard('<?php echo htmlspecialchars($account['email']); ?>')">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <span class="password-field" data-password="<?php echo htmlspecialchars($account['password']); ?>">
                                                ••••••••
                                            </span>
                                            <button class="btn btn-sm btn-outline-secondary ms-1" onclick="togglePassword(this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('<?php echo htmlspecialchars($account['password']); ?>')">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo ucfirst($account['subscription_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_colors = [
                                                'active' => 'success',
                                                'inactive' => 'warning',
                                                'expired' => 'danger',
                                                'banned' => 'dark'
                                            ];
                                            $status_names = [
                                                'active' => '活跃',
                                                'inactive' => '未激活',
                                                'expired' => '已过期',
                                                'banned' => '已封禁'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $status_colors[$account['status']]; ?>">
                                                <?php echo $status_names[$account['status']]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo ($account['used_profiles'] / $account['profile_count']) * 100; ?>%">
                                                    <?php echo $account['used_profiles']; ?> / <?php echo $account['profile_count']; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo $account['expires_at'] ? $account['expires_at'] : '永久'; ?>
                                        </td>
                                        <td><?php echo $account['created_at']; ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <!-- 状态更新下拉菜单 -->
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="bi bi-gear"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><h6 class="dropdown-header">更新状态</h6></li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $account['id']; ?>, 'active')">
                                                                <i class="bi bi-check-circle text-success"></i> 设为活跃
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $account['id']; ?>, 'inactive')">
                                                                <i class="bi bi-pause-circle text-warning"></i> 设为未激活
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $account['id']; ?>, 'expired')">
                                                                <i class="bi bi-x-circle text-danger"></i> 设为过期
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="updateStatus(<?php echo $account['id']; ?>, 'banned')">
                                                                <i class="bi bi-shield-x text-dark"></i> 设为封禁
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" onclick="deleteAccount(<?php echo $account['id']; ?>)">
                                                                <i class="bi bi-trash"></i> 删除账号
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                                
                                                <!-- 创建分享页按钮 -->
                                                <button class="btn btn-outline-success" onclick="createSharePage(<?php echo $account['id']; ?>)" 
                                                        <?php echo $account['status'] !== 'active' ? 'disabled' : ''; ?>>
                                                    <i class="bi bi-share"></i>
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
            </div>
        </div>
    </div>

    <!-- 添加账号模态框 -->
    <div class="modal fade" id="addAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">添加 Netflix 账号</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_account">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">邮箱账号</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">密码</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subscription_type" class="form-label">套餐类型</label>
                            <select class="form-control" id="subscription_type" name="subscription_type">
                                <option value="basic">Basic</option>
                                <option value="standard">Standard</option>
                                <option value="premium" selected>Premium</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">添加账号</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 创建分享页模态框 -->
    <div class="modal fade" id="createShareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">创建分享页</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="share-pages.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_share">
                        <input type="hidden" name="netflix_account_id" id="share_account_id">
                        
                        <div class="mb-3">
                            <label for="card_type" class="form-label">卡类型</label>
                            <select class="form-control" id="card_type" name="card_type" required>
                                <option value="day">天卡 (1天)</option>
                                <option value="week">周卡 (7天)</option>
                                <option value="month" selected>月卡 (30天)</option>
                                <option value="quarter">季度卡 (90天)</option>
                                <option value="halfyear">半年卡 (180天)</option>
                                <option value="year">年卡 (365天)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="quantity" class="form-label">生成数量</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="50">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">创建分享页</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 隐藏表单用于状态更新和删除 -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="account_id" id="status_account_id">
        <input type="hidden" name="status" id="status_value">
    </form>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_account">
        <input type="hidden" name="account_id" id="delete_account_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 复制到剪贴板
        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast('已复制到剪贴板');
                });
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('已复制到剪贴板');
            }
        }

        // 显示/隐藏密码
        function togglePassword(button) {
            const passwordField = button.parentElement.querySelector('.password-field');
            const password = passwordField.getAttribute('data-password');
            const icon = button.querySelector('i');
            
            if (passwordField.textContent === '••••••••') {
                passwordField.textContent = password;
                icon.className = 'bi bi-eye-slash';
            } else {
                passwordField.textContent = '••••••••';
                icon.className = 'bi bi-eye';
            }
        }

        // 更新状态
        function updateStatus(accountId, status) {
            // 状态名称映射
            const statusNames = {
                'active': '活跃',
                'inactive': '未激活',
                'expired': '过期',
                'banned': '封禁'
            };
            
            // 验证状态值
            const validStatuses = ['active', 'inactive', 'expired', 'banned'];
            if (!validStatuses.includes(status)) {
                alert('无效的状态值: ' + status);
                return;
            }
            
            const statusName = statusNames[status] || status;
            if (confirm(`确定要将账号状态设为"${statusName}"吗？`)) {
                document.getElementById('status_account_id').value = accountId;
                document.getElementById('status_value').value = status;
                document.getElementById('statusForm').submit();
            }
        }

        // 删除账号
        function deleteAccount(accountId) {
            if (confirm('确定要删除此账号吗？此操作不可撤销！')) {
                document.getElementById('delete_account_id').value = accountId;
                document.getElementById('deleteForm').submit();
            }
        }

        // 创建分享页
        function createSharePage(accountId) {
            document.getElementById('share_account_id').value = accountId;
            new bootstrap.Modal(document.getElementById('createShareModal')).show();
        }

        // 显示提示
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
    </script>
</body>
</html>
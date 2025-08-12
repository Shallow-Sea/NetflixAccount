<?php
session_start();
require_once '../includes/functions.php';

requireLogin();

$page = $_GET['page'] ?? 1;
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// 处理各种操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add') {
        $data = [
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'subscription_type' => $_POST['subscription_type'] ?? 'premium',
            'status' => $_POST['status'] ?? 'active',
            'slot1_enabled' => isset($_POST['slot1_enabled']),
            'slot1_pin' => $_POST['slot1_pin'] ?? null,
            'slot2_enabled' => isset($_POST['slot2_enabled']),
            'slot2_pin' => $_POST['slot2_pin'] ?? null,
            'slot3_enabled' => isset($_POST['slot3_enabled']),
            'slot3_pin' => $_POST['slot3_pin'] ?? null,
            'slot4_enabled' => isset($_POST['slot4_enabled']),
            'slot4_pin' => $_POST['slot4_pin'] ?? null,
            'slot5_enabled' => isset($_POST['slot5_enabled']),
            'slot5_pin' => $_POST['slot5_pin'] ?? null,
        ];
        
        if (empty($data['email']) || empty($data['password'])) {
            $error = '邮箱和密码不能为空';
        } else {
            $result = addNetflixAccount($data);
            if ($result) {
                $success = '奈飞账号添加成功';
                $action = 'list';
            } else {
                $error = '添加失败，邮箱可能已存在';
            }
        }
    } elseif ($_POST['action'] === 'batch_add') {
        $accounts_text = $_POST['accounts_text'] ?? '';
        if (empty($accounts_text)) {
            $error = '账号信息不能为空';
        } else {
            $lines = explode("\n", trim($accounts_text));
            $accounts = [];
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $accounts[] = [
                        'email' => trim($parts[0]),
                        'password' => trim($parts[1]),
                        'subscription_type' => $_POST['batch_subscription_type'] ?? 'premium',
                        'status' => 'active'
                    ];
                }
            }
            
            if (!empty($accounts)) {
                $result = batchAddNetflixAccounts($accounts);
                $success = "成功添加 {$result['success_count']} 个账号";
                if (!empty($result['failed_accounts'])) {
                    $error = '失败的账号: ' . implode(', ', $result['failed_accounts']);
                }
                $action = 'list';
            } else {
                $error = '没有找到有效的账号信息，格式应为：邮箱:密码';
            }
        }
    } elseif ($_POST['action'] === 'batch_update_status') {
        $ids = $_POST['selected_ids'] ?? [];
        $status = $_POST['batch_status'] ?? '';
        
        if (empty($ids)) {
            $error = '请选择要操作的账号';
        } elseif (empty($status)) {
            $error = '请选择要设置的状态';
        } else {
            if (batchUpdateNetflixAccountStatus($ids, $status)) {
                $success = '批量更新状态成功';
            } else {
                $error = '批量更新失败';
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? 0;
        if ($id && deleteNetflixAccount($id)) {
            $success = '删除成功';
        } else {
            $error = '删除失败';
        }
    }
}

// 获取账号列表
$filters = [
    'status' => $_GET['filter_status'] ?? '',
    'subscription_type' => $_GET['filter_subscription'] ?? '',
    'email' => $_GET['filter_email'] ?? ''
];

$accounts = getNetflixAccounts($page, 20, $filters);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>奈飞账号管理 - 奈飞账号管理系统</title>
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
        .slot-config {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .slot-item {
            display: flex;
            align-items-center;
            margin-bottom: 0.5rem;
        }
        .slot-item:last-child {
            margin-bottom: 0;
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
                <a class="nav-link active" href="netflix-accounts.php">
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
                <a class="nav-link" href="admins.php">
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
            <h1 class="h3">奈飞账号管理</h1>
            <div>
                <a href="?action=add" class="btn btn-primary me-2">
                    <i class="bi bi-plus-circle me-1"></i>添加账号
                </a>
                <a href="?action=batch_add" class="btn btn-success">
                    <i class="bi bi-upload me-1"></i>批量添加
                </a>
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
        
        <?php if ($action === 'add'): ?>
            <!-- 添加账号表单 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">添加奈飞账号</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">邮箱 *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">密码 *</label>
                                <input type="text" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="subscription_type" class="form-label">套餐类型</label>
                                <select class="form-select" id="subscription_type" name="subscription_type">
                                    <option value="premium">Premium</option>
                                    <option value="standard">Standard</option>
                                    <option value="basic">Basic</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">状态</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active">活跃</option>
                                    <option value="inactive">非活跃</option>
                                    <option value="suspended">暂停</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="slot-config">
                            <h6>车位配置</h6>
                            <div class="row">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="slot-item">
                                            <div class="form-check me-3">
                                                <input class="form-check-input" type="checkbox" id="slot<?php echo $i; ?>_enabled" name="slot<?php echo $i; ?>_enabled" checked>
                                                <label class="form-check-label" for="slot<?php echo $i; ?>_enabled">
                                                    车位<?php echo $i; ?>
                                                </label>
                                            </div>
                                            <input type="text" class="form-control form-control-sm" 
                                                   name="slot<?php echo $i; ?>_pin" 
                                                   placeholder="PIN码(可选)" style="width: 100px;">
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>添加账号
                            </button>
                            <a href="?" class="btn btn-secondary">取消</a>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php elseif ($action === 'batch_add'): ?>
            <!-- 批量添加表单 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">批量添加奈飞账号</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="batch_add">
                        
                        <div class="mb-3">
                            <label for="accounts_text" class="form-label">账号信息 *</label>
                            <textarea class="form-control" id="accounts_text" name="accounts_text" rows="10" 
                                      placeholder="每行一个账号，格式：邮箱:密码&#10;例如：&#10;example1@gmail.com:password1&#10;example2@gmail.com:password2" required></textarea>
                            <div class="form-text">每行一个账号，格式为：邮箱:密码</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="batch_subscription_type" class="form-label">统一套餐类型</label>
                            <select class="form-select" id="batch_subscription_type" name="batch_subscription_type">
                                <option value="premium">Premium</option>
                                <option value="standard">Standard</option>
                                <option value="basic">Basic</option>
                            </select>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-upload me-1"></i>批量添加
                            </button>
                            <a href="?" class="btn btn-secondary">取消</a>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php else: ?>
            <!-- 账号列表 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">账号列表</h5>
                </div>
                <div class="card-body">
                    <!-- 筛选和搜索 -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select class="form-select" name="filter_status">
                                <option value="">全部状态</option>
                                <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>活跃</option>
                                <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>非活跃</option>
                                <option value="suspended" <?php echo $filters['status'] === 'suspended' ? 'selected' : ''; ?>>暂停</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="filter_subscription">
                                <option value="">全部套餐</option>
                                <option value="premium" <?php echo $filters['subscription_type'] === 'premium' ? 'selected' : ''; ?>>Premium</option>
                                <option value="standard" <?php echo $filters['subscription_type'] === 'standard' ? 'selected' : ''; ?>>Standard</option>
                                <option value="basic" <?php echo $filters['subscription_type'] === 'basic' ? 'selected' : ''; ?>>Basic</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="filter_email" 
                                   placeholder="搜索邮箱" value="<?php echo htmlspecialchars($filters['email']); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">筛选</button>
                        </div>
                    </form>
                    
                    <!-- 批量操作 -->
                    <form method="POST" id="batchForm">
                        <input type="hidden" name="action" value="batch_update_status">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <select class="form-select" name="batch_status">
                                        <option value="">选择批量操作</option>
                                        <option value="active">设为活跃</option>
                                        <option value="inactive">设为非活跃</option>
                                        <option value="suspended">设为暂停</option>
                                    </select>
                                    <button type="submit" class="btn btn-warning">执行</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 账号表格 -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>邮箱</th>
                                        <th>套餐</th>
                                        <th>状态</th>
                                        <th>车位状态</th>
                                        <th>使用次数</th>
                                        <th>活跃分享</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($accounts['data'] as $account): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_ids[]" value="<?php echo $account['id']; ?>" class="account-checkbox">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($account['email']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($account['password']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $account['subscription_type'] === 'premium' ? 'success' : ($account['subscription_type'] === 'standard' ? 'primary' : 'secondary'); ?>">
                                                    <?php echo ucfirst($account['subscription_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $account['status'] === 'active' ? 'success' : ($account['status'] === 'inactive' ? 'secondary' : 'danger'); ?>">
                                                    <?php 
                                                    echo $account['status'] === 'active' ? '活跃' : 
                                                         ($account['status'] === 'inactive' ? '非活跃' : '暂停'); 
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="badge bg-<?php echo $account["slot{$i}_enabled"] ? 'success' : 'secondary'; ?> me-1">
                                                            <?php echo $i; ?>
                                                        </span>
                                                    <?php endfor; ?>
                                                </small>
                                            </td>
                                            <td><?php echo $account['usage_count']; ?></td>
                                            <td><?php echo $account['active_shares']; ?></td>
                                            <td><?php echo date('m-d H:i', strtotime($account['created_at'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="deleteAccount(<?php echo $account['id']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                    
                    <!-- 分页 -->
                    <?php if ($accounts['pages'] > 1): ?>
                        <nav>
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= $accounts['pages']; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- 删除确认模态框 -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">确认删除</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    确定要删除这个账号吗？此操作无法撤销。
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" class="btn btn-danger">确认删除</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 全选功能
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.account-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
        
        // 删除账号
        function deleteAccount(id) {
            document.getElementById('deleteId').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // 验证批量操作
        document.getElementById('batchForm').addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('.account-checkbox:checked');
            const operation = document.querySelector('[name="batch_status"]').value;
            
            if (selected.length === 0) {
                e.preventDefault();
                alert('请选择要操作的账号');
                return;
            }
            
            if (!operation) {
                e.preventDefault();
                alert('请选择要执行的操作');
                return;
            }
            
            if (!confirm(`确定要对选中的 ${selected.length} 个账号执行此操作吗？`)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
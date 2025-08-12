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
            'netflix_account_id' => $_POST['netflix_account_id'] ?? null,
            'card_type' => $_POST['card_type'] ?? 'month',
            'title' => $_POST['title'] ?? '奈飞高级账号分享',
            'description' => $_POST['description'] ?? null
        ];
        
        $result = addSharePage($data);
        if ($result) {
            $success = '分享页创建成功';
            $action = 'list';
        } else {
            $error = '创建失败，可能没有可用的奈飞账号';
        }
    } elseif ($_POST['action'] === 'batch_add') {
        $count = (int)($_POST['count'] ?? 0);
        $card_type = $_POST['card_type'] ?? 'month';
        $title = $_POST['title'] ?? null;
        
        if ($count <= 0 || $count > 100) {
            $error = '批量生成数量应在1-100之间';
        } else {
            $result = batchAddSharePages($count, $card_type, $title);
            $success = "成功创建 {$result['success_count']} 个分享页";
            
            // 存储分享码用于导出
            if (!empty($result['share_codes'])) {
                $_SESSION['last_generated_codes'] = $result['share_codes'];
            }
            
            $action = 'list';
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? 0;
        if ($id) {
            global $pdo;
            $stmt = $pdo->prepare("DELETE FROM share_pages WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = '删除成功';
            } else {
                $error = '删除失败';
            }
        }
    } elseif ($_POST['action'] === 'export') {
        $ids = $_POST['selected_ids'] ?? [];
        $format = $_POST['export_format'] ?? 'txt';
        
        if (empty($ids)) {
            $error = '请选择要导出的分享页';
        } else {
            $content = exportSharePages($ids, $format);
            if ($content) {
                $filename = 'share_pages_' . date('YmdHis') . '.' . ($format === 'excel' ? 'csv' : $format);
                
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . strlen($content));
                echo $content;
                exit;
            } else {
                $error = '导出失败';
            }
        }
    }
}

// 获取分享页列表
$filters = [
    'is_activated' => $_GET['filter_activated'] ?? '',
    'card_type' => $_GET['filter_card_type'] ?? '',
    'share_code' => $_GET['filter_code'] ?? ''
];

$share_pages = getSharePages($page, 20, $filters);

// 获取可用的奈飞账号
global $pdo;
$stmt = $pdo->prepare("SELECT id, email FROM netflix_accounts WHERE status = 'active' ORDER BY usage_count ASC");
$stmt->execute();
$available_accounts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分享页管理 - 奈飞账号管理系统</title>
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
        .share-code {
            font-family: monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .copy-btn {
            padding: 2px 6px;
            font-size: 0.7rem;
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
                <a class="nav-link active" href="share-pages.php">
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
            <h1 class="h3">分享页管理</h1>
            <div>
                <a href="?action=add" class="btn btn-primary me-2">
                    <i class="bi bi-plus-circle me-1"></i>创建分享页
                </a>
                <a href="?action=batch" class="btn btn-success">
                    <i class="bi bi-collection me-1"></i>批量生成
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
                
                <?php if (isset($_SESSION['last_generated_codes'])): ?>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-success" onclick="showGeneratedCodes()">
                            <i class="bi bi-eye me-1"></i>查看生成的分享码
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'add'): ?>
            <!-- 创建分享页表单 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">创建分享页</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="netflix_account_id" class="form-label">选择奈飞账号</label>
                                <select class="form-select" id="netflix_account_id" name="netflix_account_id">
                                    <option value="">自动分配（负载均衡）</option>
                                    <?php foreach ($available_accounts as $account): ?>
                                        <option value="<?php echo $account['id']; ?>">
                                            <?php echo htmlspecialchars($account['email']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">留空将自动选择负载最低的账号</div>
                            </div>
                            <div class="col-md-6">
                                <label for="card_type" class="form-label">卡类型</label>
                                <select class="form-select" id="card_type" name="card_type" required>
                                    <option value="day">天卡 (1天)</option>
                                    <option value="week">周卡 (7天)</option>
                                    <option value="month" selected>月卡 (30天)</option>
                                    <option value="quarter">季度卡 (90天)</option>
                                    <option value="half_year">半年卡 (180天)</option>
                                    <option value="year">年卡 (365天)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">分享页标题</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="奈飞高级账号分享" placeholder="奈飞高级账号分享">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">描述（可选）</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="分享页描述信息"></textarea>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>创建分享页
                            </button>
                            <a href="?" class="btn btn-secondary">取消</a>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php elseif ($action === 'batch'): ?>
            <!-- 批量生成表单 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">批量生成分享页</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="batch_add">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="count" class="form-label">生成数量 *</label>
                                <input type="number" class="form-control" id="count" name="count" 
                                       min="1" max="100" value="10" required>
                                <div class="form-text">一次最多生成100个</div>
                            </div>
                            <div class="col-md-6">
                                <label for="card_type" class="form-label">卡类型 *</label>
                                <select class="form-select" id="card_type" name="card_type" required>
                                    <option value="day">天卡 (1天)</option>
                                    <option value="week">周卡 (7天)</option>
                                    <option value="month" selected>月卡 (30天)</option>
                                    <option value="quarter">季度卡 (90天)</option>
                                    <option value="half_year">半年卡 (180天)</option>
                                    <option value="year">年卡 (365天)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">统一标题</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   placeholder="留空使用默认标题">
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            系统将自动为每个分享页分配负载最低的奈飞账号，确保负载均衡。
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-collection me-1"></i>批量生成
                            </button>
                            <a href="?" class="btn btn-secondary">取消</a>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php else: ?>
            <!-- 分享页列表 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">分享页列表</h5>
                </div>
                <div class="card-body">
                    <!-- 筛选和搜索 -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select class="form-select" name="filter_activated">
                                <option value="">全部状态</option>
                                <option value="true" <?php echo $filters['is_activated'] === 'true' ? 'selected' : ''; ?>>已激活</option>
                                <option value="false" <?php echo $filters['is_activated'] === 'false' ? 'selected' : ''; ?>>待激活</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="filter_card_type">
                                <option value="">全部卡类型</option>
                                <option value="day" <?php echo $filters['card_type'] === 'day' ? 'selected' : ''; ?>>天卡</option>
                                <option value="week" <?php echo $filters['card_type'] === 'week' ? 'selected' : ''; ?>>周卡</option>
                                <option value="month" <?php echo $filters['card_type'] === 'month' ? 'selected' : ''; ?>>月卡</option>
                                <option value="quarter" <?php echo $filters['card_type'] === 'quarter' ? 'selected' : ''; ?>>季度卡</option>
                                <option value="half_year" <?php echo $filters['card_type'] === 'half_year' ? 'selected' : ''; ?>>半年卡</option>
                                <option value="year" <?php echo $filters['card_type'] === 'year' ? 'selected' : ''; ?>>年卡</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="filter_code" 
                                   placeholder="搜索分享码" value="<?php echo htmlspecialchars($filters['share_code']); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">筛选</button>
                        </div>
                    </form>
                    
                    <!-- 批量操作 -->
                    <form method="POST" id="exportForm">
                        <input type="hidden" name="action" value="export">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <select class="form-select" name="export_format">
                                        <option value="txt">TXT格式</option>
                                        <option value="excel">Excel格式</option>
                                    </select>
                                    <button type="submit" class="btn btn-info">导出选中</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 分享页表格 -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>分享码</th>
                                        <th>卡类型</th>
                                        <th>关联账号</th>
                                        <th>状态</th>
                                        <th>激活时间</th>
                                        <th>到期时间</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($share_pages['data'] as $share): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_ids[]" value="<?php echo $share['id']; ?>" class="share-checkbox">
                                            </td>
                                            <td>
                                                <span class="share-code"><?php echo htmlspecialchars($share['share_code']); ?></span>
                                                <button type="button" class="btn btn-sm btn-outline-primary copy-btn ms-1" 
                                                        onclick="copyShareLink('<?php echo $share['share_code']; ?>')">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                                <br>
                                                <small class="text-muted">
                                                    <a href="../share.php?code=<?php echo $share['share_code']; ?>" target="_blank" class="text-decoration-none">
                                                        <i class="bi bi-box-arrow-up-right me-1"></i>预览
                                                    </a>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo getCardTypeName($share['card_type']); ?>
                                                </span>
                                                <br>
                                                <small class="text-muted"><?php echo $share['duration_days']; ?>天</small>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo htmlspecialchars($share['netflix_email']); ?>
                                                    <br>
                                                    <span class="badge bg-<?php echo $share['account_status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo $share['account_status']; ?>
                                                    </span>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($share['is_activated']): ?>
                                                    <span class="badge bg-success">已激活</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">待激活</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($share['activated_at']): ?>
                                                    <small><?php echo date('m-d H:i', strtotime($share['activated_at'])); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($share['expires_at']): ?>
                                                    <small class="<?php echo strtotime($share['expires_at']) < time() ? 'text-danger' : 'text-success'; ?>">
                                                        <?php echo date('m-d H:i', strtotime($share['expires_at'])); ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('m-d H:i', strtotime($share['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="deleteSharePage(<?php echo $share['id']; ?>)">
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
                    <?php if ($share_pages['pages'] > 1): ?>
                        <nav>
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= $share_pages['pages']; $i++): ?>
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
                    确定要删除这个分享页吗？此操作无法撤销。
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
    
    <!-- 显示生成的分享码模态框 -->
    <div class="modal fade" id="generatedCodesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">生成的分享码</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="generatedCodesList"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="copyAllCodes()">复制全部链接</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 全选功能
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.share-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
        
        // 复制分享链接
        function copyShareLink(shareCode) {
            const baseUrl = window.location.origin + window.location.pathname.replace('/admin/share-pages.php', '/share.php');
            const shareUrl = baseUrl + '?code=' + shareCode;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shareUrl).then(function() {
                    showToast('分享链接已复制');
                });
            } else {
                fallbackCopy(shareUrl);
            }
        }
        
        // 显示提示
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast show position-fixed top-0 start-50 translate-middle-x mt-3';
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="toast-body bg-success text-white rounded">
                    <i class="bi bi-check-circle me-2"></i>${message}
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        // 备用复制方法
        function fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.select();
            
            try {
                document.execCommand('copy');
                showToast('分享链接已复制');
            } catch (err) {
                showToast('复制失败，请手动复制');
            }
            
            document.body.removeChild(textArea);
        }
        
        // 删除分享页
        function deleteSharePage(id) {
            document.getElementById('deleteId').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // 显示生成的分享码
        function showGeneratedCodes() {
            <?php if (isset($_SESSION['last_generated_codes'])): ?>
                const codes = <?php echo json_encode($_SESSION['last_generated_codes']); ?>;
                const baseUrl = window.location.origin + window.location.pathname.replace('/admin/share-pages.php', '/share.php');
                let html = '<div class="list-group">';
                
                codes.forEach(function(code) {
                    const url = baseUrl + '?code=' + code;
                    html += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <code>${code}</code>
                                <br>
                                <small class="text-muted">${url}</small>
                            </div>
                            <button class="btn btn-sm btn-outline-primary" onclick="copyShareLink('${code}')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    `;
                });
                
                html += '</div>';
                document.getElementById('generatedCodesList').innerHTML = html;
                new bootstrap.Modal(document.getElementById('generatedCodesModal')).show();
            <?php endif; ?>
        }
        
        // 复制全部链接
        function copyAllCodes() {
            <?php if (isset($_SESSION['last_generated_codes'])): ?>
                const codes = <?php echo json_encode($_SESSION['last_generated_codes']); ?>;
                const baseUrl = window.location.origin + window.location.pathname.replace('/admin/share-pages.php', '/share.php');
                let allLinks = '';
                
                codes.forEach(function(code) {
                    allLinks += baseUrl + '?code=' + code + '\n';
                });
                
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(allLinks).then(function() {
                        showToast('全部链接已复制');
                    });
                } else {
                    fallbackCopy(allLinks);
                }
            <?php endif; ?>
        }
        
        // 验证导出操作
        document.getElementById('exportForm').addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('.share-checkbox:checked');
            
            if (selected.length === 0) {
                e.preventDefault();
                alert('请选择要导出的分享页');
                return;
            }
            
            if (!confirm(`确定要导出选中的 ${selected.length} 个分享页吗？`)) {
                e.preventDefault();
            }
        });
    </script>
    
    <?php
    // 清除已显示的生成码
    if (isset($_SESSION['last_generated_codes'])) {
        unset($_SESSION['last_generated_codes']);
    }
    ?>
</body>
</html>
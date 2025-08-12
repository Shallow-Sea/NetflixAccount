<?php
session_start();
require_once '../includes/functions.php';

requireLogin();

$page = $_GET['page'] ?? 1;
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';
$edit_announcement = null;

// 处理编辑操作
if ($action === 'edit' && isset($_GET['id'])) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_announcement = $stmt->fetch();
    
    if (!$edit_announcement) {
        $error = '公告不存在';
        $action = 'list';
    }
}

// 处理各种操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add') {
        $data = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'content_type' => $_POST['content_type'] ?? 'html',
            'is_popup' => isset($_POST['is_popup']),
            'popup_duration' => (int)($_POST['popup_duration'] ?? 5000),
            'is_active' => isset($_POST['is_active']),
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null
        ];
        
        if (empty($data['title']) || empty($data['content'])) {
            $error = '标题和内容不能为空';
        } else {
            $result = addAnnouncement($data);
            if ($result) {
                $success = '公告创建成功';
                $action = 'list';
            } else {
                $error = '创建失败';
            }
        }
    } elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'] ?? 0;
        $data = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'content_type' => $_POST['content_type'] ?? 'html',
            'is_popup' => isset($_POST['is_popup']),
            'popup_duration' => (int)($_POST['popup_duration'] ?? 5000),
            'is_active' => isset($_POST['is_active']),
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null
        ];
        
        if (empty($data['title']) || empty($data['content'])) {
            $error = '标题和内容不能为空';
        } else {
            $result = updateAnnouncement($id, $data);
            if ($result) {
                $success = '公告更新成功';
                $action = 'list';
            } else {
                $error = '更新失败';
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? 0;
        if ($id && deleteAnnouncement($id)) {
            $success = '删除成功';
        } else {
            $error = '删除失败';
        }
    } elseif ($_POST['action'] === 'toggle_status') {
        $id = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? 0;
        
        global $pdo;
        $stmt = $pdo->prepare("UPDATE announcements SET is_active = ? WHERE id = ?");
        if ($stmt->execute([$status, $id])) {
            $success = '状态更新成功';
        } else {
            $error = '状态更新失败';
        }
    }
}

// 获取公告列表
$filters = [
    'is_active' => $_GET['filter_active'] ?? '',
    'is_popup' => $_GET['filter_popup'] ?? ''
];

$announcements = getAnnouncements($page, 20, $filters);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>公告管理 - 奈飞账号管理系统</title>
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
        .content-preview {
            max-height: 100px;
            overflow: hidden;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 0.5rem;
            background: #f8f9fa;
        }
        .editor-tabs {
            border-bottom: 1px solid #dee2e6;
        }
        .editor-tabs .nav-link {
            border: none;
            color: #6c757d;
        }
        .editor-tabs .nav-link.active {
            background: #f8f9fa;
            color: #495057;
            border-bottom: 2px solid #0d6efd;
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
                <a class="nav-link active" href="announcements.php">
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
            <h1 class="h3">公告管理</h1>
            <a href="?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>创建公告
            </a>
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
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- 创建/编辑公告表单 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo $action === 'edit' ? '编辑公告' : '创建公告'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_announcement['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">公告标题 *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($edit_announcement['title'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">内容类型</label>
                            <ul class="nav nav-tabs editor-tabs" id="contentTypeTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo (!$edit_announcement || $edit_announcement['content_type'] === 'html') ? 'active' : ''; ?>" 
                                            id="html-tab" data-bs-toggle="tab" data-bs-target="#html-pane" type="button" role="tab">
                                        HTML编辑器
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo ($edit_announcement && $edit_announcement['content_type'] === 'markdown') ? 'active' : ''; ?>" 
                                            id="markdown-tab" data-bs-toggle="tab" data-bs-target="#markdown-pane" type="button" role="tab">
                                        Markdown编辑器
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content mt-3" id="contentTypeTabsContent">
                                <div class="tab-pane fade <?php echo (!$edit_announcement || $edit_announcement['content_type'] === 'html') ? 'show active' : ''; ?>" 
                                     id="html-pane" role="tabpanel">
                                    <textarea class="form-control" name="content_html" rows="8" 
                                              placeholder="输入HTML内容..."><?php echo htmlspecialchars($edit_announcement && $edit_announcement['content_type'] === 'html' ? $edit_announcement['content'] : ''); ?></textarea>
                                    <input type="hidden" name="content_type_html" value="html">
                                    <div class="form-text">
                                        支持HTML标签，如：&lt;h1&gt;标题&lt;/h1&gt;、&lt;p&gt;段落&lt;/p&gt;、&lt;strong&gt;粗体&lt;/strong&gt; 等
                                    </div>
                                </div>
                                <div class="tab-pane fade <?php echo ($edit_announcement && $edit_announcement['content_type'] === 'markdown') ? 'show active' : ''; ?>" 
                                     id="markdown-pane" role="tabpanel">
                                    <textarea class="form-control" name="content_markdown" rows="8" 
                                              placeholder="输入Markdown内容..."><?php echo htmlspecialchars($edit_announcement && $edit_announcement['content_type'] === 'markdown' ? $edit_announcement['content'] : ''); ?></textarea>
                                    <input type="hidden" name="content_type_markdown" value="markdown">
                                    <div class="form-text">
                                        支持Markdown语法，如：# 标题、**粗体**、*斜体*、[链接](url) 等
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="card border-light">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">弹窗设置</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="is_popup" name="is_popup" 
                                                   <?php echo ($edit_announcement && $edit_announcement['is_popup']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_popup">
                                                启用弹窗显示
                                            </label>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="popup_duration" class="form-label">弹窗显示时长（毫秒）</label>
                                            <input type="number" class="form-control" id="popup_duration" name="popup_duration" 
                                                   value="<?php echo $edit_announcement['popup_duration'] ?? 5000; ?>" min="1000" max="30000">
                                            <div class="form-text">1000-30000毫秒，0表示需手动关闭</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card border-light">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">显示控制</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                   <?php echo (!$edit_announcement || $edit_announcement['is_active']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_active">
                                                立即激活
                                            </label>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">开始显示时间</label>
                                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" 
                                                   value="<?php echo $edit_announcement && $edit_announcement['start_date'] ? date('Y-m-d\TH:i', strtotime($edit_announcement['start_date'])) : ''; ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">结束显示时间</label>
                                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" 
                                                   value="<?php echo $edit_announcement && $edit_announcement['end_date'] ? date('Y-m-d\TH:i', strtotime($edit_announcement['end_date'])) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i><?php echo $action === 'edit' ? '更新公告' : '创建公告'; ?>
                            </button>
                            <a href="?" class="btn btn-secondary">取消</a>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php else: ?>
            <!-- 公告列表 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">公告列表</h5>
                </div>
                <div class="card-body">
                    <!-- 筛选 -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select class="form-select" name="filter_active">
                                <option value="">全部状态</option>
                                <option value="1" <?php echo $filters['is_active'] === '1' ? 'selected' : ''; ?>>活跃</option>
                                <option value="0" <?php echo $filters['is_active'] === '0' ? 'selected' : ''; ?>>非活跃</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="filter_popup">
                                <option value="">全部类型</option>
                                <option value="1" <?php echo $filters['is_popup'] === '1' ? 'selected' : ''; ?>>弹窗公告</option>
                                <option value="0" <?php echo $filters['is_popup'] === '0' ? 'selected' : ''; ?>>普通公告</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">筛选</button>
                        </div>
                    </form>
                    
                    <!-- 公告表格 -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>标题</th>
                                    <th>类型</th>
                                    <th>内容预览</th>
                                    <th>状态</th>
                                    <th>显示时间</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($announcements['data'] as $announcement): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                                            <?php if ($announcement['is_popup']): ?>
                                                <br>
                                                <small class="text-info">
                                                    <i class="bi bi-window-stack me-1"></i>弹窗
                                                    (<?php echo $announcement['popup_duration']; ?>ms)
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $announcement['content_type'] === 'markdown' ? 'info' : 'secondary'; ?>">
                                                <?php echo strtoupper($announcement['content_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="content-preview">
                                                <?php 
                                                if ($announcement['content_type'] === 'markdown') {
                                                    echo parseMarkdown(mb_substr($announcement['content'], 0, 100) . '...');
                                                } else {
                                                    echo mb_substr(strip_tags($announcement['content']), 0, 100) . '...';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $announcement['is_active'] ? '0' : '1'; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-<?php echo $announcement['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <i class="bi bi-<?php echo $announcement['is_active'] ? 'check-circle' : 'pause-circle'; ?>"></i>
                                                    <?php echo $announcement['is_active'] ? '活跃' : '暂停'; ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <small>
                                                <?php if ($announcement['start_date']): ?>
                                                    开始: <?php echo date('m-d H:i', strtotime($announcement['start_date'])); ?><br>
                                                <?php endif; ?>
                                                <?php if ($announcement['end_date']): ?>
                                                    结束: <?php echo date('m-d H:i', strtotime($announcement['end_date'])); ?>
                                                <?php endif; ?>
                                                <?php if (!$announcement['start_date'] && !$announcement['end_date']): ?>
                                                    <span class="text-muted">永久显示</span>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small><?php echo date('m-d H:i', strtotime($announcement['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="?action=edit&id=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- 分页 -->
                    <?php if ($announcements['pages'] > 1): ?>
                        <nav>
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= $announcements['pages']; $i++): ?>
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
                    确定要删除这个公告吗？此操作无法撤销。
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
        // 删除公告
        function deleteAnnouncement(id) {
            document.getElementById('deleteId').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // 处理内容类型切换
        document.addEventListener('DOMContentLoaded', function() {
            const htmlTab = document.getElementById('html-tab');
            const markdownTab = document.getElementById('markdown-tab');
            
            htmlTab.addEventListener('click', function() {
                document.querySelector('input[name="content_type"]')?.remove();
                document.querySelector('input[name="content"]')?.remove();
            });
            
            markdownTab.addEventListener('click', function() {
                document.querySelector('input[name="content_type"]')?.remove();
                document.querySelector('input[name="content"]')?.remove();
            });
        });
        
        // 表单提交时处理内容
        document.querySelector('form').addEventListener('submit', function(e) {
            const activeTab = document.querySelector('.nav-tabs .nav-link.active').id;
            let content = '';
            let contentType = '';
            
            if (activeTab === 'html-tab') {
                content = document.querySelector('textarea[name="content_html"]').value;
                contentType = 'html';
            } else {
                content = document.querySelector('textarea[name="content_markdown"]').value;
                contentType = 'markdown';
            }
            
            // 创建隐藏字段
            const contentInput = document.createElement('input');
            contentInput.type = 'hidden';
            contentInput.name = 'content';
            contentInput.value = content;
            
            const typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = 'content_type';
            typeInput.value = contentType;
            
            this.appendChild(contentInput);
            this.appendChild(typeInput);
        });
    </script>
</body>
</html>
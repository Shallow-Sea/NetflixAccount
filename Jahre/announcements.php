<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

checkAdminAccess();

$error = '';
$success = '';

// 处理添加公告
if ($_POST['action'] ?? '' === 'add_announcement') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $content_type = sanitizeInput($_POST['content_type'] ?? 'html');
    $is_popup = (int)($_POST['is_popup'] ?? 0);
    $popup_duration = (int)($_POST['popup_duration'] ?? 5000);
    $priority = (int)($_POST['priority'] ?? 0);
    
    if (empty($title) || empty($content)) {
        $error = '请填写公告标题和内容';
    } else {
        if (addAnnouncement($title, $content, $content_type, $is_popup, $popup_duration)) {
            // 更新优先级
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE announcements SET priority = ? WHERE id = LAST_INSERT_ID()");
            $stmt->execute([$priority]);
            
            $success = '公告添加成功';
        } else {
            $error = '公告添加失败';
        }
    }
}

// 处理更新公告状态
if ($_POST['action'] ?? '' === 'toggle_status') {
    $announcement_id = (int)($_POST['announcement_id'] ?? 0);
    $is_active = (int)($_POST['is_active'] ?? 0);
    
    if ($announcement_id > 0) {
        $pdo = getConnection();
        $stmt = $pdo->prepare("UPDATE announcements SET is_active = ? WHERE id = ?");
        if ($stmt->execute([$is_active, $announcement_id])) {
            $success = '公告状态更新成功';
        } else {
            $error = '公告状态更新失败';
        }
    }
}

// 处理删除公告
if ($_POST['action'] ?? '' === 'delete_announcement') {
    $announcement_id = (int)($_POST['announcement_id'] ?? 0);
    
    if ($announcement_id > 0) {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        if ($stmt->execute([$announcement_id])) {
            $success = '公告删除成功';
        } else {
            $error = '公告删除失败';
        }
    }
}

// 获取公告列表
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM announcements ORDER BY priority DESC, created_at DESC");
$stmt->execute();
$announcements = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>公告管理 - 奈飞分享系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Markdown 编辑器 -->
    <link href="https://cdn.jsdelivr.net/npm/easymde@2.18.0/dist/easymde.min.css" rel="stylesheet">
    <!-- HTML 编辑器 -->
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
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
                    <h2><i class="bi bi-megaphone"></i> 公告管理</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                        <i class="bi bi-plus-circle"></i> 添加公告
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

                <!-- 公告统计 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center border-success">
                            <div class="card-body">
                                <h5 class="card-title text-success">活跃公告</h5>
                                <h3 class="card-text">
                                    <?php echo count(array_filter($announcements, fn($a) => $a['is_active'])); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-info">
                            <div class="card-body">
                                <h5 class="card-title text-info">弹窗公告</h5>
                                <h3 class="card-text">
                                    <?php echo count(array_filter($announcements, fn($a) => $a['is_popup'] && $a['is_active'])); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-warning">
                            <div class="card-body">
                                <h5 class="card-title text-warning">草稿公告</h5>
                                <h3 class="card-text">
                                    <?php echo count(array_filter($announcements, fn($a) => !$a['is_active'])); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-primary">
                            <div class="card-body">
                                <h5 class="card-title text-primary">总公告数</h5>
                                <h3 class="card-text"><?php echo count($announcements); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 公告列表 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">公告列表</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>标题</th>
                                        <th>内容类型</th>
                                        <th>优先级</th>
                                        <th>是否弹窗</th>
                                        <th>弹窗时长</th>
                                        <th>状态</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($announcements as $announcement): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $announcement['content_type'] === 'html' ? 'warning' : 'info'; ?>">
                                                <?php echo strtoupper($announcement['content_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $announcement['priority']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($announcement['is_popup']): ?>
                                                <i class="bi bi-check-circle text-success"></i> 是
                                            <?php else: ?>
                                                <i class="bi bi-x-circle text-muted"></i> 否
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($announcement['is_popup']): ?>
                                                <?php echo $announcement['popup_duration']; ?>ms
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($announcement['is_active']): ?>
                                                <span class="badge bg-success">活跃</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">草稿</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo $announcement['created_at']; ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-info" data-bs-toggle="modal" 
                                                        data-bs-target="#previewModal" onclick="previewAnnouncement(<?php echo htmlspecialchars(json_encode($announcement), ENT_QUOTES); ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                
                                                <button class="btn btn-outline-<?php echo $announcement['is_active'] ? 'warning' : 'success'; ?>" 
                                                        onclick="toggleStatus(<?php echo $announcement['id']; ?>, <?php echo $announcement['is_active'] ? 'false' : 'true'; ?>)">
                                                    <i class="bi bi-<?php echo $announcement['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                </button>
                                                
                                                <button class="btn btn-outline-danger" onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)">
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
            </div>
        </div>
    </div>

    <!-- 添加公告模态框 -->
    <div class="modal fade" id="addAnnouncementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">添加公告</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="announcementForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_announcement">
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="title" class="form-label">公告标题</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-4">
                                <label for="priority" class="form-label">优先级</label>
                                <input type="number" class="form-control" id="priority" name="priority" value="0" min="0" max="100">
                                <div class="form-text">数字越大优先级越高</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content_type" class="form-label">内容类型</label>
                            <select class="form-control" id="content_type" name="content_type" onchange="switchEditor()">
                                <option value="html">HTML</option>
                                <option value="markdown">Markdown</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">公告内容</label>
                            <div id="html-editor" style="height: 300px;"></div>
                            <textarea class="form-control d-none" id="markdown-editor" name="content" rows="15"></textarea>
                            <textarea id="content" name="content" class="d-none"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="hidden" name="is_popup" value="0">
                                    <input type="checkbox" class="form-check-input" id="is_popup" name="is_popup" value="1" onchange="togglePopupOptions()">
                                    <label class="form-check-label" for="is_popup">
                                        弹窗显示
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="popup_duration" class="form-label">弹窗时长 (毫秒)</label>
                                <input type="number" class="form-control" id="popup_duration" name="popup_duration" value="5000" min="1000" max="30000" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">添加公告</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 预览模态框 -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewTitle">公告预览</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="previewContent">
                    <!-- 动态内容 -->
                </div>
            </div>
        </div>
    </div>

    <!-- 隐藏表单 -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="announcement_id" id="status_announcement_id">
        <input type="hidden" name="is_active" id="status_is_active" value="0">
    </form>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_announcement">
        <input type="hidden" name="announcement_id" id="delete_announcement_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Markdown 编辑器 -->
    <script src="https://cdn.jsdelivr.net/npm/easymde@2.18.0/dist/easymde.min.js"></script>
    <!-- HTML 编辑器 -->
    <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
    <!-- Markdown 解析器 -->
    <script src="https://cdn.jsdelivr.net/npm/marked@4.0.0/marked.min.js"></script>
    
    <script>
        let quillEditor;
        let markdownEditor;
        
        // 初始化编辑器
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化HTML编辑器
            quillEditor = new Quill('#html-editor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'align': [] }],
                        ['blockquote', 'code-block'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link', 'image'],
                        ['clean']
                    ]
                }
            });
            
            // 初始化Markdown编辑器
            markdownEditor = new EasyMDE({
                element: document.getElementById('markdown-editor'),
                spellChecker: false,
                status: false,
                toolbar: [
                    'bold', 'italic', 'heading', '|',
                    'quote', 'unordered-list', 'ordered-list', '|',
                    'link', 'image', 'table', '|',
                    'preview', 'side-by-side', 'fullscreen', '|',
                    'guide'
                ]
            });
        });
        
        function switchEditor() {
            const contentType = document.getElementById('content_type').value;
            const htmlEditor = document.getElementById('html-editor');
            const markdownContainer = document.querySelector('.CodeMirror');
            
            if (contentType === 'html') {
                htmlEditor.style.display = 'block';
                if (markdownContainer) markdownContainer.style.display = 'none';
            } else {
                htmlEditor.style.display = 'none';
                if (markdownContainer) markdownContainer.style.display = 'block';
            }
        }
        
        function togglePopupOptions() {
            const isPopup = document.getElementById('is_popup').checked;
            document.getElementById('popup_duration').disabled = !isPopup;
        }
        
        // 表单提交处理
        document.getElementById('announcementForm').addEventListener('submit', function(e) {
            const contentType = document.getElementById('content_type').value;
            let content = '';
            
            if (contentType === 'html') {
                content = quillEditor.root.innerHTML;
            } else {
                content = markdownEditor.value();
            }
            
            document.getElementById('content').value = content;
        });
        
        function previewAnnouncement(announcement) {
            document.getElementById('previewTitle').textContent = announcement.title;
            const previewContent = document.getElementById('previewContent');
            
            if (announcement.content_type === 'markdown') {
                previewContent.innerHTML = marked.parse(announcement.content);
            } else {
                previewContent.innerHTML = announcement.content;
            }
        }
        
        function toggleStatus(announcementId, isActive) {
            const action = isActive === 'true' ? '启用' : '暂停';
            if (confirm(`确定要${action}此公告吗？`)) {
                document.getElementById('status_announcement_id').value = announcementId;
                const statusField = document.getElementById('status_is_active');
                statusField.name = 'is_active';
                statusField.value = isActive === 'true' ? '1' : '0';
                document.getElementById('statusForm').submit();
            }
        }
        
        function deleteAnnouncement(announcementId) {
            if (confirm('确定要删除此公告吗？此操作不可撤销！')) {
                document.getElementById('delete_announcement_id').value = announcementId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
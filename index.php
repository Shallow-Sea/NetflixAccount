<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// 检查是否已登录
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 获取用户信息
if (isset($_SESSION['admin_id'])) {
    $user_type = 'admin';
    $user_info = getAdminById($_SESSION['admin_id']);
} else {
    $user_type = 'user';
    $user_info = getUserById($_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>奈飞账号分享系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-tv"></i> 奈飞分享系统
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($user_type == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/accounts.php">
                            <i class="bi bi-person-lines-fill"></i> 账号管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/users.php">
                            <i class="bi bi-people"></i> 用户管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/share-pages.php">
                            <i class="bi bi-share"></i> 分享页管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/announcements.php">
                            <i class="bi bi-megaphone"></i> 公告管理
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="share-pages.php">
                            <i class="bi bi-link-45deg"></i> 我的分享页
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo htmlspecialchars($user_info['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">个人资料</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">退出登录</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 主内容区 -->
    <div class="container-fluid mt-4">
        <div class="row">
            <?php if ($user_type == 'admin'): ?>
            <!-- 管理员仪表板 -->
            <div class="col-12">
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            活跃账号</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo getActiveAccountsCount(); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-tv text-primary fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            注册用户</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo getTotalUsersCount(); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people text-success fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            活跃分享页</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo getActiveSharePagesCount(); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-share text-info fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            今日激活</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo getTodayActivationsCount(); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-check-circle text-warning fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 最新激活记录 -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">最新激活记录</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>分享码</th>
                                        <th>用户</th>
                                        <th>卡类型</th>
                                        <th>激活时间</th>
                                        <th>到期时间</th>
                                        <th>IP地址</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recent_activations = getRecentActivations(10);
                                    foreach ($recent_activations as $activation):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activation['share_code']); ?></td>
                                        <td><?php echo htmlspecialchars($activation['username'] ?? '未知用户'); ?></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo getCardTypeName($activation['card_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $activation['activated_at']; ?></td>
                                        <td><?php echo $activation['expires_at']; ?></td>
                                        <td><?php echo htmlspecialchars($activation['activation_ip']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- 用户仪表板 -->
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    欢迎使用奈飞账号分享系统！请点击"我的分享页"查看您的分享链接。
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 公告弹窗 -->
    <?php
    $active_announcements = getActiveAnnouncements();
    foreach ($active_announcements as $announcement):
        if ($announcement['is_popup']):
    ?>
    <div class="modal fade" id="announcement-<?php echo $announcement['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php
                    if ($announcement['content_type'] == 'markdown') {
                        echo parseMarkdown($announcement['content']);
                    } else {
                        echo $announcement['content'];
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php 
        endif;
    endforeach; 
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <!-- 显示公告弹窗 -->
    <script>
    <?php foreach ($active_announcements as $announcement): ?>
        <?php if ($announcement['is_popup']): ?>
        setTimeout(function() {
            var modal = new bootstrap.Modal(document.getElementById('announcement-<?php echo $announcement['id']; ?>'));
            modal.show();
        }, 1000);
        
        // 自动关闭弹窗
        setTimeout(function() {
            var modal = bootstrap.Modal.getInstance(document.getElementById('announcement-<?php echo $announcement['id']; ?>'));
            if (modal) modal.hide();
        }, <?php echo $announcement['popup_duration'] + 1000; ?>);
        <?php endif; ?>
    <?php endforeach; ?>
    </script>
</body>
</html>
<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// 如果访问根路径且管理员已登录，显示后台链接而不是自动跳转
// 这样可以让管理员选择是查看前台还是进入后台

// 获取弹窗公告（只获取弹窗类型的公告在首页显示）
$popup_announcements = [];
$all_announcements = getActiveAnnouncements();
foreach ($all_announcements as $announcement) {
    if ($announcement['is_popup']) {
        $popup_announcements[] = $announcement;
    }
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
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .hero-section {
            padding: 100px 0;
            text-align: center;
            color: white;
        }
        .netflix-logo {
            font-size: 4rem;
            font-weight: bold;
            color: #e50914;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            margin-bottom: 30px;
        }
        .hero-title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        .feature-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        .btn-custom {
            background: linear-gradient(135deg, #e50914 0%, #b2070f 100%);
            border: none;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(229, 9, 20, 0.3);
        }
        .stats-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 60px 0;
            margin: 60px 0;
        }
        .stat-item {
            text-align: center;
            color: white;
        }
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: #e50914;
        }
        .announcement-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: rgba(0,0,0,0.3);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-tv"></i> 奈飞分享系统
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">功能特色</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Jahre/">
                            <i class="bi bi-gear"></i> 管理后台
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 英雄区域 -->
    <section class="hero-section">
        <div class="container">
            <div class="netflix-logo">NETFLIX</div>
            <h1 class="hero-title">账号分享系统</h1>
            <p class="hero-subtitle">
                高质量的Netflix账号分享服务<br>
                4K超清画质 · 多设备同步 · 全球内容库
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#features" class="btn btn-custom text-white">
                    <i class="bi bi-info-circle me-2"></i>
                    了解更多
                </a>
                <a href="Jahre/" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-gear me-2"></i>
                    管理后台
                </a>
            </div>
        </div>
    </section>

    <!-- 统计数据 -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo getActiveAccountsCount(); ?></div>
                        <div>活跃账号</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo getTotalUsersCount(); ?></div>
                        <div>注册用户</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo getActiveSharePagesCount(); ?></div>
                        <div>活跃分享</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo getTodayActivationsCount(); ?></div>
                        <div>今日激活</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 功能特色 -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="text-center mb-3">
                            <i class="bi bi-tv text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="text-center mb-3">高清画质</h4>
                        <p class="text-center text-muted">
                            支持4K超高清画质，HDR技术，为您提供影院级的观影体验
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="text-center mb-3">
                            <i class="bi bi-devices text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="text-center mb-3">多设备同步</h4>
                        <p class="text-center text-muted">
                            支持手机、电脑、电视等多种设备，随时随地享受精彩内容
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="text-center mb-3">
                            <i class="bi bi-globe text-info" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="text-center mb-3">全球内容</h4>
                        <p class="text-center text-muted">
                            海量国际影视资源，热门剧集电影，满足不同观影需求
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="feature-card">
                        <h5><i class="bi bi-shield-check text-success me-2"></i>安全可靠</h5>
                        <p class="text-muted mb-0">采用先进的加密技术，保护您的账号安全，专业团队7×24小时监控维护</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="feature-card">
                        <h5><i class="bi bi-headset text-primary me-2"></i>客服支持</h5>
                        <p class="text-muted mb-0">专业的客服团队随时为您解答疑问，确保您的使用体验顺畅无忧</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- 底部 -->
    <footer class="py-4" style="background: rgba(0,0,0,0.3);">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-white mb-0">
                        © 2024 奈飞账号分享系统. 保留所有权利.
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="Jahre/" class="text-white-50 text-decoration-none">管理后台</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- 弹窗公告 -->
    <?php foreach ($popup_announcements as $announcement): ?>
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
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- 弹窗公告脚本 -->
    <script>
        <?php foreach ($popup_announcements as $announcement): ?>
            setTimeout(function() {
                var modal = new bootstrap.Modal(document.getElementById('announcement-<?php echo $announcement['id']; ?>'));
                modal.show();
            }, 1000);
            
            setTimeout(function() {
                var modal = bootstrap.Modal.getInstance(document.getElementById('announcement-<?php echo $announcement['id']; ?>'));
                if (modal) modal.hide();
            }, <?php echo $announcement['popup_duration'] + 1000; ?>);
        <?php endforeach; ?>
    </script>
</body>
</html>
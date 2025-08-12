<?php
session_start();
require_once '../includes/functions.php';

requireLogin();

$stats = getDashboardStats();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理仪表板 - 奈飞账号管理系统</title>
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
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .brand-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        .brand-title:hover {
            color: white;
        }
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
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
        
        <div class="user-info mx-3 text-white">
            <div class="d-flex align-items-center">
                <i class="bi bi-person-circle me-2" style="font-size: 2rem;"></i>
                <div>
                    <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                    <small class="opacity-75">管理员</small>
                </div>
            </div>
        </div>
        
        <ul class="nav flex-column px-3">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
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
            <h1 class="h3">管理仪表板</h1>
            <div class="text-muted">
                <i class="bi bi-calendar me-1"></i>
                <?php echo date('Y年m月d日'); ?>
            </div>
        </div>
        
        <!-- 统计卡片 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card text-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle text-muted">奈飞账号总数</h6>
                                <h2 class="card-title"><?php echo $stats['accounts']['total']; ?></h2>
                                <small class="text-success">
                                    活跃: <?php echo $stats['accounts']['active']; ?>
                                </small>
                            </div>
                            <i class="bi bi-tv stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card text-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle text-muted">分享页总数</h6>
                                <h2 class="card-title"><?php echo $stats['shares']['total']; ?></h2>
                                <small class="text-primary">
                                    已激活: <?php echo $stats['shares']['activated']; ?>
                                </small>
                            </div>
                            <i class="bi bi-share stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card text-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle text-muted">活跃公告</h6>
                                <h2 class="card-title"><?php echo $stats['announcements']['active']; ?></h2>
                                <small class="text-muted">正在显示</small>
                            </div>
                            <i class="bi bi-megaphone stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card text-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle text-muted">今日激活</h6>
                                <h2 class="card-title"><?php echo $stats['today_activations']; ?></h2>
                                <small class="text-muted">个分享页</small>
                            </div>
                            <i class="bi bi-lightning stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 快速操作 -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-lightning-charge me-2"></i>
                            快速操作
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="netflix-accounts.php?action=add" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    添加奈飞账号
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="share-pages.php?action=add" class="btn btn-success btn-lg w-100">
                                    <i class="bi bi-share-fill me-2"></i>
                                    创建分享页
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="share-pages.php?action=batch" class="btn btn-info btn-lg w-100">
                                    <i class="bi bi-collection me-2"></i>
                                    批量生成分享页
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="announcements.php?action=add" class="btn btn-warning btn-lg w-100">
                                    <i class="bi bi-megaphone-fill me-2"></i>
                                    发布公告
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 账号状态分布 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-pie-chart me-2"></i>
                            账号状态分布
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-success">
                                    <i class="bi bi-circle-fill me-2"></i>活跃账号
                                </span>
                                <span class="fw-bold"><?php echo $stats['accounts']['active']; ?></span>
                            </div>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-success" style="width: <?php echo $stats['accounts']['total'] ? round($stats['accounts']['active'] / $stats['accounts']['total'] * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary">
                                    <i class="bi bi-circle-fill me-2"></i>非活跃账号
                                </span>
                                <span class="fw-bold"><?php echo $stats['accounts']['inactive']; ?></span>
                            </div>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-secondary" style="width: <?php echo $stats['accounts']['total'] ? round($stats['accounts']['inactive'] / $stats['accounts']['total'] * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-danger">
                                    <i class="bi bi-circle-fill me-2"></i>暂停账号
                                </span>
                                <span class="fw-bold"><?php echo $stats['accounts']['suspended']; ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-danger" style="width: <?php echo $stats['accounts']['total'] ? round($stats['accounts']['suspended'] / $stats['accounts']['total'] * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-graph-up me-2"></i>
                            分享页状态
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-primary">
                                    <i class="bi bi-circle-fill me-2"></i>已激活
                                </span>
                                <span class="fw-bold"><?php echo $stats['shares']['activated']; ?></span>
                            </div>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-primary" style="width: <?php echo $stats['shares']['total'] ? round($stats['shares']['activated'] / $stats['shares']['total'] * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-warning">
                                    <i class="bi bi-circle-fill me-2"></i>待激活
                                </span>
                                <span class="fw-bold"><?php echo $stats['shares']['pending']; ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: <?php echo $stats['shares']['total'] ? round($stats['shares']['pending'] / $stats['shares']['total'] * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 自动刷新统计数据
        setInterval(function() {
            location.reload();
        }, 60000); // 每分钟刷新一次
    </script>
</body>
</html>
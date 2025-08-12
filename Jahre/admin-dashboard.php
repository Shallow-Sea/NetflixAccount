<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查管理员权限
checkAdminAccess();

// 获取管理员信息
$admin_info = getAdminById($_SESSION['admin_id']);

// 后台不显示弹窗公告
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员仪表板 - 奈飞分享系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- 导航栏 -->
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
                        <a class="nav-link active" href="admin-dashboard.php">
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
                        <a class="nav-link" href="../index.php">
                            <i class="bi bi-house"></i> 前台首页
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo htmlspecialchars($admin_info['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">管理员设置</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">退出登录</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 主内容区 -->
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- 管理员仪表板 -->
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="bi bi-speedometer2"></i> 管理员仪表板
                    </h2>
                    <div>
                        <a href="../index.php" class="btn btn-outline-primary">
                            <i class="bi bi-house"></i> 前台首页
                        </a>
                    </div>
                </div>

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
                                        <th>卡类型</th>
                                        <th>激活时间</th>
                                        <th>到期时间</th>
                                        <th>IP地址</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recent_activations = getRecentActivations(10);
                                    foreach ($recent_activations as $activation):
                                    ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($activation['share_code']); ?></code></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo getCardTypeName($activation['card_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $activation['activated_at']; ?></td>
                                        <td><?php echo $activation['expires_at']; ?></td>
                                        <td><?php echo htmlspecialchars($activation['activation_ip']); ?></td>
                                        <td>
                                            <a href="../share.php?code=<?php echo $activation['share_code']; ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> 查看
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 快捷操作 -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">快捷操作</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <a href="accounts.php" class="btn btn-primary btn-block">
                                            <i class="bi bi-plus-circle"></i> 添加Netflix账号
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="share-pages.php" class="btn btn-success btn-block">
                                            <i class="bi bi-share"></i> 创建分享页
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="announcements.php" class="btn btn-info btn-block">
                                            <i class="bi bi-megaphone"></i> 发布公告
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="users.php" class="btn btn-warning btn-block">
                                            <i class="bi bi-people"></i> 用户管理
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">系统状态</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-server text-success me-2"></i>
                                    <span>系统运行正常</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-database text-success me-2"></i>
                                    <span>数据库连接正常</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-shield-check text-success me-2"></i>
                                    <span>安全状态良好</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-clock text-info me-2"></i>
                                    <span>最后更新: <?php echo date('Y-m-d H:i:s'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
</body>
</html>
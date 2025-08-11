<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 获取当前用户的分享页
if (isAdmin()) {
    // 管理员可以查看所有分享页
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT sp.*, na.email as netflix_email, na.subscription_type,
               u.username
        FROM share_pages sp 
        LEFT JOIN netflix_accounts na ON sp.netflix_account_id = na.id
        LEFT JOIN users u ON sp.user_id = u.id
        ORDER BY sp.created_at DESC
    ");
    $stmt->execute();
    $share_pages = $stmt->fetchAll();
} else {
    // 普通用户只能查看自己的分享页
    $share_pages = getUserSharePages($_SESSION['user_id']);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的分享页 - 奈飞分享系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-tv"></i> 奈飞分享系统
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-house"></i> 返回首页
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2>
                    <i class="bi bi-link-45deg"></i> 
                    <?php echo isAdmin() ? '所有分享页' : '我的分享页'; ?>
                </h2>

                <?php if (empty($share_pages)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <?php echo isAdmin() ? '暂无分享页' : '您还没有任何分享页。请联系管理员获取分享链接。'; ?>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($share_pages as $page): ?>
                            <?php
                            $is_active = $page['is_activated'] && $page['expires_at'] && strtotime($page['expires_at']) > time();
                            $is_expired = $page['is_activated'] && $page['expires_at'] && strtotime($page['expires_at']) <= time();
                            $share_url = generateShareUrl($page['share_code']);
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary">
                                            <?php echo getCardTypeName($page['card_type']); ?>
                                        </span>
                                        
                                        <?php if (!$page['is_activated']): ?>
                                            <span class="badge bg-warning">未激活</span>
                                        <?php elseif ($is_expired): ?>
                                            <span class="badge bg-danger">已过期</span>
                                        <?php elseif ($is_active): ?>
                                            <span class="badge bg-success">使用中</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="bi bi-tv"></i>
                                            Netflix <?php echo ucfirst($page['subscription_type']); ?>
                                        </h6>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">邮箱:</small><br>
                                            <code><?php echo htmlspecialchars($page['netflix_email']); ?></code>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">分享码:</small><br>
                                            <code><?php echo htmlspecialchars($page['share_code']); ?></code>
                                        </div>
                                        
                                        <?php if (isAdmin() && $page['username']): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">用户:</small><br>
                                            <span><?php echo htmlspecialchars($page['username']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="mb-2">
                                            <small class="text-muted">创建时间:</small><br>
                                            <span><?php echo $page['created_at']; ?></span>
                                        </div>
                                        
                                        <?php if ($page['activated_at']): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">激活时间:</small><br>
                                            <span><?php echo $page['activated_at']; ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($page['expires_at']): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <?php echo $is_expired ? '已于以下时间过期:' : '到期时间:'; ?>
                                            </small><br>
                                            <span class="<?php echo $is_expired ? 'text-danger' : ($is_active ? 'text-success' : ''); ?>">
                                                <?php echo $page['expires_at']; ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <div class="d-grid gap-2">
                                            <a href="<?php echo $share_url; ?>" target="_blank" class="btn btn-primary">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                                打开分享页
                                            </a>
                                            
                                            <div class="btn-group">
                                                <button class="btn btn-outline-secondary btn-sm" 
                                                        onclick="copyToClipboard('<?php echo $share_url; ?>')">
                                                    <i class="bi bi-clipboard"></i> 复制链接
                                                </button>
                                                
                                                <?php if ($is_active): ?>
                                                <a href="https://www.netflix.com/login" target="_blank" 
                                                   class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-tv"></i> 前往Netflix
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- 使用说明 -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-lightbulb"></i> 使用说明</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="bi bi-1-circle text-primary"></i> 激活分享页</h6>
                                    <p class="small text-muted">点击"打开分享页"按钮，在新页面中点击"立即激活"来启用您的Netflix账号。</p>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="bi bi-2-circle text-success"></i> 使用Netflix</h6>
                                    <p class="small text-muted">激活后，使用显示的邮箱和密码登录Netflix，建议创建独立的用户配置文件。</p>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="bi bi-3-circle text-warning"></i> 注意事项</h6>
                                    <p class="small text-muted">请勿修改账号密码或个人信息，到期前请及时续费或申请新的分享页。</p>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="bi bi-4-circle text-info"></i> 技术支持</h6>
                                    <p class="small text-muted">如遇任何问题，请及时联系管理员获取技术支持。</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$share_code = $_GET['code'] ?? '';
$error = '';
$success = '';

if (empty($share_code)) {
    header('Location: login.php');
    exit;
}

// 获取分享页信息
$share_page = getSharePageByCode($share_code);

// 获取活跃公告
$active_announcements = getActiveAnnouncements();

if (!$share_page) {
    $error = '分享页不存在或已失效';
} else {
    // 检查是否已激活
    if ($share_page['is_activated']) {
        // 检查是否过期
        if ($share_page['expires_at'] && strtotime($share_page['expires_at']) < time()) {
            $error = '此分享页已过期';
        }
    }
    
    // 处理激活请求
    if ($_POST['action'] ?? '' === 'activate' && !$share_page['is_activated']) {
        if (activateSharePage($share_code)) { // 移除用户ID参数
            // 重新获取分享页信息
            $share_page = getSharePageByCode($share_code);
            $success = '激活成功！账号信息已显示在下方。';
        } else {
            $error = '激活失败，请稍后重试';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>奈飞账号分享</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .share-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
        }
        .netflix-logo {
            color: #e50914;
            font-size: 2.5rem;
            font-weight: bold;
        }
        .activate-btn {
            background: linear-gradient(135deg, #e50914 0%, #b2070f 100%);
            border: none;
            border-radius: 10px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .card-type-badge {
            font-size: 1.2rem;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
        }
        .account-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        .copy-button {
            border: none;
            background: #007bff;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
            font-size: 0.8rem;
        }
        .countdown {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e50914;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .feature-list li:last-child {
            border-bottom: none;
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="container share-container">
        <?php if ($error): ?>
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
                    <h3 class="mt-3 text-danger">出错了</h3>
                    <p class="text-muted"><?php echo $error; ?></p>
                    <a href="login.php" class="btn btn-primary">返回首页</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <!-- Netflix Logo -->
                    <div class="netflix-logo mb-3">NETFLIX</div>
                    
                    <!-- 卡类型 -->
                    <div class="mb-4">
                        <span class="badge bg-primary card-type-badge">
                            <?php echo getCardTypeName($share_page['card_type']); ?>
                        </span>
                    </div>
                    
                    <?php if ($share_page['is_activated']): ?>
                        <!-- 已激活状态 -->
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="account-info">
                            <h4 class="text-success mb-3">
                                <i class="bi bi-check-circle-fill"></i> 账号已激活
                            </h4>
                            
                            <div class="row mb-3">
                                <div class="col-sm-4 fw-bold">邮箱账号：</div>
                                <div class="col-sm-8">
                                    <code id="email"><?php echo htmlspecialchars($share_page['netflix_email']); ?></code>
                                    <button class="copy-button ms-2" onclick="copyToClipboard('email')">
                                        <i class="bi bi-clipboard"></i> 复制
                                    </button>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-sm-4 fw-bold">账号密码：</div>
                                <div class="col-sm-8">
                                    <code id="password"><?php echo htmlspecialchars($share_page['netflix_password']); ?></code>
                                    <button class="copy-button ms-2" onclick="copyToClipboard('password')">
                                        <i class="bi bi-clipboard"></i> 复制
                                    </button>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-sm-4 fw-bold">套餐类型：</div>
                                <div class="col-sm-8">
                                    <span class="badge bg-info"><?php echo ucfirst($share_page['subscription_type']); ?></span>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-sm-4 fw-bold">激活时间：</div>
                                <div class="col-sm-8"><?php echo $share_page['activated_at']; ?></div>
                            </div>
                            
                            <div class="row">
                                <div class="col-sm-4 fw-bold">到期时间：</div>
                                <div class="col-sm-8">
                                    <span class="countdown" id="countdown" data-expires="<?php echo $share_page['expires_at']; ?>">
                                        <?php echo $share_page['expires_at']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 系统公告 -->
                        <?php if (!empty($active_announcements)): ?>
                        <div class="mt-4">
                            <h5><i class="bi bi-megaphone text-info"></i> 系统公告</h5>
                            <?php foreach ($active_announcements as $announcement): ?>
                                <?php if (!$announcement['is_popup']): ?>
                                <div class="alert alert-info mb-2">
                                    <h6 class="mb-2"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                    <div>
                                        <?php
                                        if ($announcement['content_type'] == 'markdown') {
                                            echo parseMarkdown($announcement['content']);
                                        } else {
                                            echo $announcement['content'];
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- 使用说明 -->
                        <div class="mt-4">
                            <h5><i class="bi bi-lightbulb"></i> 使用说明</h5>
                            <ul class="feature-list text-start">
                                <li><i class="bi bi-check text-success me-2"></i> 请在到期前充分使用此账号</li>
                                <li><i class="bi bi-check text-success me-2"></i> 请勿修改账号密码或个人信息</li>
                                <li><i class="bi bi-check text-success me-2"></i> 建议创建独立的用户配置文件</li>
                                <li><i class="bi bi-check text-success me-2"></i> 如遇问题请点击下方售后按钮联系客服</li>
                            </ul>
                        </div>
                        
                        <!-- 售后服务按钮 -->
                        <div class="mt-4 text-center">
                            <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#customerServiceModal">
                                <i class="bi bi-headset me-2"></i>
                                售后服务
                            </button>
                        </div>
                        
                        <!-- Netflix登录链接 -->
                        <div class="mt-4">
                            <a href="https://www.netflix.com/login" target="_blank" class="btn btn-danger btn-lg">
                                <i class="bi bi-box-arrow-up-right me-2"></i>
                                前往 Netflix 登录
                            </a>
                        </div>
                        
                    <?php else: ?>
                        <!-- 未激活状态 -->
                        <div class="mb-4">
                            <h4 class="text-primary">
                                <i class="bi bi-tv"></i> 奈飞高级账号分享
                            </h4>
                            <p class="text-muted">点击下方按钮激活您的 <?php echo getCardTypeName($share_page['card_type']); ?></p>
                        </div>
                        
                        <!-- 套餐特性 -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <ul class="feature-list text-start">
                                    <li><i class="bi bi-check text-success me-2"></i> 4K超高清画质</li>
                                    <li><i class="bi bi-check text-success me-2"></i> 多设备同时观看</li>
                                    <li><i class="bi bi-check text-success me-2"></i> 支持下载离线观看</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="feature-list text-start">
                                    <li><i class="bi bi-check text-success me-2"></i> 全球内容库</li>
                                    <li><i class="bi bi-check text-success me-2"></i> 无广告观看体验</li>
                                    <li><i class="bi bi-check text-success me-2"></i> 支持多语言字幕</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- 激活按钮 -->
                        <form method="POST">
                            <input type="hidden" name="action" value="activate">
                            <button type="submit" class="btn btn-danger activate-btn pulse" onclick="return confirm('确定要激活此分享页吗？激活后将无法撤销。')">
                                <i class="bi bi-play-circle-fill me-2"></i>
                                立即激活
                            </button>
                        </form>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                激活后账号信息将显示，有效期 <?php echo $share_page['duration_days']; ?> 天
                            </small>
                        </div>
                        
                        <!-- 系统公告 -->
                        <?php if (!empty($active_announcements)): ?>
                        <div class="mt-4">
                            <h5><i class="bi bi-megaphone text-info"></i> 系统公告</h5>
                            <?php foreach ($active_announcements as $announcement): ?>
                                <?php if (!$announcement['is_popup']): ?>
                                <div class="alert alert-info mb-2">
                                    <h6 class="mb-2"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                    <div>
                                        <?php
                                        if ($announcement['content_type'] == 'markdown') {
                                            echo parseMarkdown($announcement['content']);
                                        } else {
                                            echo $announcement['content'];
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- 售后服务按钮 -->
                        <div class="mt-4 text-center">
                            <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#customerServiceModal">
                                <i class="bi bi-headset me-2"></i>
                                售后服务
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 分享码信息 -->
            <div class="card mt-3">
                <div class="card-body text-center">
                    <small class="text-muted">
                        分享码: <code><?php echo htmlspecialchars($share_code); ?></code>
                        <br>
                        创建时间: <?php echo $share_page['created_at']; ?>
                    </small>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 售后服务弹窗 -->
    <div class="modal fade" id="customerServiceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-headset me-2"></i>
                        售后服务
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-4">
                        <i class="bi bi-wechat text-success" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h4 class="mb-3">售后微信</h4>
                    
                    <div class="card bg-light p-3 mb-4">
                        <h3 class="text-primary mb-0" id="wechatId">CatCar88</h3>
                    </div>
                    
                    <p class="text-muted mb-4">
                        <i class="bi bi-info-circle me-1"></i>
                        如有任何问题，请添加微信联系客服
                    </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-primary me-2" onclick="copyWechatId()">
                        <i class="bi bi-clipboard me-1"></i>
                        复制微信号
                    </button>
                    <button type="button" class="btn btn-success" onclick="openWechat()">
                        <i class="bi bi-wechat me-1"></i>
                        跳转微信
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 复制到剪贴板
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast('复制成功');
                });
            } else {
                // 兼容旧浏览器
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast('复制成功');
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
        
        // 倒计时功能
        function updateCountdown() {
            const countdownElement = document.getElementById('countdown');
            if (!countdownElement) return;
            
            const expiresAt = countdownElement.getAttribute('data-expires');
            const expireTime = new Date(expiresAt).getTime();
            const now = new Date().getTime();
            const timeLeft = expireTime - now;
            
            if (timeLeft > 0) {
                const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                
                countdownElement.innerHTML = `剩余 ${days}天 ${hours}时 ${minutes}分 ${seconds}秒`;
                
                if (timeLeft < 24 * 60 * 60 * 1000) { // 少于24小时
                    countdownElement.className = 'countdown text-danger';
                } else if (timeLeft < 7 * 24 * 60 * 60 * 1000) { // 少于7天
                    countdownElement.className = 'countdown text-warning';
                }
            } else {
                countdownElement.innerHTML = '已过期';
                countdownElement.className = 'countdown text-danger';
            }
        }
        
        // 如果页面包含倒计时元素，每秒更新一次
        if (document.getElementById('countdown')) {
            updateCountdown();
            setInterval(updateCountdown, 1000);
        }
        
        // 复制微信号
        function copyWechatId() {
            const wechatId = 'CatCar88';
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(wechatId).then(function() {
                    showToast('微信号已复制到剪贴板');
                }).catch(function() {
                    fallbackCopy(wechatId);
                });
            } else {
                fallbackCopy(wechatId);
            }
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
                showToast('微信号已复制到剪贴板');
            } catch (err) {
                showToast('复制失败，请手动复制');
            }
            
            document.body.removeChild(textArea);
        }
        
        // 跳转微信
        function openWechat() {
            const wechatId = 'CatCar88';
            
            // 检测设备类型和浏览器
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            const isWeChat = /MicroMessenger/i.test(navigator.userAgent);
            
            if (isWeChat) {
                // 在微信内部，直接复制微信号
                copyWechatId();
                showToast('请长按微信号进行复制');
            } else if (isMobile) {
                // 移动端尝试打开微信
                const wechatUrl = `weixin://contacts/profile/${wechatId}`;
                
                // 创建隐藏链接尝试打开微信
                const link = document.createElement('a');
                link.href = wechatUrl;
                link.style.display = 'none';
                document.body.appendChild(link);
                
                try {
                    link.click();
                    // 如果2秒后还在当前页面，说明没有微信应用
                    setTimeout(() => {
                        copyWechatId();
                        showToast('请安装微信后手动搜索添加');
                    }, 2000);
                } catch (e) {
                    copyWechatId();
                    showToast('请手动打开微信搜索添加');
                }
                
                document.body.removeChild(link);
            } else {
                // 桌面端直接复制微信号
                copyWechatId();
                showToast('请打开微信手动搜索添加');
            }
        }
    </script>
</body>
</html>
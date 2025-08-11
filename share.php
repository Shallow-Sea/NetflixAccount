<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$share_code = $_GET['code'] ?? '';
$error = '';
$success = '';

if (empty($share_code)) {
    header('Location: index.php');
    exit;
}

// 获取分享页信息
$share_page = getSharePageByCode($share_code);

// 获取弹窗公告（只在分享页显示弹窗类型的公告）
$popup_announcements = [];
$all_announcements = getActiveAnnouncements();
foreach ($all_announcements as $announcement) {
    if ($announcement['is_popup']) {
        $popup_announcements[] = $announcement;
    }
}

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
    
    // 处理激活请求 - 添加POST请求检查避免刷新重复提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'activate' && !$share_page['is_activated']) {
        if (activateSharePage($share_code)) {
            // 激活成功后重定向到同一页面避免刷新重复提交
            $redirect_url = $_SERVER['REQUEST_URI'];
            header("Location: $redirect_url?activated=1");
            exit;
        } else {
            $error = '激活失败，请稍后重试';
        }
    }
    
    // 检查是否是激活成功后的重定向
    if (isset($_GET['activated']) && $_GET['activated'] == '1') {
        $success = '激活成功！账号信息已显示在下方。';
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
        .accordion-button {
            font-weight: 500;
            border-radius: 10px !important;
        }
        .accordion-button:not(.collapsed) {
            background-color: #e7f3ff;
            border-color: #bee5eb;
        }
        .accordion-item {
            border: 1px solid #dee2e6;
            border-radius: 10px !important;
            margin-bottom: 10px;
        }
        .accordion-body {
            background-color: #f8f9fa;
        }
        .accordion-body h6 {
            color: #495057;
            font-weight: 600;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }
        .accordion-body p {
            line-height: 1.6;
        }
        .announcement-content {
            font-size: 1.1rem;
            line-height: 1.7;
        }
        .announcement-content h1,
        .announcement-content h2,
        .announcement-content h3,
        .announcement-content h4,
        .announcement-content h5,
        .announcement-content h6 {
            color: #495057;
            margin-top: 1rem;
            margin-bottom: 0.8rem;
        }
        .announcement-content p {
            margin-bottom: 1rem;
        }
        .announcement-content strong {
            color: #dc3545;
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
                    <a href="index.php" class="btn btn-primary">返回首页</a>
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
            
            <!-- 使用帮助和常见问题 -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-question-circle-fill text-info me-2"></i>
                        使用帮助和常见问题
                    </h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="helpAccordion">
                        <!-- 地区不支持问题 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRegion">
                                    <i class="bi bi-geo-alt-fill text-warning me-2"></i>
                                    提示该地区不支持怎么办？
                                </button>
                            </h2>
                            <div id="collapseRegion" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        如果显示该地区不支持，是因为魔法没有解锁流媒体
                                    </div>
                                    
                                    <h6><i class="bi bi-phone me-2"></i>手机操作方法：</h6>
                                    <p class="mb-3">关闭app，然后手机网络切换一下地区，换成<strong>美、新、台、日</strong>，切换成功之后再重新打开app登录。还是不行的话重启一下设备。</p>
                                    
                                    <h6><i class="bi bi-laptop me-2"></i>电脑操作方法：</h6>
                                    <p class="mb-3">切换地区网络换<strong>美、新、台、日</strong>，切换成功之后清除浏览器缓存重新登录。或者浏览器开无痕模式登录。</p>
                                    
                                    <h6><i class="bi bi-tv me-2"></i>电视或苹果TV操作方法：</h6>
                                    <p class="mb-0">建议重启设备，切换节点之后重新打开应用。</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 内容少的问题 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContent">
                                    <i class="bi bi-film text-primary me-2"></i>
                                    为什么只能看到很少的剧（只有自制剧）？
                                </button>
                            </h2>
                            <div id="collapseContent" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        资源随地区变化，内容是根据地区显示的
                                    </div>
                                    <p>很多地区不能完全解锁奈飞，所以只能看自制剧，会造成找的剧没有。<strong>建议多切换地区去尝试</strong>，更换其他地区即可获得更多内容。</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 到期提示问题 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExpiry">
                                    <i class="bi bi-clock-fill text-success me-2"></i>
                                    为什么提示几天后到期？
                                </button>
                            </h2>
                            <div id="collapseExpiry" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle me-2"></i>
                                        无需担心，该提示不影响观看，系统会自动续费
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 登录问题 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLogin">
                                    <i class="bi bi-shield-exclamation text-danger me-2"></i>
                                    密码错误和登录问题解决办法
                                </button>
                            </h2>
                            <div id="collapseLogin" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    <div class="alert alert-danger">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        账号发您之前我们是试过没问题，提示密码错误的请尝试以下操作
                                    </div>
                                    
                                    <h6><i class="bi bi-tv-fill me-2"></i>电视登录建议：</h6>
                                    <p class="mb-3">电视对IP质量要求最高，建议在<strong>手机app登录后，手机通过相机扫描电视二维码登录</strong></p>
                                    
                                    <h6><i class="bi bi-exclamation-circle me-2"></i>出现"尝试登录次数过多"解决办法：</h6>
                                    <div class="alert alert-warning">
                                        频繁多次登录导致风控或当前网络问题，请等待晚点更换网络地区后再尝试<br>
                                        <strong>建议40-60分钟后</strong>重试
                                    </div>
                                    
                                    <h6>解决步骤：</h6>
                                    <div class="mb-3">
                                        <strong>第一步：</strong> 打开分享页面链接，刷新后，获取登录信息（选账号密码进行登录）
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>第二步（根据设备选择）：</strong>
                                        
                                        <h6 class="mt-3"><i class="bi bi-phone me-2"></i>手机、iPad操作：</h6>
                                        <p>关闭奈飞APP → 网络切换地区（推荐：<strong>港、新、台、日</strong>）→ 等待15秒 → 重新打开奈飞APP登录</p>
                                        
                                        <h6><i class="bi bi-laptop me-2"></i>电脑谷歌浏览器操作：</h6>
                                        <p>清理浏览器缓存 → 网络切换地区（推荐：<strong>港、新、台、日</strong>）→ 等待15秒 → 重新打开网址登录</p>
                                        
                                        <h6><i class="bi bi-tv me-2"></i>电视、苹果TV操作：</h6>
                                        <p>重启设备 → 网络切换地区（推荐：<strong>港、新、台、日</strong>）→ 等待15秒 → 重新打开应用</p>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="bi bi-gear me-2"></i>
                                        <strong>重要提示：</strong>一定要开启<strong>全局模式</strong>，非全局模式网络是自动的，切换地区是无效的
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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

    <!-- 系统公告弹窗 -->
    <?php foreach ($popup_announcements as $announcement): ?>
        <div class="modal fade" id="announcement-<?php echo $announcement['id']; ?>" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-megaphone me-2"></i>
                            <?php echo htmlspecialchars($announcement['title']); ?>
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="announcement-content">
                            <?php
                            if ($announcement['content_type'] == 'markdown') {
                                echo parseMarkdown($announcement['content']);
                            } else {
                                echo $announcement['content'];
                            }
                            ?>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-primary btn-lg" data-bs-dismiss="modal">
                            <i class="bi bi-check-circle me-1"></i>
                            确定
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

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
                // 移动端尝试多种方式打开微信
                const wechatUrls = [
                    `weixin://dl/business/?t=iJE7NKV79tU`, // 微信添加好友链接
                    `weixin://contacts/profile/${wechatId}`, // 微信用户资料
                    `wechat://contacts/profile/${wechatId}`, // 备用协议
                    `weixin://` // 微信主页
                ];
                
                let tried = 0;
                const tryNext = () => {
                    if (tried < wechatUrls.length) {
                        try {
                            const link = document.createElement('a');
                            link.href = wechatUrls[tried];
                            link.style.display = 'none';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            
                            tried++;
                            
                            // 如果尝试所有URL后仍无法打开，复制微信号
                            setTimeout(() => {
                                if (tried >= wechatUrls.length) {
                                    copyWechatId();
                                    showToast('无法直接打开微信，请手动搜索添加');
                                } else {
                                    tryNext();
                                }
                            }, 1000);
                        } catch (e) {
                            tried++;
                            if (tried < wechatUrls.length) {
                                tryNext();
                            } else {
                                copyWechatId();
                                showToast('请手动打开微信搜索添加');
                            }
                        }
                    }
                };
                
                tryNext();
            } else {
                // 桌面端直接复制微信号
                copyWechatId();
                showToast('请打开微信手动搜索添加');
            }
        }
        
        // 显示弹窗公告
        <?php foreach ($popup_announcements as $announcement): ?>
            setTimeout(function() {
                var modal = new bootstrap.Modal(document.getElementById('announcement-<?php echo $announcement['id']; ?>'));
                modal.show();
            }, 500);
        <?php endforeach; ?>
    </script>
</body>
</html>
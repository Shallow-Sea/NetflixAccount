<?php
/**
 * 权限修复脚本
 * 用于修复安装过程中的文件权限问题
 */

$messages = [];
$errors = [];

// 检查并创建必要的目录
$directories = ['config', 'database'];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            $messages[] = "✅ 创建目录: $dir";
        } else {
            $errors[] = "❌ 无法创建目录: $dir";
        }
    } else {
        $messages[] = "✅ 目录已存在: $dir";
    }
    
    // 设置目录权限
    if (is_dir($dir)) {
        if (chmod($dir, 0755)) {
            $messages[] = "✅ 设置目录权限: $dir (755)";
        } else {
            $errors[] = "❌ 无法设置目录权限: $dir";
        }
    }
}

// 检查文件权限
$files = [
    'install.php',
    'share.php',
    'admin/login.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        if (chmod($file, 0644)) {
            $messages[] = "✅ 设置文件权限: $file (644)";
        } else {
            $errors[] = "❌ 无法设置文件权限: $file";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>权限修复 - 奈飞账号管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .container {
            max-width: 600px;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .brand-title {
            color: #e50914;
            font-weight: bold;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <div class="brand-title">NETFLIX</div>
                    <h3>权限修复工具</h3>
                </div>
                
                <div class="mb-4">
                    <h5><i class="bi bi-tools me-2"></i>权限检查和修复结果</h5>
                </div>
                
                <?php if (!empty($messages)): ?>
                    <div class="alert alert-success">
                        <h6><i class="bi bi-check-circle me-2"></i>成功操作</h6>
                        <?php foreach ($messages as $message): ?>
                            <div><?php echo $message; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-exclamation-triangle me-2"></i>错误信息</h6>
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle me-2"></i>手动修复方法</h6>
                    <p>如果自动修复失败，请在服务器上执行以下命令：</p>
                    <pre class="bg-light p-3 rounded"><code># 设置目录权限
chmod 755 config/
chmod 755 database/

# 设置文件权限
chmod 644 *.php
chmod 644 admin/*.php

# 或者更宽松的权限（不推荐生产环境）
chmod -R 777 ./</code></pre>
                </div>
                
                <div class="alert alert-warning">
                    <h6><i class="bi bi-shield-exclamation me-2"></i>安全提示</h6>
                    <ul class="mb-0">
                        <li>修复权限后，请删除此文件：<code>rm fix-permissions.php</code></li>
                        <li>生产环境不建议使用777权限</li>
                        <li>确保Web服务器用户对config目录有写入权限</li>
                    </ul>
                </div>
                
                <div class="text-center">
                    <a href="install.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-arrow-right me-2"></i>继续安装
                    </a>
                    <button onclick="location.reload()" class="btn btn-secondary btn-lg ms-2">
                        <i class="bi bi-arrow-clockwise me-2"></i>重新检查
                    </button>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-white">权限修复完成后，建议删除此文件以确保安全</small>
        </div>
    </div>
</body>
</html>
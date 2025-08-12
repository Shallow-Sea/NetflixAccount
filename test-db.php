<?php
/**
 * 数据库连接测试工具
 * 用于独立测试数据库连接
 */

$test_result = '';
$debug_info = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $dbname = trim($_POST['db_name'] ?? 'netflix_manager');
    $username = trim($_POST['db_username'] ?? 'root');
    $password = $_POST['db_password'] ?? '';
    
    // 收集调试信息
    $debug_info[] = "主机: " . $host;
    $debug_info[] = "数据库名: " . $dbname;
    $debug_info[] = "用户名: " . $username;
    $debug_info[] = "密码长度: " . strlen($password);
    $debug_info[] = "密码是否为空: " . (empty($password) ? '是' : '否');
    
    try {
        // 测试连接到MySQL服务器（不指定数据库）
        $dsn = "mysql:host=$host;charset=utf8mb4";
        $debug_info[] = "DSN: " . $dsn;
        
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        $test_result = '✅ 数据库连接成功！';
        
        // 尝试创建数据库
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $test_result .= '<br>✅ 数据库创建成功！';
            
            // 测试连接到具体数据库
            $dsn_with_db = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $pdo_db = new PDO($dsn_with_db, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $test_result .= '<br>✅ 连接到数据库成功！';
            
        } catch (PDOException $e) {
            $test_result .= '<br>⚠️ 数据库创建失败: ' . $e->getMessage();
        }
        
    } catch (PDOException $e) {
        $test_result = '❌ 数据库连接失败: ' . $e->getMessage();
        $debug_info[] = "错误代码: " . $e->getCode();
        
        // 常见错误解释
        if (strpos($e->getMessage(), 'Access denied') !== false) {
            if (strpos($e->getMessage(), 'using password: NO') !== false) {
                $test_result .= '<br><strong>问题分析：</strong>系统认为您没有提供密码，但您可能已经输入了密码。这通常是密码传递问题。';
            } else {
                $test_result .= '<br><strong>问题分析：</strong>用户名或密码错误。';
            }
        } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
            $test_result .= '<br><strong>问题分析：</strong>无法连接到数据库服务器，请检查MySQL是否运行。';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库连接测试 - 奈飞账号管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .container {
            max-width: 700px;
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
                    <h3>数据库连接测试</h3>
                </div>
                
                <?php if ($test_result): ?>
                    <div class="alert alert-<?php echo strpos($test_result, '❌') !== false ? 'danger' : 'success'; ?>">
                        <h6><i class="bi bi-database me-2"></i>连接结果</h6>
                        <div><?php echo $test_result; ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($debug_info)): ?>
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle me-2"></i>调试信息</h6>
                        <?php foreach ($debug_info as $info): ?>
                            <div><small><?php echo $info; ?></small></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="db_host" class="form-label">数据库主机</label>
                            <input type="text" class="form-control" id="db_host" name="db_host" 
                                   value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="db_name" class="form-label">数据库名称</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" 
                                   value="<?php echo htmlspecialchars($_POST['db_name'] ?? 'netflix_manager'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="db_username" class="form-label">数据库用户名</label>
                            <input type="text" class="form-control" id="db_username" name="db_username" 
                                   value="<?php echo htmlspecialchars($_POST['db_username'] ?? 'root'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="db_password" class="form-label">数据库密码</label>
                            <input type="password" class="form-control" id="db_password" name="db_password" 
                                   placeholder="输入数据库密码">
                            <div class="form-text">如果密码为空，请留空此字段</div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-database-check me-2"></i>测试数据库连接
                    </button>
                </form>
                
                <div class="mt-4">
                    <div class="alert alert-warning">
                        <h6><i class="bi bi-lightbulb me-2"></i>常见问题解决</h6>
                        <ul class="mb-0">
                            <li><strong>Access denied (using password: NO):</strong> 密码传递问题，请确认密码字段</li>
                            <li><strong>Access denied (using password: YES):</strong> 用户名或密码错误</li>
                            <li><strong>Connection refused:</strong> MySQL服务未启动或端口不正确</li>
                            <li><strong>Unknown MySQL server host:</strong> 主机地址错误</li>
                        </ul>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="install.php" class="btn btn-success">
                        <i class="bi bi-arrow-left me-2"></i>返回安装
                    </a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-white">测试完成后，建议删除此文件以确保安全</small>
        </div>
    </div>
</body>
</html>
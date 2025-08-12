<?php
// 调试版安装程序
$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// 检查是否已经安装
if (file_exists('config/installed.lock') && $step != 'complete') {
    header('Location: admin/login.php');
    exit;
}

// 显示所有POST数据用于调试
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<pre>调试信息 - POST数据：';
    print_r($_POST);
    echo '</pre>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // 数据库配置测试
        $host = trim($_POST['db_host'] ?? 'localhost');
        $dbname = trim($_POST['db_name'] ?? 'netflix_manager');
        $username = trim($_POST['db_username'] ?? 'root');
        $password = $_POST['db_password'] ?? '';
        
        // 详细的调试信息
        echo "<div class='alert alert-info'>";
        echo "<h6>调试信息：</h6>";
        echo "主机: " . htmlspecialchars($host) . "<br>";
        echo "数据库名: " . htmlspecialchars($dbname) . "<br>";
        echo "用户名: " . htmlspecialchars($username) . "<br>";
        echo "密码长度: " . strlen($password) . "<br>";
        echo "密码为空: " . (empty($password) ? '是' : '否') . "<br>";
        echo "原始密码字段: " . htmlspecialchars($password) . "<br>";
        echo "</div>";
        
        if (empty($password)) {
            $error = '数据库密码为空，请检查密码字段是否正确填写';
        } else {
            try {
                $dsn = "mysql:host=$host;charset=utf8mb4";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$dbname`");
                
                // 检查config目录
                if (!is_dir('config')) {
                    if (!mkdir('config', 0755, true)) {
                        throw new Exception('无法创建config目录，请检查文件权限');
                    }
                }
                
                if (!is_writable('config')) {
                    throw new Exception('config目录不可写，请设置目录权限为755或777');
                }
                
                // 保存数据库配置
                $escaped_password = str_replace(['\\', "'"], ['\\\\', "\\'"], $password);
                $config_content = "<?php
class Database {
    private \$host = '$host';
    private \$db_name = '$dbname';
    private \$username = '$username';
    private \$password = '$escaped_password';
    private \$pdo = null;
    
    public function getConnection() {
        if (\$this->pdo === null) {
            try {
                \$dsn = \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name . \";charset=utf8mb4\";
                \$options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                \$this->pdo = new PDO(\$dsn, \$this->username, \$this->password, \$options);
            } catch(PDOException \$e) {
                throw new Exception(\"数据库连接失败: \" . \$e->getMessage());
            }
        }
        return \$this->pdo;
    }
}

// 数据库实例
\$db = new Database();
\$pdo = \$db->getConnection();
?>";
                
                $write_result = file_put_contents('config/database.php', $config_content);
                if ($write_result === false) {
                    throw new Exception('无法写入数据库配置文件，请检查config目录权限');
                }
                
                $success = '数据库连接成功，配置文件已保存！';
                $step = 2;
                
            } catch (PDOException $e) {
                $error = '数据库连接失败: ' . $e->getMessage();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>调试版安装 - 奈飞账号管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .install-container {
            max-width: 600px;
            margin: 0 auto;
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
    <div class="container install-container">
        <div class="card">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <div class="brand-title">NETFLIX</div>
                    <h3>调试版数据库配置</h3>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
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
                                   autocomplete="off" placeholder="请输入数据库密码">
                            <div class="form-text">如果密码为空，请留空此字段</div>
                        </div>
                    </div>
                    
                    <!-- 添加隐藏字段用于调试 -->
                    <input type="hidden" name="debug" value="1">
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-database me-2"></i>测试连接（调试版）
                    </button>
                </form>
                
                <div class="alert alert-info mt-4">
                    <h6><i class="bi bi-info-circle me-2"></i>调试说明</h6>
                    <p class="mb-0">此版本会显示详细的调试信息，包括接收到的所有POST数据。请输入密码后点击测试。</p>
                </div>
                
                <div class="text-center mt-3">
                    <a href="install.php" class="btn btn-secondary">返回正常安装</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
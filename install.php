<?php
// 简单的安装向导
$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// 检查是否已经安装
if (file_exists('config/installed.lock') && $step != 'complete') {
    header('Location: admin/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // 数据库配置测试
        $host = trim($_POST['db_host'] ?? 'localhost');
        $dbname = trim($_POST['db_name'] ?? 'netflix_manager');
        $username = trim($_POST['db_username'] ?? 'root');
        $password = $_POST['db_password'] ?? ''; // 密码可能为空，不要trim
        
        // 调试信息（临时使用，生产环境应删除）
        if (empty($password)) {
            $error = '数据库密码为空，请检查密码字段是否正确填写';
        } else {
            try {
                // 先测试数据库连接
                $dsn = "mysql:host=$host;charset=utf8mb4";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // 创建数据库
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$dbname`");
            
            // 检查config目录是否存在，不存在则创建
            if (!is_dir('config')) {
                if (!mkdir('config', 0755, true)) {
                    throw new Exception('无法创建config目录，请检查文件权限');
                }
            }
            
            // 检查config目录是否可写
            if (!is_writable('config')) {
                throw new Exception('config目录不可写，请设置目录权限为755或777');
            }
            
            // 保存数据库配置，正确转义密码中的特殊字符
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
            
                $step = 2;
            } catch (PDOException $e) {
                $error = '数据库连接失败: ' . $e->getMessage() . '（请检查数据库主机、用户名、密码是否正确）';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($step == 2) {
        // 创建数据表和初始数据
        try {
            if (!file_exists('config/database.php')) {
                throw new Exception('数据库配置文件不存在，请重新配置数据库');
            }
            
            require_once 'config/database.php';
            
            if (!file_exists('database/setup.sql')) {
                throw new Exception('数据库结构文件(database/setup.sql)不存在');
            }
            
            // 读取SQL文件并执行
            $sql = file_get_contents('database/setup.sql');
            if ($sql === false) {
                throw new Exception('无法读取数据库结构文件');
            }
            
            // 分解SQL语句并执行
            $statements = explode(';', $sql);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    try {
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        // 如果是表已存在的错误，继续执行
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            throw $e;
                        }
                    }
                }
            }
            
            $step = 3;
        } catch (Exception $e) {
            $error = '数据表创建失败: ' . $e->getMessage();
        }
    } elseif ($step == 3) {
        // 创建管理员账号
        $admin_username = $_POST['admin_username'] ?? 'admin';
        $admin_password = $_POST['admin_password'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        
        if (empty($admin_password) || strlen($admin_password) < 6) {
            $error = '管理员密码长度至少6位';
        } else {
            try {
                require_once 'config/database.php';
                
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO admins (username, password, email, is_active) 
                    VALUES (?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE 
                    password = VALUES(password), 
                    email = VALUES(email)
                ");
                
                $stmt->execute([$admin_username, $hashed_password, $admin_email]);
                
                // 创建安装锁定文件
                file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
                
                $step = 'complete';
            } catch (Exception $e) {
                $error = '管理员账号创建失败: ' . $e->getMessage();
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
    <title>系统安装 - 奈飞账号管理系统</title>
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
        .step-indicator {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        .step {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            margin-right: 10px;
            font-weight: bold;
        }
        .step.active {
            background: #0d6efd;
            color: white;
        }
        .step.completed {
            background: #198754;
            color: white;
        }
        .step.pending {
            background: #e9ecef;
            color: #6c757d;
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
                    <h3>账号管理系统安装向导</h3>
                </div>
                
                <!-- 步骤指示器 -->
                <div class="step-indicator">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'pending'; ?>">1</span>
                            数据库配置
                        </div>
                        <div>
                            <span class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'pending'; ?>">2</span>
                            创建数据表
                        </div>
                        <div>
                            <span class="step <?php echo $step >= 3 ? ($step == 'complete' ? 'completed' : 'active') : 'pending'; ?>">3</span>
                            管理员账号
                        </div>
                        <div>
                            <span class="step <?php echo $step == 'complete' ? 'completed' : 'pending'; ?>">✓</span>
                            安装完成
                        </div>
                    </div>
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
                
                <?php if ($step == 1): ?>
                    <!-- 步骤1：数据库配置 -->
                    <div class="mb-4">
                        <h4><i class="bi bi-database me-2"></i>数据库配置</h4>
                        <p class="text-muted">请输入数据库连接信息，系统将自动创建数据库（如果不存在）。</p>
                        
                        <?php 
                        // 检查文件权限
                        $permission_issues = [];
                        if (!is_dir('config')) {
                            $permission_issues[] = 'config目录不存在';
                        } elseif (!is_writable('config')) {
                            $permission_issues[] = 'config目录不可写';
                        }
                        
                        if (!empty($permission_issues)):
                        ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>权限检查：</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($permission_issues as $issue): ?>
                                        <li><?php echo $issue; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="mt-2">
                                    <strong>解决方法：</strong><br>
                                    <code>chmod 755 config/</code> 或 <code>chmod 777 config/</code>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="db_host" class="form-label">数据库主机</label>
                                <input type="text" class="form-control" id="db_host" name="db_host" 
                                       value="<?php echo $_POST['db_host'] ?? 'localhost'; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="db_name" class="form-label">数据库名称</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" 
                                       value="<?php echo $_POST['db_name'] ?? 'netflix_manager'; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="db_username" class="form-label">数据库用户名</label>
                                <input type="text" class="form-control" id="db_username" name="db_username" 
                                       value="<?php echo $_POST['db_username'] ?? 'root'; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="db_password" class="form-label">数据库密码</label>
                                <input type="password" class="form-control" id="db_password" name="db_password">
                                <div class="form-text">如果数据库密码为空，请留空此字段</div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-arrow-right me-2"></i>测试连接并继续
                        </button>
                    </form>
                    
                <?php elseif ($step == 2): ?>
                    <!-- 步骤2：创建数据表 -->
                    <div class="mb-4">
                        <h4><i class="bi bi-table me-2"></i>创建数据表</h4>
                        <p class="text-muted">系统将创建必要的数据表和初始数据。</p>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        数据库连接成功！即将创建以下数据表：
                        <ul class="mt-2 mb-0">
                            <li>管理员表 (admins)</li>
                            <li>奈飞账号表 (netflix_accounts)</li>
                            <li>分享页表 (share_pages)</li>
                            <li>公告表 (announcements)</li>
                            <li>系统设置表 (settings)</li>
                            <li>操作日志表 (operation_logs)</li>
                        </ul>
                    </div>
                    
                    <form method="POST">
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-database-add me-2"></i>创建数据表
                        </button>
                    </form>
                    
                <?php elseif ($step == 3): ?>
                    <!-- 步骤3：创建管理员账号 -->
                    <div class="mb-4">
                        <h4><i class="bi bi-person-plus me-2"></i>创建管理员账号</h4>
                        <p class="text-muted">请设置系统管理员账号，用于登录管理界面。</p>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="admin_username" class="form-label">管理员用户名</label>
                            <input type="text" class="form-control" id="admin_username" name="admin_username" 
                                   value="<?php echo $_POST['admin_username'] ?? 'admin'; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_password" class="form-label">管理员密码</label>
                            <input type="password" class="form-control" id="admin_password" name="admin_password" 
                                   minlength="6" required>
                            <div class="form-text">密码长度至少6位</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_email" class="form-label">管理员邮箱（可选）</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                   value="<?php echo $_POST['admin_email'] ?? ''; ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-warning btn-lg w-100">
                            <i class="bi bi-check-circle me-2"></i>完成安装
                        </button>
                    </form>
                    
                <?php elseif ($step == 'complete'): ?>
                    <!-- 安装完成 -->
                    <div class="text-center">
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        
                        <h4 class="text-success mb-3">安装完成！</h4>
                        
                        <p class="text-muted mb-4">
                            奈飞账号管理系统已成功安装。<br>
                            您现在可以使用管理员账号登录系统。
                        </p>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-shield-exclamation me-2"></i>
                            <strong>安全提示：</strong>请删除或重命名 install.php 文件以确保系统安全。
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="admin/login.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>进入管理界面
                            </a>
                            <a href="share.php?code=demo" class="btn btn-outline-secondary" target="_blank">
                                <i class="bi bi-eye me-2"></i>查看分享页示例
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-white">
                Netflix 账号管理系统 v1.0 
                <br>
                © 2024 Netflix Manager. All rights reserved.
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
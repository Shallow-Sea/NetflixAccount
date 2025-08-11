<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_DATABASE', 'netflix_share');

// 创建数据库连接
function getConnection() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE . ";charset=utf8mb4",
                DB_USERNAME,
                DB_PASSWORD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    
    return $connection;
}

// 初始化数据库
function initDatabase() {
    try {
        // 先连接到MySQL服务器（不指定数据库）
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";charset=utf8mb4",
            DB_USERNAME,
            DB_PASSWORD,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // 创建数据库（如果不存在）
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_DATABASE . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // 选择数据库
        $pdo->exec("USE " . DB_DATABASE);
        
        // 读取并执行SQL文件
        $sql = file_get_contents(__DIR__ . '/../database_design.sql');
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("数据库初始化失败: " . $e->getMessage());
        return false;
    }
}

// 检查数据库是否已初始化
function isDatabaseInitialized() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}
?>
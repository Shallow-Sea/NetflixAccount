<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'netflix_manager';
    private $username = 'root';
    private $password = '';
    private $pdo = null;
    
    public function getConnection() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch(PDOException $e) {
                throw new Exception("数据库连接失败: " . $e->getMessage());
            }
        }
        return $this->pdo;
    }
}

// 数据库实例
$db = new Database();
$pdo = $db->getConnection();
?>
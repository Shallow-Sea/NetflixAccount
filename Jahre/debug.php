<?php
// 简单的调试页面
echo "PHP版本: " . PHP_VERSION . "<br>";
echo "当前时间: " . date('Y-m-d H:i:s') . "<br>";

// 检查session
session_start();
echo "Session ID: " . session_id() . "<br>";

// 检查数据库连接
try {
    require_once '../config/database.php';
    echo "数据库连接: 成功<br>";
} catch (Exception $e) {
    echo "数据库连接: 失败 - " . $e->getMessage() . "<br>";
}

// 检查functions文件
try {
    require_once '../includes/functions.php';
    echo "Functions文件: 成功<br>";
} catch (Exception $e) {
    echo "Functions文件: 失败 - " . $e->getMessage() . "<br>";
}

// 检查管理员权限
try {
    if (function_exists('checkAdminAccess')) {
        checkAdminAccess();
        echo "管理员权限: 成功<br>";
    } else {
        echo "管理员权限: checkAdminAccess函数不存在<br>";
    }
} catch (Exception $e) {
    echo "管理员权限: 失败 - " . $e->getMessage() . "<br>";
}

// 检查Netflix账号获取
try {
    if (function_exists('getNetflixAccounts')) {
        $accounts = getNetflixAccounts('active');
        echo "活跃Netflix账号数量: " . count($accounts) . "<br>";
    } else {
        echo "getNetflixAccounts函数不存在<br>";
    }
} catch (Exception $e) {
    echo "获取Netflix账号: 失败 - " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "如果上面所有检查都通过，问题可能在share-pages.php的具体实现中。<br>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug</title>
</head>
<body>
    <h1>JavaScript测试</h1>
    <button onclick="testFunc()">测试按钮</button>
    
    <script>
        function testFunc() {
            alert('JavaScript正常工作！');
        }
        
        console.log('JavaScript已加载');
        document.addEventListener('DOMContentLoaded', function() {
            console.log('页面DOM已加载完成');
        });
    </script>
</body>
</html>
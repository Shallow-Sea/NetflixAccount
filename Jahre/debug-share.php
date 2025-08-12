<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// 简化版本，只测试基本功能
$error = '';
$success = '';

// 获取Netflix账号列表用于创建分享页
try {
    $active_accounts = getNetflixAccounts('active');
    echo "Found " . count($active_accounts) . " active accounts<br>";
} catch (Exception $e) {
    echo "Error getting accounts: " . $e->getMessage() . "<br>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Debug Test Page</h1>
        <button onclick="copyToClipboard('test')">Test Copy</button>
        <button onclick="deleteSharePage(123)">Test Delete</button>
    </div>

    <script>
        function copyToClipboard(text) {
            alert('Copy function called with: ' + text);
        }
        
        function deleteSharePage(id) {
            alert('Delete function called with ID: ' + id);
        }
    </script>
</body>
</html>
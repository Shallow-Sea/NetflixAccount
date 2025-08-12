<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

checkAdminAccess();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>Minimal Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>最小化测试页面</h1>
        
        <button onclick="copyToClipboard('test')" class="btn btn-primary">测试复制</button>
        <button onclick="toggleSelectAll()" class="btn btn-secondary">测试全选</button>
        <button onclick="deleteSharePage(123)" class="btn btn-danger">测试删除</button>
        
        <p class="mt-3">如果上面的按钮都能正常工作（弹出alert），说明JavaScript正常。</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(text) {
            alert('copyToClipboard called with: ' + text);
        }
        
        function toggleSelectAll() {
            alert('toggleSelectAll called');
        }
        
        function deleteSharePage(id) {
            alert('deleteSharePage called with ID: ' + id);
        }
        
        // 页面加载完成后的测试
        document.addEventListener('DOMContentLoaded', function() {
            console.log('JavaScript loaded successfully');
        });
    </script>
</body>
</html>
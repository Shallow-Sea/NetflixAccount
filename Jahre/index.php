<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// 检查是否已经登录
if (isset($_SESSION['admin_id'])) {
    // 已登录，重定向到管理仪表板
    header('Location: admin-dashboard.php');
} else {
    // 未登录，重定向到登录页面
    header('Location: ../login.php');
}
exit;
?>
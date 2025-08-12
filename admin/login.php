<?php
session_start();
require_once '../includes/functions.php';

$error = '';

// 如果已经登录，重定向到仪表板
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } else {
        if (login($username, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = '用户名或密码错误';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 奈飞账号管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            padding: 3rem;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: #e50914;
            font-weight: bold;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #666;
            margin: 0;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 1rem;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: #e50914;
            box-shadow: 0 0 0 0.2rem rgba(229, 9, 20, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #e50914 0%, #b2070f 100%);
            border: none;
            border-radius: 10px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: bold;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(229, 9, 20, 0.3);
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>NETFLIX</h1>
            <p>管理系统</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="bi bi-person me-2"></i>用户名
                </label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="bi bi-lock me-2"></i>密码
                </label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-danger btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                登录
            </button>
        </form>
    </div>
</body>
</html>
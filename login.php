<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// 如果已经登录，重定向到主页
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// 检查数据库是否已初始化
if (!isDatabaseInitialized()) {
    if (initDatabase()) {
        $success = '系统初始化成功！默认管理员账号：admin，密码：password';
    } else {
        $error = '系统初始化失败，请检查数据库配置';
    }
}

// 处理登录请求
if ($_POST['action'] ?? '' === 'login') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $is_admin = isset($_POST['is_admin']);
    
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        if (login($username, $password, $is_admin)) {
            header('Location: index.php');
            exit;
        } else {
            $error = '用户名或密码错误';
        }
    }
}

// 处理注册请求
if ($_POST['action'] ?? '' === 'register') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } elseif ($password !== $confirm_password) {
        $error = '两次输入的密码不一致';
    } elseif (strlen($password) < 6) {
        $error = '密码长度至少为6位';
    } else {
        if (registerUser($username, $password, $email)) {
            $success = '注册成功，请登录';
        } else {
            $error = '注册失败，用户名可能已存在';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 奈飞账号分享系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-container {
            margin-top: 5vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            text-align: center;
            padding: 2rem;
        }
        .tab-content {
            padding: 2rem;
        }
        .nav-pills .nav-link {
            color: #667eea;
            border-radius: 20px;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 20px;
            padding: 0.5rem 2rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">
                            <i class="bi bi-tv me-2"></i>
                            奈飞分享系统
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($error): ?>
                            <div class="alert alert-danger mx-4 mt-3 mb-0">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success mx-4 mt-3 mb-0">
                                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- 导航标签 -->
                        <ul class="nav nav-pills nav-justified mx-4 mt-4" id="authTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="login-tab" data-bs-toggle="pill" data-bs-target="#login" type="button" role="tab">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> 登录
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="register-tab" data-bs-toggle="pill" data-bs-target="#register" type="button" role="tab">
                                    <i class="bi bi-person-plus me-1"></i> 注册
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="authTabContent">
                            <!-- 登录表单 -->
                            <div class="tab-pane fade show active" id="login" role="tabpanel">
                                <form method="POST">
                                    <input type="hidden" name="action" value="login">
                                    
                                    <div class="mb-3">
                                        <label for="username" class="form-label">用户名</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-person"></i>
                                            </span>
                                            <input type="text" class="form-control" id="username" name="username" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label">密码</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin">
                                        <label class="form-check-label" for="is_admin">
                                            管理员登录
                                        </label>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-box-arrow-in-right me-1"></i> 登录
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- 注册表单 -->
                            <div class="tab-pane fade" id="register" role="tabpanel">
                                <form method="POST">
                                    <input type="hidden" name="action" value="register">
                                    
                                    <div class="mb-3">
                                        <label for="reg_username" class="form-label">用户名</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-person"></i>
                                            </span>
                                            <input type="text" class="form-control" id="reg_username" name="username" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reg_email" class="form-label">邮箱（可选）</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-envelope"></i>
                                            </span>
                                            <input type="email" class="form-control" id="reg_email" name="email">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reg_password" class="form-label">密码</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="reg_password" name="password" minlength="6" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">确认密码</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="bi bi-lock-fill"></i>
                                            </span>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-person-plus me-1"></i> 注册
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-light">
                        © 2024 奈飞账号分享系统. 保留所有权利.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 密码确认验证
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('reg_password').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('密码不匹配');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
-- 奈飞账号分享管理系统数据库设计

-- 管理员表
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 奈飞账号表
CREATE TABLE netflix_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'expired', 'banned') DEFAULT 'active',
    profile_count INT DEFAULT 4,
    used_profiles INT DEFAULT 0,
    subscription_type ENUM('basic', 'standard', 'premium') DEFAULT 'premium',
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 用户表
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    registration_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 分享页表
CREATE TABLE share_pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    share_code VARCHAR(32) UNIQUE NOT NULL,
    netflix_account_id INT NOT NULL,
    user_id INT NULL,
    card_type ENUM('day', 'week', 'month', 'quarter', 'halfyear', 'year') NOT NULL,
    duration_days INT NOT NULL,
    is_activated BOOLEAN DEFAULT FALSE,
    activated_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (netflix_account_id) REFERENCES netflix_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 激活记录表
CREATE TABLE activation_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    share_page_id INT NOT NULL,
    user_id INT NULL,
    activation_ip VARCHAR(45),
    user_agent TEXT,
    activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (share_page_id) REFERENCES share_pages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 公告表
CREATE TABLE announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    content_type ENUM('html', 'markdown') DEFAULT 'html',
    is_popup BOOLEAN DEFAULT FALSE,
    popup_duration INT DEFAULT 5000, -- 毫秒
    is_active BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 系统配置表
CREATE TABLE system_configs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 插入默认管理员账号
INSERT INTO admins (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

-- 插入系统默认配置
INSERT INTO system_configs (config_key, config_value, description) VALUES
('site_name', '奈飞账号分享系统', '网站名称'),
('registration_enabled', 'true', '是否开放注册'),
('max_accounts_per_user', '5', '每个用户最大账号数量'),
('day_card_price', '5', '天卡价格'),
('week_card_price', '30', '周卡价格'),
('month_card_price', '100', '月卡价格'),
('quarter_card_price', '280', '季度卡价格'),
('halfyear_card_price', '500', '半年卡价格'),
('year_card_price', '900', '年卡价格');
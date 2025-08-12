-- 奈飞账号管理系统数据库结构

-- 创建数据库
CREATE DATABASE IF NOT EXISTS netflix_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE netflix_manager;

-- 管理员表
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- 奈飞账号表
CREATE TABLE IF NOT EXISTS netflix_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    subscription_type ENUM('basic', 'standard', 'premium') DEFAULT 'premium',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    
    -- 5个车位配置
    slot1_enabled BOOLEAN DEFAULT TRUE,
    slot1_pin VARCHAR(10) DEFAULT NULL,
    slot2_enabled BOOLEAN DEFAULT TRUE,
    slot2_pin VARCHAR(10) DEFAULT NULL,
    slot3_enabled BOOLEAN DEFAULT TRUE,
    slot3_pin VARCHAR(10) DEFAULT NULL,
    slot4_enabled BOOLEAN DEFAULT TRUE,
    slot4_pin VARCHAR(10) DEFAULT NULL,
    slot5_enabled BOOLEAN DEFAULT TRUE,
    slot5_pin VARCHAR(10) DEFAULT NULL,
    
    -- 负载统计
    usage_count INT DEFAULT 0,
    last_used_at TIMESTAMP NULL,
    
    -- 时间戳
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_usage_count (usage_count),
    INDEX idx_last_used (last_used_at)
);

-- 分享页表
CREATE TABLE IF NOT EXISTS share_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    share_code VARCHAR(32) NOT NULL UNIQUE,
    netflix_account_id INT NOT NULL,
    card_type ENUM('day', 'week', 'month', 'quarter', 'half_year', 'year') NOT NULL,
    duration_days INT NOT NULL,
    
    -- 激活状态
    is_activated BOOLEAN DEFAULT FALSE,
    activated_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    
    -- 分享页设置
    title VARCHAR(255) DEFAULT '奈飞高级账号分享',
    description TEXT DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (netflix_account_id) REFERENCES netflix_accounts(id) ON DELETE CASCADE,
    INDEX idx_share_code (share_code),
    INDEX idx_activated (is_activated),
    INDEX idx_expires (expires_at)
);

-- 公告表
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    content_type ENUM('html', 'markdown') DEFAULT 'html',
    
    -- 弹窗设置
    is_popup BOOLEAN DEFAULT FALSE,
    popup_duration INT DEFAULT 5000, -- 毫秒
    
    -- 显示控制
    is_active BOOLEAN DEFAULT TRUE,
    start_date TIMESTAMP NULL,
    end_date TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active),
    INDEX idx_popup (is_popup),
    INDEX idx_dates (start_date, end_date)
);

-- 系统设置表
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 操作日志表
CREATE TABLE IF NOT EXISTS operation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NOT NULL, -- 'account', 'share_page', 'announcement', 'admin'
    target_id INT NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    INDEX idx_admin (admin_id),
    INDEX idx_action (action),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created (created_at)
);

-- 插入默认管理员账号
INSERT INTO admins (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- 密码: password

-- 插入默认系统设置
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_title', '奈飞账号管理系统', '网站标题'),
('contact_wechat', 'CatCar88', '客服微信号'),
('default_card_types', 'day:1,week:7,month:30,quarter:90,half_year:180,year:365', '卡类型对应天数'),
('auto_select_account', '1', '自动选择负载最低的账号'),
('max_account_usage', '10', '单个账号最大使用次数');
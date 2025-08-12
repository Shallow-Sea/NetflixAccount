# 奈飞账号管理系统

一套完整的奈飞账号分享管理系统，包含前端展示页面和后端管理界面。

## 功能特性

### 🎯 核心功能

- **奈飞账号管理**
  - 添加/编辑/删除账号
  - 批量导入账号
  - 账号状态管理（活跃/非活跃/暂停）
  - 5个车位独立开关和PIN码设置
  - 负载均衡自动分配

- **分享页管理**
  - 创建独立分享页面
  - 批量生成分享页
  - 多种卡类型支持（天卡/周卡/月卡/季度卡/半年卡/年卡）
  - 自动激活系统
  - 分享链接导出（TXT/Excel格式）

- **公告系统**
  - 全局弹窗公告
  - HTML/Markdown格式支持
  - 定时显示控制
  - 弹窗时长设置

- **管理员系统**
  - 多管理员支持
  - 密码安全管理
  - 操作日志记录
  - 权限控制

### 🎨 界面特色

- 现代化响应式设计
- Bootstrap 5 + 渐变背景
- 直观的管理仪表板
- 移动端友好界面
- 用户友好的分享页面

## 系统要求

- **PHP**: 7.4+ (推荐 8.0+)
- **MySQL**: 5.7+ 或 MariaDB 10.3+
- **Web服务器**: Apache/Nginx
- **PHP扩展**: PDO, PDO_MySQL, mbstring

## 安装步骤

### 1. 下载系统文件
```bash
# 将所有文件上传到您的网站目录
```

### 2. 设置文件权限
```bash
chmod 755 config/
chmod 755 database/
chmod 644 *.php
```

### 3. 运行安装向导
访问 `http://yourdomain.com/install.php` 并按照步骤完成安装：

1. **数据库配置** - 输入MySQL数据库连接信息
2. **创建数据表** - 系统自动创建必要的数据表
3. **管理员账号** - 设置系统管理员账号

### 4. 安全设置
安装完成后，请删除或重命名 `install.php` 文件：
```bash
rm install.php
# 或
mv install.php install.php.bak
```

## 使用说明

### 管理后台访问
- 地址：`http://yourdomain.com/admin/login.php`
- 默认账号：安装时设置的管理员账号

### 分享页面访问
- 地址格式：`http://yourdomain.com/share.php?code=分享码`
- 分享码由系统自动生成

## 目录结构

```
netflix-manager/
├── admin/                  # 管理后台
│   ├── login.php          # 登录页面
│   ├── dashboard.php      # 仪表板
│   ├── netflix-accounts.php # 账号管理
│   ├── share-pages.php    # 分享页管理
│   ├── announcements.php  # 公告管理
│   ├── admins.php         # 管理员管理
│   └── logout.php         # 登出
├── config/                # 配置文件
│   └── database.php       # 数据库配置
├── database/              # 数据库文件
│   └── setup.sql          # 数据库结构
├── includes/              # 公共函数库
│   └── functions.php      # 核心函数
├── share.php              # 分享页面（前端）
├── install.php            # 安装向导
└── README.md              # 说明文档
```

## 数据库表结构

- `admins` - 管理员表
- `netflix_accounts` - 奈飞账号表
- `share_pages` - 分享页表
- `announcements` - 公告表
- `settings` - 系统设置表
- `operation_logs` - 操作日志表

## 配置说明

### 卡类型配置
在 `settings` 表中可以修改卡类型对应的天数：
- `day` - 天卡 (1天)
- `week` - 周卡 (7天)
- `month` - 月卡 (30天)
- `quarter` - 季度卡 (90天)
- `half_year` - 半年卡 (180天)
- `year` - 年卡 (365天)

### 客服微信配置
在系统设置中可以修改客服微信号。

## API接口

系统所有操作均通过内置函数完成，主要接口包括：

### 账号管理
- `addNetflixAccount($data)` - 添加账号
- `updateNetflixAccount($id, $data)` - 更新账号
- `deleteNetflixAccount($id)` - 删除账号
- `getNetflixAccounts($page, $limit, $filters)` - 获取账号列表

### 分享页管理
- `addSharePage($data)` - 创建分享页
- `activateSharePage($share_code)` - 激活分享页
- `getSharePageByCode($share_code)` - 获取分享页信息

### 公告管理
- `addAnnouncement($data)` - 创建公告
- `getActiveAnnouncements()` - 获取活跃公告

## 常见问题

### Q: 如何修改分享页的客服微信？
A: 在管理后台的系统设置中修改，或直接修改 `share.php` 中的微信号。

### Q: 如何自定义分享页样式？
A: 修改 `share.php` 文件中的CSS样式部分。

### Q: 如何设置定时任务清理过期分享页？
A: 可以设置Linux cron任务，定期调用清理脚本：
```bash
# 每小时清理过期分享页
0 * * * * php /path/to/cleanup.php
```

### Q: 如何备份数据？
A: 定期备份MySQL数据库：
```bash
mysqldump -u username -p netflix_manager > backup.sql
```

## 更新日志

### v1.0.0 (2024-08-12)
- ✅ 初始版本发布
- ✅ 完整的奈飞账号管理功能
- ✅ 分享页生成和管理
- ✅ 公告系统
- ✅ 管理员权限管理
- ✅ 响应式前端界面
- ✅ 自动安装向导

## 支持与反馈

如有问题或建议，请通过以下方式联系：

- 邮箱：yuhangshallow@gmail.com

## 许可证

本项目采用 MIT 许可证，详见 LICENSE 文件。

## 免责声明

本系统仅供学习和研究使用，请遵守相关法律法规。使用本系统产生的任何问题由使用者自行承担。

---

© 2024 Netflix Manager. All rights reserved.
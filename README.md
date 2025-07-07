# 好朋友博客系统

一个简洁美观的个人博客系统，支持图文、图片和文字三种文章类型，具有现代化的响应式设计。

## 功能特点

### 核心功能
- 📝 **多类型文章**：支持图文并茂、图片分享、文字分享三种类型
- 👥 **用户管理**：管理员和作者两种角色权限
- 🖼️ **图片管理**：支持图片上传、管理和展示
- 🔍 **搜索功能**：全文搜索，支持标题和内容检索
- 📅 **归档功能**：按年月日归档文章
- 📱 **响应式设计**：适配PC、平板、手机等设备

### 界面特色
- 🎨 **瀑布流布局**：类似Pinterest的卡片式展示
- 🌙 **暗色模式**：支持明暗主题切换
- ✨ **现代化UI**：基于Tailwind CSS的美观界面
- 🚀 **快捷操作**：随机文章、快速搜索、一键回顶
- 📊 **统计展示**：网站数据统计和文章计数

### 编辑功能
- 📋 **富文本编辑**：基于Quill.js的强大编辑器
- 🏷️ **文章类型**：自动识别和分类不同类型内容
- 💾 **自动保存**：编辑过程中自动保存草稿
- 🔗 **分享功能**：支持复制链接和社交媒体分享

## 技术栈

- **后端**：PHP 7.4+
- **数据库**：MySQL 5.7+
- **前端**：HTML5 + Tailwind CSS + Alpine.js
- **编辑器**：Quill.js
- **图标**：Font Awesome

## 安装部署

### 环境要求
- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Web服务器（Apache/Nginx）
- 支持PDO扩展

### 安装步骤

1. **下载源码**
   ```bash
---AI帮我写的readme 凑合看吧---
   ```

2. **配置数据库**
   - 创建MySQL数据库：`urdata`
   - 修改 `config/database.php` 中的数据库连接信息：
   ```php
   define('DB_HOST', 'localhost');     // 数据库地址
   define('DB_NAME', 'urdata');        // 数据库名
   define('DB_USER', 'root');          // 数据库用户名
   define('DB_PASS', '123456');        // 数据库密码
   ```

3. **创建上传目录**
   ```bash
   mkdir uploads
   chmod 755 uploads
   ```

4. **访问网站**
   - 将项目文件放置到Web服务器目录
   - 访问网站，系统会自动创建数据库表

5. **数据库升级（可选）**
   如需支持完整的多类型文章功能，请执行以下SQL语句：
   ```sql
   ALTER TABLE posts ADD COLUMN post_type VARCHAR(20) DEFAULT 'article';
   ALTER TABLE posts ADD COLUMN images TEXT;
   ```



## 使用说明

### 发布文章
1. 登录后点击"写文章"
2. 选择文章类型：
   - **图文并茂**：使用富文本编辑器创建包含文字和图片的文章
   - **图片分享**：上传多张图片并添加简短描述
   - **文字分享**：纯文字内容，适合随笔和心情记录
3. 填写标题、内容和摘要
4. 选择是否立即发布
5. 保存文章

### 管理文章
- 在首页点击文章下方的编辑/删除按钮
- 支持草稿保存和发布状态切换
- 可修改文章所有信息

### 浏览功能
- **首页展示**：轮播图 + 瀑布流文章列表
- **分类浏览**：按文章类型筛选
- **搜索文章**：在导航栏搜索框输入关键词
- **时间归档**：侧边栏显示按月归档
- **随机阅读**：点击随机按钮浏览随机文章

## 目录结构

```
blog-system/
├── api/                    # API接口
│   ├── search.php         # 搜索接口
│   ├── upload_image.php   # 图片上传
│   └── ...
├── config/                # 配置文件
│   └── database.php       # 数据库配置
├── includes/              # 公共文件
│   ├── functions.php      # 核心函数
│   ├── header.php         # 页面头部
│   └── sidebar.php        # 侧边栏
├── uploads/               # 上传文件目录
├── index.php              # 首页
├── post.php               # 文章详情页
├── create.php             # 创建文章页
├── edit.php               # 编辑文章页
├── login.php              # 登录页
└── logout.php             # 登出处理
```

## 数据库结构

### posts 表（文章）
- `id`：文章ID
- `title`：文章标题
- `content`：文章内容
- `excerpt`：文章摘要
- `cover_image`：封面图片
- `published`：发布状态
- `created_at`：创建时间
- `updated_at`：更新时间

> **注意**：如需支持多类型文章和图片功能，需要手动添加以下字段：
> - `post_type VARCHAR(20) DEFAULT 'article'`：文章类型
> - `images TEXT`：文章图片（JSON格式）

### users 表（用户）
- `id`：用户ID
- `username`：用户名
- `password`：密码
- `email`：邮箱
- `role`：角色（admin/author）
- `status`：状态（active/inactive）
- `last_login`：最后登录时间

### images 表（图片）
- `id`：图片ID
- `filename`：文件名
- `original_name`：原始文件名
- `file_path`：文件路径
- `mime_type`：文件类型
- `file_size`：文件大小

## 注意事项

1. **文件权限**：确保 `uploads/` 目录有写入权限
2. **PHP设置**：建议设置合适的 `upload_max_filesize` 和 `post_max_size`
3. **安全性**：生产环境请修改默认密码和数据库配置
4. **备份**：定期备份数据库和上传的文件

## 浏览器支持

- Chrome 80+
- Firefox 70+
- Safari 13+
- Edge 80+

## 许可证

MIT License

## 更新日志

### v1.0.0
- 基础博客功能
- 多类型文章支持
- 响应式设计
- 用户权限管理
- 图片上传功能
- 搜索和归档功能 

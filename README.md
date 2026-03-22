# HuSNS

一款免费开源的轻量级社交平台系统。

## 项目简介

HuSNS 是一个基于 PHP 开发的轻量级社交平台，采用自研 MVC 架构，无需依赖第三方框架，开箱即用。系统功能完整，包含用户注册登录、动态发布、评论互动、关注体系、话题聚合、积分系统等社交平台核心功能。

## 功能特性

- 📝 **动态发布** - 支持文字、图片、视频、附件发布
- 💬 **互动交流** - 评论、点赞、转发功能
- 👥 **社交关系** - 用户关注、粉丝体系
- 🏷️ **话题系统** - #话题# 标签聚合
- 📧 **邮件通知** - 邮箱验证、消息通知
- 🎯 **积分系统** - 可自定义积分规则
- 🔒 **隐藏内容** - [hide]标签评论可见
- 👤 **@提及** - @用户名 提醒功能
- 🎨 **主题切换** - 支持明暗主题切换
- 🔌 **插件系统** - 钩子机制支持扩展
- 📱 **响应式设计** - 适配PC和移动端

## 环境要求

- PHP >= 7.4.0
- MySQL >= 5.6
- PDO 扩展
- GD 库
- MBString 扩展
- JSON 扩展

## 安装说明

1. 下载源码并解压到网站目录
2. 配置 Web 服务器指向项目根目录
3. 访问网站，自动跳转到安装向导
4. 按照向导填写数据库信息和管理员账号
5. 完成安装

### Nginx 配置示例

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/husns;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Apache 配置

项目已包含 `.htaccess` 文件，确保 Apache 开启了 `mod_rewrite` 模块。

## 目录结构

```
├── admin/              # 后台管理控制器
├── content/            # 内容模块
│   ├── announcement/   # 公告模块
│   ├── download/       # 下载模块
│   ├── link/           # 友情链接
│   ├── notification/   # 通知模块
│   ├── point/          # 积分模块
│   ├── post/           # 帖子模块
│   ├── topic/          # 话题模块
│   └── user/           # 用户模块
├── core/               # 核心框架
├── install/            # 安装程序
├── plugins/            # 插件目录
├── static/             # 静态资源
├── templates/          # 模板文件
├── uploads/            # 上传目录
├── config.php          # 配置文件
└── index.php           # 入口文件
```

## 后台管理

访问 `/?r=admin` 进入后台管理页面，支持：

- 用户管理（查看、编辑、封禁）
- 帖子管理（删除、置顶、加精）
- 话题管理（置顶、屏蔽）
- 系统设置（站点配置、注册设置、邮件配置等）
- 插件管理

## 开发扩展

### 创建插件

1. 在 `plugins/` 目录创建插件文件夹
2. 创建 `Plugin.php` 主文件和 `info.json` 配置文件
3. 使用钩子系统注册事件

```php
<?php
namespace Plugin\demo;

use Hook;

class Plugin
{
    public function __construct()
    {
        Hook::register('post_after_publish', [$this, 'onPostPublish']);
    }
    
    public function onPostPublish($post)
    {
        // 处理逻辑
    }
}
```

### 可用钩子

- `app_start` - 应用启动
- `app_end` - 应用结束
- `user_login` - 用户登录
- `user_logout` - 用户登出
- `user_register` - 用户注册
- `post_before_publish` - 帖子发布前
- `post_after_publish` - 帖子发布后
- `before_render` - 视图渲染前

## 安全说明

- 密码采用 bcrypt + salt 加密存储
- CSRF Token 验证
- XSS 过滤
- SQL 注入防护（PDO 预处理）
- 文件上传安全检查
- 管理员二次密码验证

## 声明

⚠️ **严禁用于违法违规用途**

本软件仅供学习和研究使用，使用者需遵守当地法律法规。作者不对因使用本软件而产生的任何后果负责。

## 技术支持

- 官网：[https://huyourui.com](https://huyourui.com)
- QQ：281900864

## 开源协议

本项目基于 [MIT](LICENSE) 协议开源。

## 更新日志

### v1.16.0
- 新增后台用户名长度设置功能
- 优化话题页面发布框光标定位
- 修复后台设置保存问题

更多历史版本请查看 [Releases](https://github.com/your-repo/releases)。

---

感谢使用 HuSNS！如果觉得不错，欢迎 ⭐ Star 支持！

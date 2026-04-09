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
- 📦 **依赖注入** - 降低类之间耦合度
- 📋 **日志系统** - 完善的日志记录功能
- ⚠️ **异常处理** - 统一的异常处理机制
- 🧪 **单元测试** - 内置测试框架

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
│   ├── App.php         # 应用主类
│   ├── Container.php   # 依赖注入容器
│   ├── Controller.php  # 控制器基类
│   ├── Database.php    # 数据库抽象层
│   ├── ExceptionHandler.php  # 异常处理器
│   ├── Helper.php      # 工具函数
│   ├── Hook.php        # 钩子系统
│   ├── Logger.php      # 日志系统
│   ├── Model.php       # 模型基类
│   ├── Security.php    # 安全处理
│   ├── Setting.php     # 系统设置
│   ├── View.php        # 视图引擎
│   └── ...
├── install/            # 安装程序
├── plugins/            # 插件目录
├── static/             # 静态资源
├── templates/          # 模板文件
├── tests/              # 单元测试
├── uploads/            # 上传目录
├── logs/               # 日志目录
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

### 依赖注入

系统内置依赖注入容器，支持服务绑定和自动解析：

```php
// 绑定服务
Container::bind('service', function() {
    return new MyService();
});

// 单例绑定
Container::singleton('logger', Logger::getInstance());

// 获取实例
$service = Container::make('service');
```

### 日志记录

```php
// 记录不同级别的日志
Logger::info('用户登录成功', ['user_id' => 1]);
Logger::error('数据库错误', ['error' => $e->getMessage()]);
Logger::debug('请求参数', $_REQUEST);
```

### 单元测试

```bash
# 运行所有测试
php tests/run.php

# 运行指定测试
php tests/run.php Helper
```

## 安全说明

- 密码采用 bcrypt + salt 加密存储
- CSRF Token 验证
- XSS 过滤
- SQL 注入防护（PDO 预处理）
- 文件上传安全检查
- 管理员二次密码验证
- 统一异常处理机制

## 声明

⚠️ **严禁用于违法违规用途**

本软件仅供学习和研究使用，使用者需遵守当地法律法规。作者不对因使用本软件而产生的任何后果负责。

## 技术支持

- 官网：[https://huyourui.com](https://huyourui.com)
- QQ：281900864

## 开源协议

本项目基于 [MIT](LICENSE) 协议开源。

## 更新日志

### v2.2.3 (2024-01-XX)
- 🔒 **安全更新** - 修复多项安全漏洞
  - 修复IP欺骗漏洞，添加可信代理验证
  - 修复SQL注入风险，使用预处理语句
  - 修复会话固定攻击风险，登录后重新生成会话ID
  - 增强XSS过滤，移除更多危险标签和属性
  - 修复文件上传目录权限（0777→0755）
  - 增强附件MIME类型验证
  - 添加会话安全配置（HttpOnly、SameSite等）
  - 添加安全响应头（X-Frame-Options、X-XSS-Protection等）
  - 修复Cookie安全标志（Secure、SameSite）
- 📝 完善安全相关代码注释

### v2.2.2 (2024-01-XX)
- 🖼️ 优化用户头像存储逻辑，每个用户只保留一个头像文件
- 🖼️ 优化用户头像显示，文件不存在时自动显示首字符头像
- 🎨 首字符头像支持16种渐变配色，根据用户名自动分配
- 🔧 头像上传时自动删除旧头像文件，节省存储空间
- 📝 完善头像相关代码注释

### v2.2.0
- ✨ 新增依赖注入容器(Container)，降低类之间耦合度
- ✨ 新增日志系统(Logger)，支持多级别日志记录
- ✨ 新增异常处理器(ExceptionHandler)，统一异常处理
- ✨ 新增单元测试框架，支持基础测试用例
- ✨ 新增系统日志表(system_logs)
- 🔧 重构Controller基类，使用依赖注入获取服务
- 📝 完善代码注释，提高代码可读性
- 🐛 修复若干已知问题

### v2.1.0
- 新增收藏功能
- 优化帖子详情页展示
- 修复后台管理若干问题

### v2.0.0
- 全新MVC架构重构
- 新增插件系统
- 新增积分系统
- 优化前端UI

### v1.16.0
- 新增后台用户名长度设置功能
- 优化话题页面发布框光标定位
- 修复后台设置保存问题

更多历史版本请查看 [Releases](https://gitee.com/huyourui/husns/releases)。

---

感谢使用 HuSNS！如果觉得不错，欢迎 ⭐ Star 支持！

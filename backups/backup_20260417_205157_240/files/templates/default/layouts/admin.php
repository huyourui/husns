<?php
/**
 * HuSNS - 一款免费开源的社交平台
 * 
 * @author  HYR
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT
 * @声明    严禁用于违法违规用途
 */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title . ' - ' : ''; ?>后台管理</title>
    <link rel="stylesheet" href="<?php echo $this->asset('css/admin.css'); ?>">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>后台管理</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="<?php echo $this->url('admin'); ?>" class="<?php echo !isset($_GET['action']) ? 'active' : ''; ?>">仪表盘</a>
                <a href="<?php echo $this->url('admin/users'); ?>">用户管理</a>
                <a href="<?php echo $this->url('admin/posts'); ?>">动态管理</a>
                <a href="<?php echo $this->url('admin/comments'); ?>">评论管理</a>
                <a href="<?php echo $this->url('topic'); ?>">话题管理</a>
                <a href="<?php echo $this->url('invite'); ?>">邀请码管理</a>
                <a href="<?php echo $this->url('announcement'); ?>">公告管理</a>
                <a href="<?php echo $this->url('point'); ?>"><?php echo $this->escape(Setting::getPointName()); ?>管理</a>
                <a href="<?php echo $this->url('link'); ?>">友情链接</a>
                <a href="<?php echo $this->url('plugin'); ?>">插件管理</a>
                <a href="<?php echo $this->url('upgrade'); ?>">系统更新</a>
                <a href="<?php echo $this->url('admin/settings'); ?>">系统设置</a>
                <a href="<?php echo $this->url(); ?>">返回前台</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <span>欢迎，<?php echo $_SESSION['username'] ?? ''; ?></span>
                <a href="<?php echo $this->url('user/logout'); ?>">退出</a>
            </header>

            <div class="admin-content">
                <?php if ($this->hasFlash('success')): ?>
                <div class="alert alert-success"><?php echo $this->flash('success'); ?></div>
                <?php endif; ?>
                
                <?php if ($this->hasFlash('error')): ?>
                <div class="alert alert-error"><?php echo $this->flash('error'); ?></div>
                <?php endif; ?>

                <?php echo $content; ?>
            </div>
            
            <footer class="admin-footer">
                &copy; <?php echo date('Y'); ?> <?php echo $this->escape(Setting::getSiteName()); ?> | Powered by <a href="https://huyourui.com" target="_blank" rel="noopener">HuSNS</a>
            </footer>
        </main>
    </div>

    <script src="<?php echo $this->asset('js/admin.js'); ?>"></script>
</body>
</html>

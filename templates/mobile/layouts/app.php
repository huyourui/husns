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
$currentPage = $currentPage ?? 'home';
$bodyClass = $bodyClass ?? '';
$pageType = $pageType ?? '';
$pageData = $pageData ?? [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?php echo Setting::getTitle($title ?? ''); ?></title>
    <link rel="stylesheet" href="<?php echo $this->asset('css/mobile-app.css'); ?>">
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.body.classList.add('dark-mode');
            }
        })();
    </script>
</head>
<body class="mobile-body <?php echo $bodyClass; ?>" 
      data-base-url="<?php echo Helper::getBaseUrl(); ?>"
      data-page-type="<?php echo $pageType; ?>"
      <?php foreach ($pageData as $key => $value): ?>
      data-<?php echo $key; ?>="<?php echo $this->escape($value); ?>"
      <?php endforeach; ?>>
    
    <header class="m-header">
        <div class="m-header-content">
            <?php if (isset($showBack) && $showBack): ?>
            <a href="javascript:history.back()" class="m-header-back">←</a>
            <?php endif; ?>
            <h1 class="m-header-title"><?php echo $this->escape($headerTitle ?? Setting::getSiteName()); ?></h1>
            <div class="m-header-right">
                <?php if (isset($headerRight)): ?>
                <?php echo $headerRight; ?>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="m-main">
        <?php echo $content; ?>
    </main>

    <?php if (!isset($hideTabBar) || !$hideTabBar): ?>
    <nav class="m-tabbar">
        <a href="<?php echo $this->url('mobile'); ?>" class="m-tabbar-item <?php echo $currentPage === 'home' ? 'active' : ''; ?>">
            <span class="m-tabbar-icon">🏠</span>
            <span class="m-tabbar-label">首页</span>
        </a>
        <a href="<?php echo $this->url('mobile/hot'); ?>" class="m-tabbar-item <?php echo $currentPage === 'hot' ? 'active' : ''; ?>">
            <span class="m-tabbar-icon">🔥</span>
            <span class="m-tabbar-label">热门</span>
        </a>
        <a href="<?php echo $this->url('mobile/publish'); ?>" class="m-tabbar-item m-tabbar-publish">
            <span class="m-tabbar-icon-publish">+</span>
        </a>
        <a href="<?php echo $this->url('mobile/notification'); ?>" class="m-tabbar-item <?php echo $currentPage === 'notification' ? 'active' : ''; ?>">
            <span class="m-tabbar-icon">🔔</span>
            <span class="m-tabbar-label">消息</span>
            <span class="unread-badge" style="display:none;">0</span>
        </a>
        <a href="<?php echo $this->url('mobile/profile'); ?>" class="m-tabbar-item <?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
            <span class="m-tabbar-icon">👤</span>
            <span class="m-tabbar-label">我的</span>
        </a>
    </nav>
    <?php endif; ?>

    <div class="loading-indicator" style="display:none;">
        <div class="loading-spinner"></div>
    </div>

    <script src="<?php echo $this->asset('js/mobile-app.js'); ?>"></script>
</body>
</html>

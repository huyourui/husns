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
<html lang="<?php echo str_replace('_', '-', I18n::getCurrentLang()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Setting::getTitle($title ?? ''); ?></title>
    <meta name="keywords" content="<?php echo $this->escape(Setting::getKeywords()); ?>">
    <meta name="description" content="<?php echo $this->escape($description ?? Setting::getDescription()); ?>">
    <link rel="stylesheet" href="<?php echo $this->asset('css/style.css'); ?>">
    <?php $head = Hook::trigger('head', ''); echo is_string($head) ? $head : ''; ?>
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.body.classList.add('dark-mode');
            }
        })();
    </script>
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="<?php echo $this->url(); ?>" class="logo"><?php echo $this->escape(Setting::getSiteName()); ?></a>
            <nav class="nav">
                <a href="<?php echo $this->url(); ?>">首页</a>
                <a href="<?php echo $this->url('post/hot'); ?>">热门</a>
                <a href="<?php echo $this->url('post/featured'); ?>">推荐</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo $this->url('user/profile'); ?>">个人中心</a>
                <a href="<?php echo $this->url('notification'); ?>" class="notification-link" id="notificationLink">
                    消息
                    <span class="notification-badge" id="notificationBadge" style="display:none;">0</span>
                </a>
                <a href="<?php echo $this->url('user/settings'); ?>">设置</a>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <a href="<?php echo $this->url('admin'); ?>">后台管理</a>
                <?php endif; ?>
                <a href="<?php echo $this->url('user/logout'); ?>">退出</a>
                <?php else: ?>
                <a href="<?php echo $this->url('user/login'); ?>">登录</a>
                <a href="<?php echo $this->url('user/register'); ?>">注册</a>
                <?php endif; ?>
                <button class="theme-toggle" id="themeToggle" title="切换主题">🌙</button>
                
                <?php
                // 语言切换器
                $availableLangs = I18n::getAvailableLanguages();
                $currentLang = I18n::getCurrentLang();
                $langNames = [
                    'zh-cn' => '简',
                    'zh-tw' => '繁',
                    'en' => 'EN'
                ];
                if (count($availableLangs) > 1):
                ?>
                <div class="language-switcher">
                    <button class="language-toggle" id="languageToggle" title="切换语言">
                        <?php echo $langNames[$currentLang] ?? strtoupper($currentLang); ?>
                    </button>
                    <div class="language-dropdown" id="languageDropdown" style="display:none;">
                        <?php foreach ($availableLangs as $code => $info): ?>
                        <a href="?lang=<?php echo $code; ?>" class="language-option<?php echo $code === $currentLang ? ' active' : ''; ?>">
                            <?php echo $this->escape($info['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <?php if ($this->hasFlash('success')): ?>
            <div class="alert alert-success"><?php echo $this->flash('success'); ?></div>
            <?php endif; ?>
            
            <?php if ($this->hasFlash('error')): ?>
            <div class="alert alert-error"><?php echo $this->flash('error'); ?></div>
            <?php endif; ?>

            <?php echo $content; ?>
        </div>
    </main>

    <?php 
    $linkModel = new LinkModel();
    $links = $linkModel->getActive();
    ?>
    <footer class="footer">
        <div class="container">
            <?php if (!empty($links)): ?>
            <div class="footer-links">
                <span class="footer-links-label">友情链接：</span>
                <?php foreach ($links as $link): ?>
                <a href="<?php echo $this->escape($link['url']); ?>" target="_blank" rel="noopener"><?php echo $this->escape($link['name']); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <p class="copyright">&copy; <?php echo date('Y'); ?> <?php echo $this->escape(Setting::getSiteName()); ?>. All rights reserved. Powered by <a href="https://huyourui.com" target="_blank" rel="noopener">HuSNS</a></p>
            <?php 
            $icpNumber = Setting::getIcpNumber();
            if (!empty($icpNumber)): 
                $icpUrl = Setting::getIcpUrl();
            ?>
            <p class="icp-info"><a href="<?php echo $this->escape($icpUrl); ?>" target="_blank" rel="noopener"><?php echo $this->escape($icpNumber); ?></a></p>
            <?php endif; ?>
            <p class="mobile-link"><a href="javascript:void(0)" onclick="switchToMobile()">移动版</a></p>
        </div>
    </footer>

    <div class="repost-modal" id="repostModal" style="display:none;">
        <div class="repost-modal-content">
            <div class="repost-modal-header">
                <h3>转发微博</h3>
                <span class="repost-modal-close" onclick="closeRepostModal()">×</span>
            </div>
            <form id="repostForm">
                <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
                <input type="hidden" name="post_id" id="repostPostId">
                <textarea name="content" placeholder="说点什么吧..." rows="3" data-max-length="<?php echo Setting::getMaxPostLength(); ?>"></textarea>
                <div class="repost-modal-footer">
                    <label class="repost-checkbox">
                        <input type="checkbox" name="also_comment" value="1"> 同时评论
                    </label>
                    <div class="char-counter">
                        <span class="char-count">0</span>/<span><?php echo Setting::getMaxPostLength(); ?></span>
                    </div>
                    <button type="submit" class="btn btn-primary">转发</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="editModal" class="modal" style="display:none;">
        <div class="modal-content" style="width:600px;">
            <div class="modal-header">
                <h3>编辑微博</h3>
                <span class="modal-close" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="editForm">
                <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
                <input type="hidden" name="id" id="editPostId">
                <div class="form-group">
                    <textarea name="content" id="editContent" rows="5" style="width:100%;resize:vertical;" placeholder="请输入内容..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeEditModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        var BASE_URL = '<?php echo Helper::getBaseUrl(); ?>';
        
        function switchToMobile() {
            document.cookie = 'prefer_desktop=; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC';
            location.href = BASE_URL + '/?r=mobile';
        }
    </script>
    <script src="<?php echo $this->asset('js/app.js'); ?>"></script>
    <script>
        function updateNotificationCount() {
            fetch(BASE_URL + '/?r=notification/getUnreadCount')
                .then(response => response.json())
                .then(data => {
                    if (data.code === 0) {
                        const badge = document.getElementById('notificationBadge');
                        if (data.data.count > 0) {
                            badge.textContent = data.data.count > 99 ? '99+' : data.data.count;
                            badge.style.display = 'inline';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                });
        }
        
        <?php if (isset($_SESSION['user_id'])): ?>
        updateNotificationCount();
        setInterval(updateNotificationCount, 60000);
        <?php endif; ?>
    </script>
    <?php $footer = Hook::trigger('footer'); echo is_string($footer) ? $footer : ''; ?>
</body>
</html>

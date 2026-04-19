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
                <a href="<?php echo $this->url(); ?>"><?php echo $this->t('nav.home'); ?></a>
                <a href="<?php echo $this->url('post/hot'); ?>"><?php echo $this->t('nav.hot'); ?></a>
                <a href="<?php echo $this->url('post/featured'); ?>"><?php echo $this->t('common.recommend'); ?></a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo $this->url('user/profile'); ?>"><?php echo $this->t('nav.profile'); ?></a>
                <a href="<?php echo $this->url('notification'); ?>" class="notification-link" id="notificationLink">
                    <?php echo $this->t('nav.message'); ?>
                    <span class="notification-badge" id="notificationBadge" style="display:none;">0</span>
                </a>
                <a href="<?php echo $this->url('user/settings'); ?>"><?php echo $this->t('common.settings'); ?></a>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <a href="<?php echo $this->url('admin'); ?>"><?php echo $this->t('common.admin'); ?></a>
                <?php endif; ?>
                <a href="<?php echo $this->url('user/logout'); ?>"><?php echo $this->t('common.logout'); ?></a>
                <?php else: ?>
                <a href="<?php echo $this->url('user/login'); ?>"><?php echo $this->t('common.login'); ?></a>
                <a href="<?php echo $this->url('user/register'); ?>"><?php echo $this->t('common.register'); ?></a>
                <?php endif; ?>
                <button class="theme-toggle" id="themeToggle" title="<?php echo $this->t('common.theme'); ?>">🌙</button>
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
                <span class="footer-links-label"><?php echo $this->t('common.link'); ?>：</span>
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
            <p class="mobile-link"><a href="javascript:void(0)" onclick="switchToMobile()"><?php echo $this->t('common.mobile_version'); ?></a></p>
            
            <?php
            // 语言切换器（放在页脚）
            $availableLangs = I18n::getAvailableLanguages();
            $currentLang = I18n::getCurrentLang();
            $langNames = [
                'zh-cn' => '简体中文',
                'zh-tw' => '繁體中文',
                'en' => 'English'
            ];
            // 构建当前 URL（保留其他参数）
            $currentUrl = $_SERVER['REQUEST_URI'];
            // 移除已有的 lang 参数
            $currentUrl = preg_replace('/([?&])lang=[^&]+(&|$)/', '$1', $currentUrl);
            $currentUrl = rtrim($currentUrl, '?&');
            $separator = strpos($currentUrl, '?') === false ? '?' : '&';
            if (count($availableLangs) > 1):
            ?>
            <div class="footer-language">
                <span class="footer-language-label"><i class="lang-icon">🌐</i> <?php echo $this->t('common.language'); ?></span>
                <div class="lang-divider"></div>
                <?php 
                $langIndex = 0;
                foreach ($availableLangs as $code => $info): 
                    $langUrl = $currentUrl . $separator . 'lang=' . $code;
                ?>
                <a href="<?php echo $langUrl; ?>" class="footer-language-link<?php echo $code === $currentLang ? ' active' : ''; ?>">
                    <?php echo $this->escape($langNames[$code] ?? $info['name']); ?>
                </a>
                <?php 
                $langIndex++;
                endforeach; 
                ?>
            </div>
            <?php endif; ?>
        </div>
    </footer>

    <div class="repost-modal" id="repostModal" style="display:none;">
        <div class="repost-modal-content">
            <div class="repost-modal-header">
                <h3><?php echo $this->t('post.repost'); ?></h3>
                <span class="repost-modal-close" onclick="closeRepostModal()">×</span>
            </div>
            <form id="repostForm">
                <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
                <input type="hidden" name="post_id" id="repostPostId">
                <textarea name="content" placeholder="<?php echo $this->t('post.repost_placeholder'); ?>" rows="3" data-max-length="<?php echo Setting::getMaxPostLength(); ?>"></textarea>
                <div class="repost-modal-footer">
                    <label class="repost-checkbox">
                        <input type="checkbox" name="also_comment" value="1"> <?php echo $this->t('post.also_comment'); ?>
                    </label>
                    <div class="char-counter">
                        <span class="char-count">0</span>/<span><?php echo Setting::getMaxPostLength(); ?></span>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php echo $this->t('post.repost'); ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="editModal" class="modal" style="display:none;">
        <div class="modal-content" style="width:600px;">
            <div class="modal-header">
                <h3><?php echo $this->t('post.edit_post'); ?></h3>
                <span class="modal-close" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="editForm">
                <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
                <input type="hidden" name="id" id="editPostId">
                <div class="form-group">
                    <textarea name="content" id="editContent" rows="5" style="width:100%;resize:vertical;" placeholder="<?php echo $this->t('post.placeholder'); ?>"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeEditModal()"><?php echo $this->t('common.cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo $this->t('common.save'); ?></button>
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

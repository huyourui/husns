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
<div class="m-profile-header">
    <?php echo $this->avatar($user['avatar'] ?? null, $user['username'] ?? '', 'large'); ?>
    <div class="m-profile-username"><?php echo $this->escape($user['username']); ?></div>
    <?php if (!empty($user['bio'])): ?>
    <div class="m-profile-bio"><?php echo $this->escape($user['bio']); ?></div>
    <?php endif; ?>
    <div class="m-profile-stats">
        <div class="m-profile-stat">
            <div class="m-profile-stat-num"><?php echo $postCount; ?></div>
            <div class="m-profile-stat-label">微博</div>
        </div>
        <div class="m-profile-stat">
            <div class="m-profile-stat-num"><?php echo $followCount['following'] ?? 0; ?></div>
            <div class="m-profile-stat-label">关注</div>
        </div>
        <div class="m-profile-stat">
            <div class="m-profile-stat-num"><?php echo $followCount['followers'] ?? 0; ?></div>
            <div class="m-profile-stat-label">粉丝</div>
        </div>
    </div>
</div>

<div class="m-menu-list">
    <a href="<?php echo $this->url('mobile/settings'); ?>" class="m-menu-item">
        <div class="m-menu-left">
            <span class="m-menu-icon">⚙️</span>
            <span class="m-menu-text">设置</span>
        </div>
        <span class="m-menu-arrow">›</span>
    </a>
    <a href="<?php echo $this->url('user/favorites'); ?>" class="m-menu-item">
        <div class="m-menu-left">
            <span class="m-menu-icon">⭐</span>
            <span class="m-menu-text">收藏</span>
        </div>
        <span class="m-menu-arrow">›</span>
    </a>
    <a href="<?php echo $this->url('mobile/logout'); ?>" class="m-menu-item">
        <div class="m-menu-left">
            <span class="m-menu-icon">🚪</span>
            <span class="m-menu-text">退出登录</span>
        </div>
        <span class="m-menu-arrow">›</span>
    </a>
</div>

<div style="padding:10px 15px;font-weight:600;color:var(--text-secondary);font-size:13px;">我的微博</div>

<div class="m-post-list">
    <?php if (empty($posts)): ?>
    <div class="m-empty">
        <div class="m-empty-icon">📭</div>
        <div class="m-empty-text">还没有发布微博</div>
    </div>
    <?php else: ?>
    <?php foreach ($posts as $post): ?>
    <div class="m-post-item" data-id="<?php echo $post['id']; ?>">
        <a href="<?php echo $this->url('mobile/detail?id=' . $post['id']); ?>" style="text-decoration:none;color:inherit;">
            <div class="m-post-content">
                <?php echo $post['content']; ?>
            </div>
            <?php if (!empty($post['images'])): ?>
            <div class="m-post-images many" style="margin-bottom:10px;">
                <?php foreach (array_slice($post['images'], 0, 3) as $img): ?>
                <img class="m-post-image" src="<?php echo $this->uploadUrl($img); ?>" alt="" loading="lazy">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </a>
        <div class="m-post-actions">
            <span class="m-action-btn">
                <span class="m-action-icon">🤍</span>
                <span class="m-action-count"><?php echo $post['likes']; ?></span>
            </span>
            <span class="m-action-btn">
                <span class="m-action-icon">💬</span>
                <span class="m-action-count"><?php echo $post['comments']; ?></span>
            </span>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (count($posts) >= 10): ?>
<div class="m-load-more">
    <a href="<?php echo $this->url('mobile/profile?page=' . ($page + 1)); ?>" style="color:var(--primary-color);text-decoration:none;">加载更多</a>
</div>
<?php endif; ?>

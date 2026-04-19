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
    <?php echo $this->avatar($targetUser['avatar'] ?? null, $targetUser['username'] ?? '', 'large'); ?>
    <div class="m-profile-username"><?php echo $this->escape($targetUser['username']); ?></div>
    <?php if (!empty($targetUser['bio'])): ?>
    <div class="m-profile-bio"><?php echo $this->escape($targetUser['bio']); ?></div>
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
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $targetUser['id']): ?>
    <div style="margin-top:15px;">
        <button class="m-btn <?php echo $isFollowing ? 'm-btn-secondary' : 'm-btn-primary'; ?>" style="padding:8px 30px;" onclick="toggleFollow(<?php echo $targetUser['id']; ?>, this)">
            <?php echo $isFollowing ? '已关注' : '关注'; ?>
        </button>
    </div>
    <?php endif; ?>
</div>

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
    <a href="<?php echo $this->url('mobile/user?id=' . $targetUser['id'] . '&page=' . ($page + 1)); ?>" style="color:var(--primary-color);text-decoration:none;">加载更多</a>
</div>
<?php endif; ?>

<script>
function toggleFollow(userId, btn) {
    var formData = new FormData();
    formData.append('user_id', userId);
    formData.append('csrf_token', CSRF_TOKEN);
    
    fetch(BASE_URL + '/?r=user/toggleFollow', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.code === 0) {
            if (data.data.following) {
                btn.textContent = '已关注';
                btn.classList.remove('m-btn-primary');
                btn.classList.add('m-btn-secondary');
            } else {
                btn.textContent = '关注';
                btn.classList.remove('m-btn-secondary');
                btn.classList.add('m-btn-primary');
            }
        }
    });
}
</script>

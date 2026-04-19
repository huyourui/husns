<?php
$currentPage = 'profile';
$pageType = 'profile';
$bodyClass = 'profile-page';
$this->setLayout('app');
?>
<div class="user-profile-header">
    <div class="user-avatar" id="userAvatar">
        <div class="avatar-placeholder">?</div>
    </div>
    <div class="user-info">
        <div class="user-name" id="userName">加载中...</div>
        <div class="user-stats" id="userStats"></div>
    </div>
</div>

<!-- 功能菜单 -->
<div class="profile-menu">
    <a href="<?php echo $this->url('mobile/favorites'); ?>" class="menu-item">
        <span class="menu-icon">⭐</span>
        <span class="menu-text">我的收藏</span>
        <span class="menu-arrow">›</span>
    </a>
    <a href="<?php echo $this->url('mobile/follows'); ?>" class="menu-item">
        <span class="menu-icon">👥</span>
        <span class="menu-text">我的关注</span>
        <span class="menu-arrow">›</span>
    </a>
    <a href="<?php echo $this->url('mobile/fans'); ?>" class="menu-item">
        <span class="menu-icon">👤</span>
        <span class="menu-text">我的粉丝</span>
        <span class="menu-arrow">›</span>
    </a>
</div>

<!-- 退出登录按钮 -->
<div class="logout-section">
    <button class="logout-btn" id="logoutBtn">退出登录</button>
</div>

<div class="post-list" id="postList"></div>

<script>
// 退出登录功能
document.getElementById('logoutBtn').addEventListener('click', function() {
    window.location.href = '<?php echo $this->url('mobile/logout'); ?>';
});
</script>

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
<div class="post-list" id="postList"></div>

<?php
$pageType = 'user';
$bodyClass = 'user-page';
$hideTabBar = false;
$showBack = true;
$headerTitle = $username ?? '';
$pageData = ['user-id' => $userId ?? '', 'username' => $username ?? ''];
$this->setLayout('app');
?>
<div id="userProfile" class="user-profile-header"></div>
<div class="post-list" id="postList"></div>

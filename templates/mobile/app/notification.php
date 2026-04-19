<?php
$currentPage = 'notification';
$pageType = 'notification';
$bodyClass = 'notification-page';
$this->setLayout('app');
?>
<div class="notification-header">
    <span>消息通知</span>
    <button class="mark-all-read">全部已读</button>
</div>
<div class="notification-list" id="notificationList"></div>

<?php
$currentPage = 'notification';
$pageType = 'notification';
$bodyClass = 'notification-page';
$this->setLayout('app');
?>
<div class="notification-header">
    <a href="javascript:void(0)" id="markAllRead" class="mark-all-read">全部已读</a>
</div>
<div class="notification-list" id="notificationList">
    <div class="loading-state">
        <div class="loading-spinner"></div>
        加载中...
    </div>
</div>
<button class="load-more-btn" id="loadMoreBtn" style="display:none;">加载更多</button>

<script src="<?php echo $this->asset('js/mobile-notification.js'); ?>"></script>

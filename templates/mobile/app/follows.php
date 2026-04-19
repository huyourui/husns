<?php
$currentPage = 'follows';
$pageType = 'follows';
$bodyClass = 'follows-page';
$this->setLayout('app');
?>
<div class="follows-list" id="followsList">
    <div class="loading-state">
        <div class="loading-spinner"></div>
        加载中...
    </div>
</div>
<button class="load-more-btn" id="loadMoreBtn" style="display:none;">加载更多</button>

<script src="<?php echo $this->asset('js/mobile-follows.js'); ?>"></script>

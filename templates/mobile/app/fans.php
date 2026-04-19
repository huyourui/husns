<?php
$currentPage = 'fans';
$pageType = 'fans';
$bodyClass = 'fans-page';
$this->setLayout('app');
?>
<div class="fans-list" id="fansList">
    <div class="loading-state">
        <div class="loading-spinner"></div>
        加载中...
    </div>
</div>
<button class="load-more-btn" id="loadMoreBtn" style="display:none;">加载更多</button>

<script src="<?php echo $this->asset('js/mobile-fans.js'); ?>"></script>

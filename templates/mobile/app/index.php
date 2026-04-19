<?php
$currentPage = 'home';
$pageType = 'home';
$bodyClass = 'home-page';
$tab = isset($tab) ? $tab : 'all';
$this->setLayout('app');
?>
<div class="tab-bar">
    <a href="<?php echo $this->url('mobile'); ?>" class="tab-item<?php echo ($tab !== 'following') ? ' active' : ''; ?>" data-tab="all">全部</a>
    <a href="<?php echo $this->url('mobile', ['tab' => 'following']); ?>" class="tab-item<?php echo ($tab === 'following') ? ' active' : ''; ?>" data-tab="following">关注</a>
</div>
<div class="post-list" id="postList">
    <div class="loading-state">
        <div class="loading-spinner"></div>
        加载中...
    </div>
</div>
<button class="load-more-btn" id="loadMoreBtn" style="display:none;">加载更多</button>

<script src="<?php echo $this->asset('js/mobile-home.js'); ?>?v=4.0.4"></script>

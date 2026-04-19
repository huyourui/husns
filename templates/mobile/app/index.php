<?php
$currentPage = 'home';
$pageType = 'home';
$bodyClass = 'home-page';
$this->setLayout('app');
?>
<div class="tab-bar">
    <a href="javascript:void(0)" class="tab-item <?php echo (!isset($tab) || $tab === 'all') ? 'active' : ''; ?>" data-tab="all">全部</a>
    <a href="javascript:void(0)" class="tab-item <?php echo (isset($tab) && $tab === 'following') ? 'active' : ''; ?>" data-tab="following">关注</a>
</div>
<div class="post-list" id="postList"></div>
<button class="load-more-btn" style="display:none;">加载更多</button>

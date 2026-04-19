<?php
$currentPage = 'repost';
$pageType = 'repost';
$bodyClass = 'repost-page';
$this->setLayout('app');

// 从URL获取微博ID
$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<!-- 原文预览区域 -->
<div class="original-post-preview" id="originalPostPreview">
    <div class="loading-state">
        <div class="loading-spinner"></div>
        加载中...
    </div>
</div>

<!-- 转发输入区域 -->
<div class="repost-form-container">
    <form id="repostForm" class="repost-form">
        <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
        <textarea name="content" placeholder="说说你的看法..." class="repost-textarea" rows="4"></textarea>
        <div class="repost-actions">
            <button type="submit" class="submit-btn">转发</button>
        </div>
    </form>
</div>

<script src="<?php echo $this->asset('js/mobile-repost.js'); ?>"></script>

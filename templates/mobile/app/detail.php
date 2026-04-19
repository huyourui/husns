<?php
$currentPage = 'detail';
$pageType = 'detail';
$bodyClass = 'detail-page';
$this->setLayout('app');

// 从URL获取微博ID
$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>

<!-- 微博详情容器 -->
<div class="post-detail-container" id="postDetail" data-post-id="<?php echo $postId; ?>">
    <div class="loading-state">
        <div class="loading-spinner"></div>
        加载中...
    </div>
</div>

<!-- 评论区域 -->
<div class="comment-section">
    <h3>评论</h3>
    <div class="comment-list" id="commentList">
        <div class="loading-state">
            <div class="loading-spinner"></div>
            加载中...
        </div>
    </div>
</div>

<!-- 评论输入框 -->
<div class="comment-form-container">
    <form id="commentForm" class="comment-form">
        <input type="text" name="content" placeholder="发表评论..." class="comment-input">
        <button type="submit" class="submit-btn">发送</button>
    </form>
</div>

<script src="<?php echo $this->asset('js/mobile-detail.js'); ?>"></script>

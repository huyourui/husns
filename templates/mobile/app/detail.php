<?php
$pageType = 'detail';
$bodyClass = 'detail-page';
$hideTabBar = false;
$showBack = true;
$headerTitle = '微博详情';
$pageData = ['post-id' => $id];
$this->setLayout('app');
?>
<div id="postDetail" class="post-detail"></div>
<div class="comment-section">
    <div class="comment-section-title">评论</div>
    <div id="commentList" class="comment-list"></div>
    <form class="comment-form">
        <input type="text" name="content" placeholder="发表评论...">
        <input type="hidden" name="post_id" value="<?php echo $id; ?>">
        <button type="submit">发送</button>
    </form>
</div>

<?php
/**
 * HuSNS - 一款免费开源的社交平台
 * 
 * @author  HYR
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT
 * @声明    严禁用于违法违规用途
 */
?>
<div class="page-topic">
    <div class="topic-header">
        <h1>#<?php echo $this->escape($keyword); ?>#</h1>
        <p class="topic-count">共 <?php echo $total; ?> 条动态</p>
    </div>
    
    <?php echo $this->partial('partials/publish', ['topic' => $keyword]); ?>
    
    <div class="post-list">
        <?php if (empty($posts)): ?>
        <div class="empty">暂无相关动态</div>
        <?php else: ?>
        <?php foreach ($posts as $post): ?>
        <div class="post-item" data-id="<?php echo $post['id']; ?>">
            <div class="post-avatar">
                <a href="<?php echo $this->url('user/profile?id=' . $post['user_id']); ?>">
                    <?php echo $this->avatar($post['avatar'] ?? null, $post['username']); ?>
                </a>
            </div>
            <div class="post-content">
                <div class="post-header">
                    <a href="<?php echo $this->url('user/profile?id=' . $post['user_id']); ?>" class="username">
                        <?php echo $this->escape($post['username']); ?>
                    </a>
                    <?php if (!empty($post['repost_id'])): ?>
                    <span class="repost-label">转发</span>
                    <?php endif; ?>
                    <a href="<?php echo $this->url('post/detail?id=' . $post['id']); ?>" class="time"><?php echo $post['time_ago']; ?></a>
                </div>
                
                <?php if (!empty($post['content'])): ?>
                <div class="post-text"><?php echo $post['content']; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($post['original_post'])): ?>
                <div class="repost-box">
                    <?php if (!empty($post['original_post']['deleted'])): ?>
                    <div class="repost-deleted">原文已删除</div>
                    <?php else: ?>
                    <div class="repost-header">
                        <a href="<?php echo $this->url('user/profile?id=' . $post['original_post']['user_id']); ?>" class="username">@<?php echo $this->escape($post['original_post']['username']); ?></a>
                    </div>
                    <div class="repost-content"><?php echo $post['original_post']['content']; ?></div>
                    <?php if (!empty($post['original_post']['images'])): ?>
                    <?php echo Helper::renderImageGrid($post['original_post']['images']); ?>
                    <?php endif; ?>
                    <?php if (!empty($post['original_post']['attachments'])): ?>
                    <div class="post-attachments">
                        <div class="post-attachments-title">📎 附件</div>
                        <?php foreach ($post['original_post']['attachments'] as $index => $attachment): ?>
                        <a href="<?php echo $this->url('download/attachment?id=' . $post['original_post']['id'] . '&index=' . $index); ?>" class="post-attachment-item">
                            <span class="post-attachment-icon">📄</span>
                            <span class="post-attachment-name"><?php echo $this->escape($attachment['name']); ?></span>
                            <span class="post-attachment-size"><?php echo $this->formatFileSize($attachment['size']); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php elseif (empty($post['repost_id'])): ?>
                <?php if (!empty($post['images'])): ?>
                <?php echo Helper::renderImageGrid($post['images']); ?>
                <?php endif; ?>
                <?php if (!empty($post['attachments'])): ?>
                <div class="post-attachments">
                    <div class="post-attachments-title">📎 附件</div>
                    <?php foreach ($post['attachments'] as $index => $attachment): ?>
                    <a href="<?php echo $this->url('download/attachment?id=' . $post['id'] . '&index=' . $index); ?>" class="post-attachment-item">
                        <span class="post-attachment-icon">📄</span>
                        <span class="post-attachment-name"><?php echo $this->escape($attachment['name']); ?></span>
                        <span class="post-attachment-size"><?php echo $this->formatFileSize($attachment['size']); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php echo $this->partial('partials/post-actions', ['post' => $post]); ?>
                <?php echo CommentHelper::renderCommentBox($post['id']); ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if (count($posts) == $pageSize): ?>
    <div class="load-more">
        <a href="<?php echo $this->url('post/topic?keyword=' . urlencode($keyword) . '&page=' . ($page + 1)); ?>" class="btn">加载更多</a>
    </div>
    <?php endif; ?>
</div>

<div class="repost-modal" id="repostModal" style="display:none;">
    <div class="repost-modal-content">
        <div class="repost-modal-header">
            <h3>转发微博</h3>
            <span class="repost-modal-close" onclick="closeRepostModal()">×</span>
        </div>
        <form id="repostForm">
            <?php echo $this->csrf(); ?>
            <input type="hidden" name="post_id" id="repostPostId">
            <textarea name="content" placeholder="说点什么吧..." rows="3" data-max-length="<?php echo Setting::getMaxPostLength(); ?>"></textarea>
            <div class="repost-modal-footer">
                <label class="repost-checkbox">
                    <input type="checkbox" name="also_comment" value="1"> 同时评论
                </label>
                <div class="char-counter">
                    <span class="char-count">0</span>/<span><?php echo Setting::getMaxPostLength(); ?></span>
                </div>
                <button type="submit" class="btn btn-primary">转发</button>
            </div>
        </form>
    </div>
</div>

<?php
?>
<div class="page-hot">
    <?php if (empty($posts)): ?>
    <div class="empty-state">
        <p>暂无热门动态</p>
    </div>
    <?php else: ?>
    <div class="post-list">
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
                    <?php if (!empty($post['is_featured'])): ?>
                    <span class="featured-tag">精华</span>
                    <?php endif; ?>
                    <?php if (!empty($post['repost_id'])): ?>
                    <span class="repost-label">转发</span>
                    <?php endif; ?>
                    <span class="hot-score">🔥 <?php echo $post['hot_score']; ?></span>
                </div>
                
                <?php if (!empty($post['content'])): ?>
                <div class="post-text"><?php echo $post['content']; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($post['images'])): ?>
                <?php echo Helper::renderImageGrid($post['images']); ?>
                <?php endif; ?>
                
                <?php if (!empty($post['videos'])): ?>
                <div class="post-videos">
                    <?php foreach ($post['videos'] as $video): ?>
                    <div class="post-video-item" onclick="playVideo(this)">
                        <video preload="metadata" playsinline>
                            <source src="<?php echo $this->uploadUrl($video['path']); ?>#t=3" type="video/<?php echo $video['ext']; ?>">
                        </video>
                        <div class="video-overlay">
                            <div class="video-play-btn">▶</div>
                        </div>
                        <div class="video-name-tag"><?php echo $this->escape($video['name']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($post['attachments'])): ?>
                <div class="post-attachments">
                    <?php foreach ($post['attachments'] as $attachment): ?>
                    <a href="<?php echo $this->url('download/file?id=' . $post['id'] . '&file=' . urlencode($attachment['path'])); ?>" class="attachment-item" target="_blank">
                        <span class="attachment-icon">📎</span>
                        <span class="attachment-name"><?php echo $this->escape($attachment['name']); ?></span>
                        <span class="attachment-size"><?php echo $this->formatFileSize($attachment['size']); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="post-footer">
                    <span class="post-time"><?php echo $post['time_ago']; ?></span>
                </div>
                
                <?php echo PostActionHelper::render($post); ?>
                <?php echo CommentHelper::renderCommentBox($post['id']); ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="<?php echo $this->url('post/hot?page=' . ($page - 1)); ?>" class="prev">上一页</a>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <a href="<?php echo $this->url('post/hot?page=' . $i); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
        <a href="<?php echo $this->url('post/hot?page=' . ($page + 1)); ?>" class="next">下一页</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

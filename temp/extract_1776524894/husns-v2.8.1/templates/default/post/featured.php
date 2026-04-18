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
<div class="page-featured">
    <div class="featured-header">
        <h1 class="featured-title">🌟 精华推荐</h1>
        <p class="featured-desc">精选优质内容，发现更多精彩</p>
    </div>

    <div class="post-list">
        <?php if (empty($posts)): ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <p>暂无精华内容</p>
            <small>精彩内容正在路上，敬请期待...</small>
        </div>
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
                    <span class="featured-tag">精华</span>
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
                    <?php if (!empty($post['original_post']['videos'])): ?>
                    <div class="post-videos">
                        <?php foreach ($post['original_post']['videos'] as $video): ?>
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
                <?php if (!empty($post['videos'])): ?>
                <div class="post-videos">
                    <?php foreach ($post['videos'] as $video): ?>
                    <div class="post-video-item" onclick="playVideo(this)">
                        <video preload="metadata" playsinline>
                            <source src="<?php echo $this->uploadUrl($video['path']); ?>#t=3" type="video/<?php echo $video['ext']; ?>">
                            您的浏览器不支持视频播放
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

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="<?php echo $this->url('post/featured?page=' . ($page - 1)); ?>" class="btn">上一页</a>
        <?php endif; ?>
        
        <span class="page-info">第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页</span>
        
        <?php if ($page < $totalPages): ?>
        <a href="<?php echo $this->url('post/featured?page=' . ($page + 1)); ?>" class="btn btn-primary">下一页</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.featured-header {
    text-align: center;
    padding: 32px 20px;
    background: #fff;
    border-radius: 12px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.featured-title {
    font-size: 24px;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #1e293b;
}

.featured-desc {
    font-size: 14px;
    color: #64748b;
    margin: 0;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.empty-state p {
    font-size: 16px;
    color: #64748b;
    margin: 0 0 8px 0;
}

.empty-state small {
    color: #94a3b8;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 24px;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.page-info {
    color: #64748b;
    font-size: 14px;
}

body.dark-mode .featured-header {
    background: #1e293b;
}

body.dark-mode .featured-title {
    color: #f1f5f9;
}

body.dark-mode .featured-desc {
    color: #94a3b8;
}

body.dark-mode .empty-state,
body.dark-mode .pagination {
    background: #1e293b;
}

body.dark-mode .page-info {
    color: #94a3b8;
}
</style>

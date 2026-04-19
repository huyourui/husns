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
<div class="page-home">
    <?php if (isset($_SESSION['user_id']) && $userStats): ?>
    <div class="home-layout">
        <div class="home-sidebar">
            <div class="user-card">
                <div class="user-card-avatar">
                    <?php echo $this->avatar($userStats['user']['avatar'] ?? null, $userStats['user']['username'] ?? '', 80); ?>
                </div>
                <div class="user-card-name">
                    <?php echo $this->escape($userStats['user']['username'] ?? ''); ?>
                </div>
                <div class="user-card-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($userStats['post_count']); ?></div>
                        <div class="stat-label"><?php echo $this->t('common.posts'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($userStats['total_engagement']); ?></div>
                        <div class="stat-label"><?php echo $this->t('common.engagement'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($userStats['points']); ?></div>
                        <div class="stat-label"><?php echo $this->escape(Setting::getPointName()); ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($hotTopics) && Setting::isHotTopicsEnabled()): ?>
            <div class="hot-topics-card">
                <div class="hot-topics-title">🔥 <?php echo $this->t('common.hot_topics'); ?></div>
                <div class="hot-topics-list">
                    <?php foreach ($hotTopics as $index => $topic): ?>
                    <a href="<?php echo $this->url('post/topic?keyword=' . urlencode($topic['name'])); ?>" class="hot-topic-item" title="<?php echo $this->escape($topic['name']); ?>">
                        <span class="topic-rank rank-<?php echo $index + 1; ?>"><?php echo $index + 1; ?></span>
                        <span class="topic-name"><?php echo $this->escape($topic['name']); ?></span>
                        <span class="topic-heat"><?php echo $topic['count']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="home-main">
            <?php echo $this->partial('partials/publish'); ?>
            
            <?php if (!empty($announcements)): ?>
            <div class="announcement-bar">
                <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-item announcement-<?php echo $announcement['color']; ?>">
                    <?php echo $announcement['content']; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="timeline-tabs">
                <a href="<?php echo $this->url('?tab=following'); ?>" class="tab-item <?php echo $tab === 'following' ? 'active' : ''; ?>"><?php echo $this->t('common.following'); ?></a>
                <a href="<?php echo $this->url('?tab=all'); ?>" class="tab-item <?php echo $tab === 'all' ? 'active' : ''; ?>"><?php echo $this->t('common.all_posts'); ?></a>
            </div>

            <?php if (!empty($pinnedPost)): ?>
            <div class="post-item pinned-post" data-id="<?php echo $pinnedPost['id']; ?>">
                <div class="post-avatar">
                    <a href="<?php echo $this->url('user/profile?id=' . $pinnedPost['user_id']); ?>">
                        <?php echo $this->avatar($pinnedPost['avatar'] ?? null, $pinnedPost['username']); ?>
                    </a>
                </div>
                <div class="post-content">
                    <div class="post-header">
                        <a href="<?php echo $this->url('user/profile?id=' . $pinnedPost['user_id']); ?>" class="username"><?php echo $this->escape($pinnedPost['username']); ?></a>
                        <span class="pinned-tag"><?php echo $this->t('post.pinned'); ?></span>
                        <?php if (!empty($pinnedPost['is_featured'])): ?>
                        <span class="featured-tag"><?php echo $this->t('post.featured'); ?></span>
                        <?php endif; ?>
                        <a href="<?php echo $this->url('post/detail?id=' . $pinnedPost['id']); ?>" class="time"><?php echo Helper::formatTime($pinnedPost['created_at']); ?></a>
                    </div>
                    <div class="post-text"><?php echo $pinnedPost['content']; ?></div>
                    <?php if (!empty($pinnedPost['images'])): ?>
                    <?php echo Helper::renderImageGrid($pinnedPost['images']); ?>
                    <?php endif; ?>
                    <?php if (!empty($pinnedPost['videos'])): ?>
                    <div class="post-videos">
                        <?php foreach ($pinnedPost['videos'] as $video): ?>
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
                    <?php if (!empty($pinnedPost['attachments'])): ?>
                    <div class="post-attachments">
                        <div class="post-attachments-title">📎 <?php echo $this->t('post.attachments'); ?></div>
                        <?php foreach ($pinnedPost['attachments'] as $index => $attachment): ?>
                        <a href="<?php echo $this->url('download/attachment?id=' . $pinnedPost['id'] . '&index=' . $index); ?>" class="post-attachment-item">
                            <span class="post-attachment-icon">📄</span>
                            <span class="post-attachment-name"><?php echo $this->escape($attachment['name']); ?></span>
                            <span class="post-attachment-size"><?php echo $this->formatFileSize($attachment['size']); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php echo $this->partial('partials/post-actions', ['post' => $pinnedPost]); ?>
                    <?php echo CommentHelper::renderCommentBox($pinnedPost['id']); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="post-list">
                <?php if (empty($posts)): ?>
                <?php if ($tab === 'following'): ?>
                <div class="empty">
                    <p><?php echo $this->t('post.no_following_content'); ?></p>
                    <p><a href="<?php echo $this->url('?tab=all'); ?>"><?php echo $this->t('post.view_all_posts'); ?></a> <?php echo $this->t('post.discover_more'); ?></p>
                </div>
                <?php else: ?>
                <div class="empty"><?php echo $this->t('post.empty_timeline'); ?></div>
                <?php endif; ?>
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
                            <?php if (!empty($post['is_featured'])): ?>
                            <span class="featured-tag">精华</span>
                            <?php endif; ?>
                            <?php if (!empty($post['repost_id'])): ?>
                            <span class="repost-label"><?php echo $this->t('post.repost'); ?></span>
                            <?php endif; ?>
                            <a href="<?php echo $this->url('post/detail?id=' . $post['id']); ?>" class="time"><?php echo $post['time_ago']; ?></a>
                        </div>
                        
                        <?php if (!empty($post['content'])): ?>
                        <div class="post-text"><?php echo $post['content']; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($post['original_post'])): ?>
                        <div class="repost-box">
                            <?php if (!empty($post['original_post']['deleted'])): ?>
                            <div class="repost-deleted"><?php echo $this->t('post.original_deleted'); ?></div>
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
                                    <?php echo $this->t('post.video_not_supported'); ?>
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

            <?php if (count($posts) == $pageSize): ?>
            <div class="load-more">
                <a href="<?php echo $this->url('?page=' . ($page + 1)); ?>" class="btn"><?php echo $this->t('common.load_more'); ?></a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <?php echo $this->partial('partials/publish'); ?>
    
    <?php if (!empty($announcements)): ?>
    <div class="announcement-bar">
        <?php foreach ($announcements as $announcement): ?>
        <div class="announcement-item announcement-<?php echo $announcement['color']; ?>">
            <?php echo $announcement['content']; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($pinnedPost)): ?>
    <div class="post-item pinned-post" data-id="<?php echo $pinnedPost['id']; ?>">
        <div class="post-avatar">
            <a href="<?php echo $this->url('user/profile?id=' . $pinnedPost['user_id']); ?>">
                <?php echo $this->avatar($pinnedPost['avatar'] ?? null, $pinnedPost['username']); ?>
            </a>
        </div>
        <div class="post-content">
            <div class="post-header">
                <a href="<?php echo $this->url('user/profile?id=' . $pinnedPost['user_id']); ?>" class="username"><?php echo $this->escape($pinnedPost['username']); ?></a>
                <span class="pinned-tag">置顶</span>
                <?php if (!empty($pinnedPost['is_featured'])): ?>
                <span class="featured-tag">精华</span>
                <?php endif; ?>
                <a href="<?php echo $this->url('post/detail?id=' . $pinnedPost['id']); ?>" class="time"><?php echo Helper::formatTime($pinnedPost['created_at']); ?></a>
            </div>
            <div class="post-text"><?php echo $pinnedPost['content']; ?></div>
            <?php if (!empty($pinnedPost['images'])): ?>
            <?php echo Helper::renderImageGrid($pinnedPost['images']); ?>
            <?php endif; ?>
            <?php if (!empty($pinnedPost['videos'])): ?>
            <div class="post-videos">
                <?php foreach ($pinnedPost['videos'] as $video): ?>
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
            <?php if (!empty($pinnedPost['attachments'])): ?>
            <div class="post-attachments">
                <div class="post-attachments-title">📎 附件</div>
                <?php foreach ($pinnedPost['attachments'] as $index => $attachment): ?>
                <a href="<?php echo $this->url('download/attachment?id=' . $pinnedPost['id'] . '&index=' . $index); ?>" class="post-attachment-item">
                    <span class="post-attachment-icon">📄</span>
                    <span class="post-attachment-name"><?php echo $this->escape($attachment['name']); ?></span>
                    <span class="post-attachment-size"><?php echo $this->formatFileSize($attachment['size']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="post-actions">
                <span class="action-btn comment-toggle" data-id="<?php echo $pinnedPost['id']; ?>" data-comments="<?php echo $pinnedPost['comments'] ?? 0; ?>">评论(<?php echo $pinnedPost['comments'] ?? 0; ?>)</span>
                <span class="action-btn like-btn <?php echo isset($pinnedPost['is_liked']) && $pinnedPost['is_liked'] ? 'liked' : ''; ?>" data-id="<?php echo $pinnedPost['id']; ?>">点赞(<?php echo $pinnedPost['likes'] ?? 0; ?>)</span>
                <span class="action-btn repost-btn" data-id="<?php echo $pinnedPost['id']; ?>">转发(<?php echo $pinnedPost['reposts'] ?? 0; ?>)</span>
            </div>
            <?php echo CommentHelper::renderCommentBox($pinnedPost['id']); ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="post-list">
        <?php if (empty($posts)): ?>
        <div class="empty">暂无动态，快来发布第一条吧！</div>
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
                    <?php if (!empty($post['is_featured'])): ?>
                    <span class="featured-tag">精华</span>
                    <?php endif; ?>
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
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (count($posts) == $pageSize): ?>
    <div class="load-more">
        <a href="<?php echo $this->url('?page=' . ($page + 1)); ?>" class="btn">加载更多</a>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.home-layout {
    display: flex;
    gap: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.home-sidebar {
    width: 260px;
    flex-shrink: 0;
}

.home-main {
    flex: 1;
    min-width: 0;
}

.user-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    position: sticky;
    top: 20px;
}

.user-card-avatar {
    text-align: center;
    margin-bottom: 12px;
}

.user-card-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #f1f5f9;
}

.user-card-avatar .avatar-default {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    margin: 0 auto;
    border: 3px solid #f1f5f9;
}

.user-card-name {
    text-align: center;
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
}

.user-card-stats {
    display: flex;
    justify-content: space-around;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}

.stat-label {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}

@media (max-width: 900px) {
    .home-layout {
        flex-direction: column;
    }
    
    .home-sidebar {
        width: 100%;
    }
    
    .user-card {
        position: static;
    }
    
    .user-card-stats {
        justify-content: center;
        gap: 40px;
    }
}

body.dark-mode .user-card {
    background: #1e293b;
}

body.dark-mode .user-card-avatar img {
    border-color: #334155;
}

body.dark-mode .user-card-name {
    color: #f1f5f9;
    border-bottom-color: #334155;
}

body.dark-mode .stat-value {
    color: #f1f5f9;
}

body.dark-mode .stat-label {
    color: #94a3b8;
}
</style>

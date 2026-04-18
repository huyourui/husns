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
<div class="page-profile">
    <div class="profile-header">
        <div class="profile-avatar">
            <?php echo $this->avatar($user['avatar'] ?? null, $user['username'], 'large'); ?>
        </div>
        <div class="profile-info">
            <h2><?php echo $this->escape($user['username']); ?></h2>
            <p class="bio"><?php echo $this->escape($user['bio'] ?: '这个人很懒，什么都没写...'); ?></p>
            <div class="profile-stats">
                <span>关注 <?php echo $followCount['following']; ?></span>
                <span>粉丝 <?php echo $followCount['followers']; ?></span>
                <span>动态 <?php echo (new PostModel())->countUserPosts($user['id']); ?></span>
                <span class="points-stat"><?php echo $this->escape(Setting::getPointName()); ?> <?php echo isset($user['points']) ? (int)$user['points'] : 0; ?></span>
            </div>
        </div>
        <?php if (!$isSelf && isset($_SESSION['user_id'])): ?>
        <div class="profile-action">
            <form method="post" action="<?php echo $this->url('user/' . ($isFollowing ? 'unfollow' : 'follow')); ?>">
                <?php echo $this->csrf(); ?>
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <button type="submit" class="btn <?php echo $isFollowing ? 'btn-default' : 'btn-primary'; ?>">
                    <?php echo $isFollowing ? '取消关注' : '关注'; ?>
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <div class="profile-tabs">
        <a href="<?php echo $this->url('user/profile?id=' . $user['id']); ?>" class="tab-item active">动态</a>
        <?php if ($isSelf): ?>
        <a href="<?php echo $this->url('user/favorites'); ?>" class="tab-item">我的收藏</a>
        <?php endif; ?>
    </div>

    <div class="profile-posts">
        <div class="post-list">
            <?php if (empty($posts['items'])): ?>
            <div class="empty">暂无动态</div>
            <?php else: ?>
            <?php foreach ($posts['items'] as $post): ?>
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
        
        <?php if (!empty($posts['pagination']) && $posts['pagination']['totalPages'] > 1): ?>
        <div class="pagination">
            <?php if ($posts['pagination']['page'] > 1): ?>
            <a href="<?php echo $this->url('user/profile?id=' . $user['id'] . '&page=' . ($posts['pagination']['page'] - 1)); ?>" class="btn">上一页</a>
            <?php endif; ?>
            
            <span class="page-info">第 <?php echo $posts['pagination']['page']; ?> / <?php echo $posts['pagination']['totalPages']; ?> 页</span>
            
            <?php if ($posts['pagination']['page'] < $posts['pagination']['totalPages']): ?>
            <a href="<?php echo $this->url('user/profile?id=' . $user['id'] . '&page=' . ($posts['pagination']['page'] + 1)); ?>" class="btn">下一页</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
window.addEventListener('load', function() {
    if (typeof window.initAllPostEvents === 'function') {
        window.initAllPostEvents();
    }
    if (typeof window.initRepostModal === 'function') {
        window.initRepostModal();
    }
});
</script>

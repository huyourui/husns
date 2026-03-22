<?php
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
    </div>

    <div class="profile-tabs">
        <a href="<?php echo $this->url('user/profile?id=' . $user['id']); ?>" class="tab-item">动态</a>
        <a href="<?php echo $this->url('user/favorites'); ?>" class="tab-item active">我的收藏</a>
    </div>

    <div class="profile-favorites">
        <?php if (empty($favorites)): ?>
        <div class="empty">暂无收藏</div>
        <?php else: ?>
        <div class="post-list">
            <?php foreach ($favorites as $post): ?>
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
                        <a href="<?php echo $this->url('post/detail?id=' . $post['id']); ?>" class="time"><?php echo $post['time_ago']; ?></a>
                        <span class="favorite-time">收藏于 <?php echo $post['favorited_time_ago']; ?></span>
                    </div>
                    
                    <?php if (!empty($post['content'])): ?>
                    <div class="post-text"><?php echo $post['content']; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($post['images'])): ?>
                    <div class="post-images">
                        <?php foreach (array_slice($post['images'], 0, 9) as $image): ?>
                        <img src="<?php echo $this->uploadUrl($image); ?>" alt="" onclick="previewImage(this)">
                        <?php endforeach; ?>
                    </div>
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
                    
                    <div class="post-actions">
                        <span class="action-btn repost-btn" data-id="<?php echo $post['id']; ?>" data-reposts="<?php echo $post['reposts'] ?? 0; ?>">转发(<?php echo $post['reposts'] ?? 0; ?>)</span>
                        <span class="action-btn comment-toggle" data-id="<?php echo $post['id']; ?>" data-comments="<?php echo $post['comments'] ?? 0; ?>">评论(<?php echo $post['comments'] ?? 0; ?>)</span>
                        <span class="action-btn like-btn" data-id="<?php echo $post['id']; ?>">点赞(<?php echo $post['likes'] ?? 0; ?>)</span>
                        <span class="action-btn favorite-btn favorited" data-id="<?php echo $post['id']; ?>" data-favorited="1">已收藏</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?r=user/favorites&page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

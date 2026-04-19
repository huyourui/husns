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
<div class="m-detail-page">
    <div class="m-post-item">
        <div class="m-post-header">
            <a href="<?php echo $this->url('mobile/user?id=' . $post['user_id']); ?>">
                <?php echo $this->avatar($post['avatar'] ?? null, $post['username'] ?? '', 'small'); ?>
            </a>
            <div class="m-post-user-info">
                <div class="m-post-username"><?php echo $this->escape($post['username']); ?></div>
                <div class="m-post-time"><?php echo Helper::formatTime($post['created_at']); ?></div>
            </div>
        </div>
        
        <div class="m-post-content">
            <?php echo $post['formatted_content']; ?>
        </div>
        
        <?php if (!empty($post['images'])): ?>
        <?php
        $imageCount = count($post['images']);
        $gridClass = 'single';
        if ($imageCount === 2) $gridClass = 'double';
        elseif ($imageCount === 3) $gridClass = 'triple';
        elseif ($imageCount === 4) $gridClass = 'quad';
        elseif ($imageCount > 4) $gridClass = 'many';
        ?>
        <div class="m-post-images <?php echo $gridClass; ?>">
            <?php foreach ($post['images'] as $img): ?>
            <img class="m-post-image" src="<?php echo $this->uploadUrl($img); ?>" alt="" loading="lazy">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="m-post-actions">
            <button class="m-action-btn <?php echo !empty($post['is_liked']) ? 'liked' : ''; ?>" data-action="like" data-id="<?php echo $post['id']; ?>">
                <span class="m-action-icon"><?php echo !empty($post['is_liked']) ? '❤️' : '🤍'; ?></span>
                <span class="m-action-count"><?php echo $post['likes']; ?></span>
            </button>
            <button class="m-action-btn" data-action="comment" data-id="<?php echo $post['id']; ?>">
                <span class="m-action-icon">💬</span>
                <span class="m-action-count"><?php echo $post['comments']; ?></span>
            </button>
            <button class="m-action-btn <?php echo !empty($post['is_favorited']) ? 'favorited' : ''; ?>" data-action="favorite" data-id="<?php echo $post['id']; ?>">
                <span class="m-action-icon"><?php echo !empty($post['is_favorited']) ? '⭐' : '☆'; ?></span>
            </button>
        </div>
    </div>
    
    <div class="m-comment-list">
        <div style="padding:12px 15px;font-weight:600;border-bottom:1px solid var(--border-color);">评论 (<?php echo $post['comments']; ?>)</div>
        
        <?php if (empty($comments)): ?>
        <div class="m-empty">
            <div class="m-empty-text">暂无评论</div>
        </div>
        <?php else: ?>
        <?php foreach ($comments as $comment): ?>
        <div class="m-comment-item">
            <div class="m-comment-header">
                <a href="<?php echo $this->url('mobile/user?id=' . $comment['user_id']); ?>">
                    <?php echo $this->avatar($comment['avatar'] ?? null, $comment['username'] ?? '', 'small'); ?>
                </a>
                <span class="m-comment-username"><?php echo $this->escape($comment['username']); ?></span>
                <span class="m-comment-time"><?php echo Helper::formatTime($comment['created_at']); ?></span>
            </div>
            <div class="m-comment-content">
                <?php 
                $content = Security::escape($comment['content']);
                $content = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|$)/u', '<a href="' . Helper::url('mobile/user?username=$1') . '">@$1</a>', $content);
                echo Helper::parseEmojis($content);
                ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['user_id'])): ?>
<div class="m-comment-input">
    <form id="commentForm">
        <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
        <input type="text" name="content" placeholder="发表评论..." autocomplete="off">
        <button type="submit">发送</button>
    </form>
</div>
<?php endif; ?>

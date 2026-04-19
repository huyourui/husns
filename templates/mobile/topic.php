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
<div style="padding:15px;background:var(--bg-secondary);border-bottom:1px solid var(--border-color);">
    <div style="font-size:18px;font-weight:700;">#<?php echo $this->escape($keyword); ?>#</div>
</div>

<div class="m-post-list">
    <?php if (empty($posts)): ?>
    <div class="m-empty">
        <div class="m-empty-icon">🏷️</div>
        <div class="m-empty-text">该话题下暂无内容</div>
    </div>
    <?php else: ?>
    <?php foreach ($posts as $post): ?>
    <div class="m-post-item" data-id="<?php echo $post['id']; ?>">
        <div class="m-post-header">
            <a href="<?php echo $this->url('mobile/user?id=' . $post['user_id']); ?>">
                <?php echo $this->avatar($post['avatar'] ?? null, $post['username'] ?? '', 'small'); ?>
            </a>
            <div class="m-post-user-info">
                <div class="m-post-username"><?php echo $this->escape($post['username']); ?></div>
                <div class="m-post-time"><?php echo $post['time_ago'] ?? Helper::formatTime($post['created_at']); ?></div>
            </div>
        </div>
        
        <a href="<?php echo $this->url('mobile/detail?id=' . $post['id']); ?>" style="text-decoration:none;color:inherit;">
            <div class="m-post-content">
                <?php 
                $content = Security::escape($post['content']);
                $content = preg_replace('/#(.+?)#/', '<a href="' . Helper::url('mobile/topic?keyword=$1') . '" onclick="event.stopPropagation();">#$1#</a>', $content);
                $content = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|$)/u', '<a href="' . Helper::url('mobile/user?username=$1') . '" onclick="event.stopPropagation();">@$1</a>', $content);
                echo Helper::parseEmojis($content);
                ?>
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
                <?php foreach (array_slice($post['images'], 0, 9) as $img): ?>
                <img class="m-post-image" src="<?php echo $this->uploadUrl($img); ?>" alt="" loading="lazy">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </a>
        
        <div class="m-post-actions">
            <button class="m-action-btn <?php echo !empty($post['is_liked']) ? 'liked' : ''; ?>" data-action="like" data-id="<?php echo $post['id']; ?>">
                <span class="m-action-icon"><?php echo !empty($post['is_liked']) ? '❤️' : '🤍'; ?></span>
                <span class="m-action-count"><?php echo $post['likes']; ?></span>
            </button>
            <a href="<?php echo $this->url('mobile/detail?id=' . $post['id']); ?>" class="m-action-btn" data-action="comment" data-id="<?php echo $post['id']; ?>">
                <span class="m-action-icon">💬</span>
                <span class="m-action-count"><?php echo $post['comments']; ?></span>
            </a>
            <button class="m-action-btn <?php echo !empty($post['is_favorited']) ? 'favorited' : ''; ?>" data-action="favorite" data-id="<?php echo $post['id']; ?>">
                <span class="m-action-icon"><?php echo !empty($post['is_favorited']) ? '⭐' : '☆'; ?></span>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (count($posts) >= 15): ?>
<div class="m-load-more">
    <a href="<?php echo $this->url('mobile/topic?keyword=' . urlencode($keyword) . '&page=' . ($page + 1)); ?>" style="color:var(--primary-color);text-decoration:none;">加载更多</a>
</div>
<?php endif; ?>

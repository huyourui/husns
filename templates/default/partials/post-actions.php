<?php
$canManage = isset($_SESSION['user_id']) && (
    $post['user_id'] == $_SESSION['user_id'] || 
    (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])
);
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$isSelf = isset($_SESSION['user_id']) && $post['user_id'] == $_SESSION['user_id'];
$isFavorited = false;
if (isset($_SESSION['user_id']) && !$isSelf) {
    $favoriteModel = new FavoriteModel();
    $isFavorited = $favoriteModel->isFavorited($post['id'], $_SESSION['user_id']);
}
?>
<div class="post-actions">
    <span class="action-btn repost-btn" data-id="<?php echo $post['id']; ?>" data-reposts="<?php echo $post['reposts'] ?? 0; ?>">转发(<?php echo $post['reposts'] ?? 0; ?>)</span>
    <span class="action-btn comment-toggle" data-id="<?php echo $post['id']; ?>" data-comments="<?php echo $post['comments'] ?? 0; ?>">评论(<?php echo $post['comments'] ?? 0; ?>)</span>
    <span class="action-btn like-btn" data-id="<?php echo $post['id']; ?>">点赞(<?php echo $post['likes'] ?? 0; ?>)</span>
    <?php if (!$isSelf): ?>
    <span class="action-btn favorite-btn <?php echo $isFavorited ? 'favorited' : ''; ?>" data-id="<?php echo $post['id']; ?>" data-favorited="<?php echo $isFavorited ? 1 : 0; ?>"><?php echo $isFavorited ? '已收藏' : '收藏'; ?></span>
    <?php endif; ?>
    <?php if ($canManage): ?>
    <div class="action-dropdown">
        <span class="action-btn dropdown-toggle">操作 ▼</span>
        <div class="dropdown-menu">
            <a href="javascript:void(0)" class="dropdown-item edit-btn" data-id="<?php echo $post['id']; ?>">✏️ 编辑</a>
            <a href="javascript:void(0)" class="dropdown-item delete-btn" data-id="<?php echo $post['id']; ?>">🗑️ 删除</a>
            <?php if ($isAdmin): ?>
            <a href="javascript:void(0)" class="dropdown-item pin-btn" data-id="<?php echo $post['id']; ?>" data-pinned="<?php echo !empty($post['is_pinned']) ? 1 : 0; ?>">
                <?php echo !empty($post['is_pinned']) ? '📌 取消置顶' : '📌 置顶'; ?>
            </a>
            <a href="javascript:void(0)" class="dropdown-item feature-btn" data-id="<?php echo $post['id']; ?>" data-featured="<?php echo !empty($post['is_featured']) ? 1 : 0; ?>">
                <?php echo !empty($post['is_featured']) ? '⭐ 取消加精' : '⭐ 加精'; ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

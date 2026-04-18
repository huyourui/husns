<?php

class PostActionHelper
{
    public static function render($post)
    {
        $canManage = isset($_SESSION['user_id']) && (
            $post['user_id'] == $_SESSION['user_id'] || 
            (isset($_SESSION['is_admin']) && $_SESSION['is_admin'])
        );
        
        $isSelf = isset($_SESSION['user_id']) && $post['user_id'] == $_SESSION['user_id'];
        $isFavorited = false;
        
        if (isset($_SESSION['user_id']) && !$isSelf) {
            $favoriteModel = new FavoriteModel();
            $isFavorited = $favoriteModel->isFavorited($post['id'], $_SESSION['user_id']);
        }

        $html = '<div class="post-actions">';
        $html .= '<span class="action-btn repost-btn" data-id="' . $post['id'] . '" data-reposts="' . $post['reposts'] . '">转发(' . $post['reposts'] . ')</span>';
        $html .= '<span class="action-btn comment-toggle" data-id="' . $post['id'] . '" data-comments="' . $post['comments'] . '">评论(' . $post['comments'] . ')</span>';
        $html .= '<span class="action-btn like-btn" data-id="' . $post['id'] . '">点赞(' . $post['likes'] . ')</span>';
        
        if (!$isSelf) {
            $html .= '<span class="action-btn favorite-btn ' . ($isFavorited ? 'favorited' : '') . '" data-id="' . $post['id'] . '" data-favorited="' . ($isFavorited ? 1 : 0) . '">' . ($isFavorited ? '已收藏' : '收藏') . '</span>';
        }
        
        if ($canManage) {
            $html .= '<div class="action-dropdown">';
            $html .= '<span class="action-btn dropdown-toggle">操作 ▼</span>';
            $html .= '<div class="dropdown-menu">';
            $html .= '<a href="javascript:void(0)" class="dropdown-item edit-btn" data-id="' . $post['id'] . '">✏️ 编辑</a>';
            $html .= '<a href="javascript:void(0)" class="dropdown-item delete-btn" data-id="' . $post['id'] . '">🗑️ 删除</a>';
            $html .= '</div></div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}

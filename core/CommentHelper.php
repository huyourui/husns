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
class CommentHelper
{
    public static function renderCommentBox($postId, $commentCount = 0)
    {
        $html = '<div class="comment-box" id="comment-box-' . $postId . '" style="display:none;">';
        $html .= self::renderCommentForm($postId);
        $html .= '<div class="comment-list" id="comment-list-' . $postId . '"></div>';
        $html .= '<div class="comment-more" id="comment-more-' . $postId . '" style="display:none;">';
        $html .= '<a href="javascript:void(0)" class="load-more-comments" data-post-id="' . $postId . '">加载更多评论</a>';
        $html .= '</div></div>';
        
        return $html;
    }
    
    public static function renderCommentForm($postId, $parentId = 0, $replyToUserId = 0, $placeholder = '发表评论...')
    {
        $maxLength = Setting::getMaxCommentLength();
        
        $html = '<form class="comment-form" data-post-id="' . $postId . '" data-parent-id="' . $parentId . '" data-reply-to-user-id="' . $replyToUserId . '">';
        $html .= Helper::csrfField();
        $html .= '<input type="hidden" name="post_id" value="' . $postId . '">';
        $html .= '<input type="hidden" name="parent_id" value="' . $parentId . '">';
        $html .= '<input type="hidden" name="reply_to_user_id" value="' . $replyToUserId . '">';
        $html .= '<input type="hidden" name="also_repost" value="0">';
        $html .= '<textarea name="content" placeholder="' . $placeholder . '" rows="2" data-max-length="' . $maxLength . '"></textarea>';
        $html .= '<div class="comment-form-actions">';
        if ($parentId == 0) {
            $html .= '<label class="also-repost-checkbox"><input type="checkbox"> 同时转发</label>';
        }
        $html .= '<span class="char-counter"><span class="char-count">0</span>/' . $maxLength . '</span>';
        if ($parentId > 0) {
            $html .= '<button type="button" class="btn btn-cancel-reply btn-sm">取消</button>';
        }
        $html .= '<button type="submit" class="btn btn-primary btn-sm">评论</button>';
        $html .= '</div></form>';
        
        return $html;
    }
    
    public static function renderCommentItem($comment)
    {
        $username = htmlspecialchars($comment['username']);
        $content = htmlspecialchars($comment['content']);
        $profileUrl = Helper::url('user/profile?id=' . $comment['user_id']);
        
        $html = '<div class="comment-item" data-comment-id="' . $comment['id'] . '">';
        $html .= '<div class="comment-avatar">' . Helper::avatar($comment['avatar'] ?? null, $comment['username'], 'small') . '</div>';
        $html .= '<div class="comment-body">';
        $html .= '<div class="comment-main">';
        $html .= '<a href="' . $profileUrl . '" class="username">' . $username . '</a>：';
        $html .= '<span class="comment-text">' . $content . '</span>';
        $html .= '<span class="time">' . $comment['time_ago'] . '</span>';
        $html .= '<span class="reply-btn" onclick="showReplyForm(' . $comment['id'] . ', ' . $comment['user_id'] . ', \'' . addslashes($username) . '\')">回复</span>';
        $html .= '</div>';
        
        if (!empty($comment['replies']) || !empty($comment['reply_count'])) {
            $html .= self::renderReplySection($comment);
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    public static function renderReplySection($comment)
    {
        $html = '<div class="reply-section" id="reply-section-' . $comment['id'] . '">';
        $html .= '<div class="reply-list" id="reply-list-' . $comment['id'] . '">';
        
        if (!empty($comment['replies'])) {
            $html .= self::renderReplyList($comment['replies']);
        }
        
        $html .= '</div>';
        
        if (!empty($comment['reply_count']) && $comment['reply_count'] > 3) {
            $html .= '<div class="reply-more" id="reply-more-' . $comment['id'] . '">';
            $html .= '<a href="javascript:void(0)" class="load-more-replies" data-parent-id="' . $comment['id'] . '" data-count="' . $comment['reply_count'] . '">';
            $html .= '共 ' . $comment['reply_count'] . ' 条回复，点击查看</a>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    public static function renderReplyItem($reply)
    {
        $username = htmlspecialchars($reply['username']);
        $content = htmlspecialchars($reply['content']);
        $profileUrl = Helper::url('user/profile?id=' . $reply['user_id']);
        
        $html = '<div class="reply-item" data-comment-id="' . $reply['id'] . '">';
        $html .= '<div class="reply-avatar">' . Helper::avatar($reply['avatar'] ?? null, $reply['username'], 'small') . '</div>';
        $html .= '<div class="reply-content">';
        $html .= '<a href="' . $profileUrl . '" class="username">' . $username . '</a>';
        
        if (!empty($reply['reply_to_username'])) {
            $replyToUrl = Helper::url('user/profile?username=' . urlencode($reply['reply_to_username']));
            $html .= ' 回复 <a href="' . $replyToUrl . '" class="reply-to">@' . htmlspecialchars($reply['reply_to_username']) . '</a>';
        }
        
        $html .= '：' . $content;
        $html .= '<span class="time">' . $reply['time_ago'] . '</span>';
        $html .= '<span class="reply-btn" onclick="showReplyForm(' . $reply['parent_id'] . ', ' . $reply['user_id'] . ', \'' . addslashes($username) . '\')">回复</span>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    public static function renderReplyList($replies)
    {
        $html = '';
        foreach ($replies as $reply) {
            $html .= self::renderReplyItem($reply);
        }
        return $html;
    }
    
    public static function renderCommentList($comments)
    {
        $html = '';
        foreach ($comments as $comment) {
            $html .= self::renderCommentItem($comment);
        }
        return $html;
    }
    
    public static function renderCommentSection($postId, $commentCount = 0, $comments = [])
    {
        $html = '<div class="comment-section">';
        $html .= '<h3>评论 (' . $commentCount . ')</h3>';
        
        if (isset($_SESSION['user_id'])) {
            $html .= '<form id="commentForm" class="comment-form" data-post-id="' . $postId . '" data-parent-id="0" data-reply-to-user-id="0">';
            $html .= Helper::csrfField();
            $html .= '<input type="hidden" name="post_id" value="' . $postId . '">';
            $html .= '<input type="hidden" name="parent_id" value="0">';
            $html .= '<input type="hidden" name="reply_to_user_id" value="0">';
            $html .= '<input type="hidden" name="also_repost" value="0">';
            $html .= '<textarea name="content" placeholder="发表评论..." rows="2" data-max-length="' . Setting::getMaxCommentLength() . '"></textarea>';
            $html .= '<div class="comment-form-actions">';
            $html .= '<label class="also-repost-checkbox"><input type="checkbox"> 同时转发</label>';
            $html .= '<span class="char-counter"><span class="char-count">0</span>/' . Setting::getMaxCommentLength() . '</span>';
            $html .= '<button type="submit" class="btn btn-primary btn-sm">评论</button>';
            $html .= '</div></form>';
        } else {
            $html .= '<p class="login-tip">请 <a href="' . Helper::url('user/login') . '">登录</a> 后发表评论</p>';
        }
        
        $html .= '<div class="comment-list">';
        $html .= self::renderCommentList($comments);
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}

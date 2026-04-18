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

namespace Controller\Traits;

use Helper;
use Security;
use Setting;
use Point;
use NotificationModel;
use Logger;
use FavoriteModel;

/**
 * 帖子互动相关 Trait
 * 
 * 包含点赞、收藏、评论、转发等功能
 * 
 * @package Controller\Traits
 */
trait PostInteractionTrait
{
    /**
     * 点赞
     *
     * @return void
     */
    public function like(): void
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        $banInfo = $this->userModel->getBanInfo($_SESSION['user_id']);
        if ($banInfo) {
            Helper::jsonError('您已被' . $banInfo['type_text'] . '，无法点赞');
        }

        if (!$this->checkActionInterval()) {
            $remaining = $this->getRemainingInterval();
            Helper::jsonError('操作过于频繁，请' . $remaining . '秒后再试');
        }
        
        $id = (int)Helper::post('id');
        $result = $this->postModel->like($id, $_SESSION['user_id']);
        
        if ($result) {
            Point::change($_SESSION['user_id'], 'like_post', 'post', $id);
            
            $post = $this->postModel->getPost($id, $_SESSION['user_id']);
            
            if ($post && $post['user_id'] != $_SESSION['user_id']) {
                try {
                    $notificationModel = new NotificationModel();
                    $sender = $this->userModel->find($_SESSION['user_id']);
                    if ($sender) {
                        $senderName = $sender['nickname'] ?: $sender['username'];
                        $notificationModel->sendLikeNotification($post['user_id'], $_SESSION['user_id'], $id, $senderName);
                    }
                } catch (\Exception $e) {
                Logger::error('点赞通知发送失败', [
                        'post_id' => $id,
                        'user_id' => $_SESSION['user_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            Helper::jsonSuccess(['likes' => $post['likes']]);
        } else {
            Helper::jsonError('已点赞过');
        }
    }

    /**
     * 取消点赞
     *
     * @return void
     */
    public function unlike(): void
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        $id = (int)Helper::post('id');
        $result = $this->postModel->unlike($id, $_SESSION['user_id']);
        
        if ($result) {
            $post = $this->postModel->getPost($id, $_SESSION['user_id']);
            Helper::jsonSuccess(['likes' => $post['likes']]);
        } else {
            Helper::jsonError('取消点赞失败');
        }
    }

    /**
     * 收藏
     *
     * @return void
     */
    public function favorite(): void
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        $id = (int)Helper::post('id');
        $post = $this->postModel->find($id);
        
        if (!$post || $post['status'] != 1) {
            Helper::jsonError('微博不存在');
        }
        
        if ($post['user_id'] == $_SESSION['user_id']) {
            Helper::jsonError('不能收藏自己的微博');
        }
        
        $favoriteModel = new FavoriteModel();
        $result = $favoriteModel->add($id, $_SESSION['user_id']);
        
        if ($result) {
            try {
                $notificationModel = new NotificationModel();
                $sender = $this->userModel->find($_SESSION['user_id']);
                if ($sender) {
                    $senderName = $sender['nickname'] ?: $sender['username'];
                    $notificationModel->sendFavoriteNotification($post['user_id'], $_SESSION['user_id'], $id, $senderName);
                }
            } catch (\Exception $e) {
                Logger::error('收藏通知发送失败', [
                    'post_id' => $id,
                    'user_id' => $_SESSION['user_id'],
                    'error' => $e->getMessage()
                ]);
            }
            
            Helper::jsonSuccess(null, '收藏成功');
        } else {
            Helper::jsonError('已收藏过');
        }
    }

    /**
     * 取消收藏
     *
     * @return void
     */
    public function unfavorite(): void
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        $id = (int)Helper::post('id');
        $favoriteModel = new FavoriteModel();
        $result = $favoriteModel->remove($id, $_SESSION['user_id']);
        
        if ($result) {
            Helper::jsonSuccess(null, '取消收藏成功');
        } else {
            Helper::jsonError('取消收藏失败');
        }
    }

    /**
     * 评论
     *
     * @return void
     */
    public function comment(): void
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        if (!Helper::isPost()) {
            Helper::jsonError('请求方法错误');
        }

        $banInfo = $this->userModel->getBanInfo($_SESSION['user_id']);
        if ($banInfo) {
            Helper::jsonError('您已被' . $banInfo['type_text'] . '，无法发表评论');
        }

        if (!$this->checkActionInterval()) {
            $remaining = $this->getRemainingInterval();
            Helper::jsonError('操作过于频繁，请' . $remaining . '秒后再试');
        }

        $postId = (int)Helper::post('post_id');
        $content = trim(Helper::post('content'));
        $parentId = (int)Helper::post('parent_id', 0);
        $replyToUserId = (int)Helper::post('reply_to_user_id', 0);
        $alsoRepost = (int)Helper::post('also_repost', 0);
        
        if (empty($content)) {
            Helper::jsonError('评论内容不能为空');
        }

        if (mb_strlen($content, 'UTF-8') > Setting::getMaxCommentLength()) {
            Helper::jsonError('评论内容不能超过' . Setting::getMaxCommentLength() . '字');
        }

        $content = Security::xssClean($content);
        
        $db = $this->db;
        $db->insert('comments', [
            'post_id' => $postId,
            'user_id' => $_SESSION['user_id'],
            'content' => $content,
            'parent_id' => $parentId,
            'reply_to_user_id' => $replyToUserId,
            'status' => 1,
            'ip' => Helper::getIp(),
            'created_at' => time()
        ]);
        
        $commentId = $db->lastInsertId();
        
        if ($commentId) {
            Point::change($_SESSION['user_id'], 'publish_comment', 'comment', $commentId);
            
            $db->query(
                "UPDATE __PREFIX__posts SET comments = comments + 1 WHERE id = ?",
                [$postId]
            );
            
            try {
                $post = $this->postModel->getPost($postId, $_SESSION['user_id']);
                if ($post && $post['user_id'] != $_SESSION['user_id']) {
                    $notificationModel = new NotificationModel();
                    $sender = $this->userModel->find($_SESSION['user_id']);
                    if ($sender) {
                        $notificationModel->sendCommentNotification($post['user_id'], $_SESSION['user_id'], $postId, $content, $sender['username']);
                    }
                }
                
                if ($replyToUserId && $replyToUserId != $_SESSION['user_id']) {
                    $notificationModel = new NotificationModel();
                    $sender = $this->userModel->find($_SESSION['user_id']);
                    if ($sender) {
                        $notificationModel->send(
                            $replyToUserId,
                            NotificationModel::TYPE_COMMENT,
                            "{$sender['username']} 回复了你的评论",
                            mb_substr($content, 0, 50, 'UTF-8'),
                            [
                                'sender_id' => $_SESSION['user_id'],
                                'target_type' => NotificationModel::TARGET_POST,
                                'target_id' => $postId,
                                'data' => ['post_id' => $postId, 'comment_id' => $commentId]
                            ]
                        );
                    }
                }
                
                $this->sendMentionNotifications($content, $postId, $_SESSION['user_id'], $commentId);

                if ($alsoRepost && $post) {
                    $repostId = $this->postModel->repost($_SESSION['user_id'], $postId, $content, false);
                    if ($repostId && $post['user_id'] != $_SESSION['user_id']) {
                        $notificationModel = new NotificationModel();
                        $sender = $this->userModel->find($_SESSION['user_id']);
                        if ($sender) {
                            $notificationModel->send(
                                $post['user_id'],
                                NotificationModel::TYPE_FOLLOW,
                                "{$sender['username']} 转发了你的微博",
                                mb_substr($content, 0, 50, 'UTF-8'),
                                [
                                    'sender_id' => $_SESSION['user_id'],
                                    'target_type' => NotificationModel::TARGET_POST,
                                    'target_id' => $postId,
                                    'data' => ['post_id' => $repostId, 'original_post_id' => $postId]
                                ]
                            );
                        }
                    }
                }
            } catch (\Exception $e) {
            }
            
            $comment = $db->fetch(
                "SELECT c.*, u.username, u.avatar 
                 FROM __PREFIX__comments c 
                 INNER JOIN __PREFIX__users u ON c.user_id = u.id 
                 WHERE c.id = ?",
                [$commentId]
            );
            
            $responseData = ['id' => $commentId];
            
            if ($comment) {
                $comment['time_ago'] = Helper::formatTime($comment['created_at']);
                $responseData['html'] = \CommentHelper::renderCommentItem($comment);
            }
            
            $post = $this->postModel->getPost($postId, $_SESSION['user_id']);
            if ($post && preg_match('/\[hide\].*?\[\/hide\]/is', $post['content'])) {
                $formattedContent = Security::escape($post['content']);
                $formattedContent = preg_replace('/#(.+?)#/', '<a href="' . Helper::url('post/topic?keyword=$1') . '">#$1#</a>', $formattedContent);
                $formattedContent = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|$)/u', '<a href="' . Helper::url('user/profile?username=$1') . '">@$1</a>', $formattedContent);
                $formattedContent = preg_replace('/(https?:\/\/[^\s<]+)/i', '<a href="$1" target="_blank" rel="noopener">$1</a>', $formattedContent);
                $formattedContent = Helper::parseEmojis($formattedContent);
                $formattedContent = $this->postModel->parseHideContent($formattedContent, $postId, $_SESSION['user_id'], $post['user_id']);
                $responseData['post_content'] = $formattedContent;
            }
            
            Helper::jsonSuccess($responseData, '评论成功');
        }

        Helper::jsonError('评论失败');
    }

    /**
     * 转发
     *
     * @return void
     */
    public function repost(): void
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        if (!Helper::isPost()) {
            Helper::jsonError('请求方法错误');
        }

        $banInfo = $this->userModel->getBanInfo($_SESSION['user_id']);
        if ($banInfo) {
            Helper::jsonError('您已被' . $banInfo['type_text'] . '，无法转发微博');
        }

        if (!$this->checkActionInterval()) {
            $remaining = $this->getRemainingInterval();
            Helper::jsonError('操作过于频繁，请' . $remaining . '秒后再试');
        }

        $originalPostId = (int)Helper::post('post_id');
        $content = trim(Helper::post('content'));
        $alsoComment = (int)Helper::post('also_comment', 0);

        if (!$originalPostId) {
            Helper::jsonError('参数错误');
        }

        $originalPost = $this->postModel->getPost($originalPostId, $_SESSION['user_id']);
        if (!$originalPost || $originalPost['status'] != 1) {
            Helper::jsonError('原微博不存在');
        }

        if (mb_strlen($content, 'UTF-8') > Setting::getMaxPostLength()) {
            Helper::jsonError('转发内容不能超过' . Setting::getMaxPostLength() . '字');
        }

        $content = Security::xssClean($content);

        $postId = $this->postModel->repost($_SESSION['user_id'], $originalPostId, $content, $alsoComment);

        if ($postId) {
            Point::change($_SESSION['user_id'], 'repost', 'post', $postId);
            
            try {
                $notificationModel = new NotificationModel();
                $sender = $this->userModel->find($_SESSION['user_id']);
                if ($sender && $originalPost['user_id'] != $_SESSION['user_id']) {
                    $notificationModel->send(
                        $originalPost['user_id'],
                        NotificationModel::TYPE_FOLLOW,
                        "{$sender['username']} 转发了你的微博",
                        mb_substr($content ?: $originalPost['content'], 0, 50, 'UTF-8'),
                        [
                            'sender_id' => $_SESSION['user_id'],
                            'target_type' => NotificationModel::TARGET_POST,
                            'target_id' => $originalPostId,
                            'data' => ['post_id' => $postId, 'original_post_id' => $originalPostId]
                        ]
                    );
                }
                
                if ($content) {
                    $this->sendMentionNotifications($content, $postId, $_SESSION['user_id'], null);
                }
            } catch (\Exception $e) {
            }

            Helper::jsonSuccess(['id' => $postId], '转发成功');
        }

        Helper::jsonError('转发失败');
    }

    /**
     * 获取评论列表
     *
     * @return void
     */
    public function getComments(): void
    {
        $postId = (int)Helper::get('post_id');
        $page = (int)Helper::get('page', 1);
        
        if (!$postId) {
            Helper::jsonError('参数错误');
        }
        
        $comments = $this->postModel->getComments($postId, $page, 5);
        
        $html = \CommentHelper::renderCommentList($comments);
        
        Helper::jsonSuccess(['html' => $html, 'count' => count($comments)]);
    }

    /**
     * 获取回复列表
     *
     * @return void
     */
    public function getReplies(): void
    {
        $parentId = (int)Helper::get('parent_id');
        $limit = (int)Helper::get('limit', 10);
        
        if (!$parentId) {
            Helper::jsonError('参数错误');
        }
        
        $replies = $this->postModel->getReplies($parentId, $limit);
        
        $html = \CommentHelper::renderReplyList($replies);
        
        Helper::jsonSuccess(['html' => $html, 'count' => count($replies)]);
    }
}

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
class NotificationModel extends Model
{
    protected $table = 'notifications';
    
    const TYPE_COMMENT = 'comment';
    const TYPE_LIKE = 'like';
    const TYPE_FOLLOW = 'follow';
    const TYPE_MENTION = 'mention';
    const TYPE_FAVORITE = 'favorite';
    const TYPE_SYSTEM = 'system';
    
    const TARGET_POST = 'post';
    const TARGET_COMMENT = 'comment';
    const TARGET_USER = 'user';

    public function create($data)
    {
        $data['created_at'] = time();
        return $this->insert($data);
    }

    public function send($userId, $type, $title, $content = '', $options = [])
    {
        if ($userId == ($options['sender_id'] ?? 0)) {
            return false;
        }
        
        $data = [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'sender_id' => $options['sender_id'] ?? 0,
            'target_type' => $options['target_type'] ?? '',
            'target_id' => $options['target_id'] ?? 0,
            'data' => isset($options['data']) ? json_encode($options['data'], JSON_UNESCAPED_UNICODE) : ''
        ];
        
        return $this->create($data);
    }

    public function sendCommentNotification($postUserId, $senderId, $postId, $commentContent, $senderName)
    {
        return $this->send(
            $postUserId,
            self::TYPE_COMMENT,
            "{$senderName} 评论了你的动态",
            mb_substr($commentContent, 0, 50, 'UTF-8'),
            [
                'sender_id' => $senderId,
                'target_type' => self::TARGET_POST,
                'target_id' => $postId,
                'data' => ['post_id' => $postId]
            ]
        );
    }

    public function sendLikeNotification($postUserId, $senderId, $postId, $senderName)
    {
        return $this->send(
            $postUserId,
            self::TYPE_LIKE,
            "{$senderName} 赞了你的动态",
            '',
            [
                'sender_id' => $senderId,
                'target_type' => self::TARGET_POST,
                'target_id' => $postId,
                'data' => ['post_id' => $postId]
            ]
        );
    }

    public function sendFollowNotification($userId, $senderId, $senderName)
    {
        return $this->send(
            $userId,
            self::TYPE_FOLLOW,
            "{$senderName} 关注了你",
            '',
            [
                'sender_id' => $senderId,
                'target_type' => self::TARGET_USER,
                'target_id' => $userId,
                'data' => ['follower_id' => $senderId]
            ]
        );
    }

    public function sendFavoriteNotification($postUserId, $senderId, $postId, $senderName)
    {
        return $this->send(
            $postUserId,
            self::TYPE_FAVORITE,
            "{$senderName} 收藏了你的动态",
            '',
            [
                'sender_id' => $senderId,
                'target_type' => self::TARGET_POST,
                'target_id' => $postId,
                'data' => ['post_id' => $postId]
            ]
        );
    }

    public function sendMentionNotification($userId, $senderId, $postId, $senderName, $commentId = null)
    {
        $title = $commentId 
            ? "{$senderName} 在评论中提到了你"
            : "{$senderName} 在动态中提到了你";
        
        return $this->send(
            $userId,
            self::TYPE_MENTION,
            $title,
            '',
            [
                'sender_id' => $senderId,
                'target_type' => self::TARGET_POST,
                'target_id' => $postId,
                'data' => [
                    'post_id' => $postId,
                    'comment_id' => $commentId
                ]
            ]
        );
    }

    public function getUserNotifications($userId, $page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT n.*, u.username as sender_name, u.avatar as sender_avatar
                FROM __PREFIX__notifications n
                LEFT JOIN __PREFIX__users u ON n.sender_id = u.id
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT {$offset}, {$pageSize}";
        
        $notifications = $this->db->fetchAll($sql, [$userId]);
        
        foreach ($notifications as &$notification) {
            $notification['time_ago'] = Helper::formatTime($notification['created_at']);
            $notification['data'] = $notification['data'] ? json_decode($notification['data'], true) : [];
            $notification['sender_display_name'] = $notification['sender_name'];
        }
        
        return $notifications;
    }

    public function getUnreadCount($userId)
    {
        return $this->count('user_id = ? AND is_read = 0', [$userId]);
    }

    public function markAsRead($id, $userId)
    {
        return $this->db->update(
            'notifications',
            ['is_read' => 1],
            'id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public function markAllAsRead($userId)
    {
        return $this->db->update(
            'notifications',
            ['is_read' => 1],
            'user_id = ? AND is_read = 0',
            [$userId]
        );
    }

    public function deleteNotification($id, $userId)
    {
        return $this->db->delete('notifications', 'id = ? AND user_id = ?', [$id, $userId]);
    }

    public function countUserNotifications($userId)
    {
        return $this->count('user_id = ?', [$userId]);
    }
}

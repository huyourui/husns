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
class PostModel extends Model
{
    protected $table = 'posts';

    public function getTimeline($page = 1, $pageSize = 10, $userId = null, $tab = 'all', $excludePinned = true)
    {
        $offset = ($page - 1) * $pageSize;
        $pinnedCondition = $excludePinned ? 'AND (p.is_pinned = 0 OR p.is_pinned IS NULL)' : '';
        
        if ($tab === 'following' && $userId) {
            $sql = "SELECT p.*, u.username, u.avatar 
                    FROM __PREFIX__posts p 
                    INNER JOIN __PREFIX__users u ON p.user_id = u.id 
                    LEFT JOIN __PREFIX__follows f ON p.user_id = f.follow_id AND f.user_id = ?
                    WHERE p.status = 1 AND (f.user_id = ? OR p.user_id = ?)
                    AND (u.ban_type = 0 OR u.ban_type = 1 OR u.ban_type IS NULL OR (u.ban_type = 1 AND u.ban_until > ?))
                    {$pinnedCondition}
                    ORDER BY p.created_at DESC 
                    LIMIT {$offset}, {$pageSize}";
            $posts = $this->db->fetchAll($sql, [$userId, $userId, $userId, time()]);
        } else {
            $sql = "SELECT p.*, u.username, u.avatar 
                    FROM __PREFIX__posts p 
                    INNER JOIN __PREFIX__users u ON p.user_id = u.id 
                    WHERE p.status = 1
                    AND (u.ban_type = 0 OR u.ban_type = 1 OR u.ban_type IS NULL OR (u.ban_type = 1 AND u.ban_until > ?))
                    {$pinnedCondition}
                    ORDER BY p.created_at DESC 
                    LIMIT {$offset}, {$pageSize}";
            $posts = $this->db->fetchAll($sql, [time()]);
        }

        return $this->formatPosts($posts);
    }

    public function getTimelineCount($userId = null, $tab = 'all', $excludePinned = true)
    {
        $pinnedCondition = $excludePinned ? 'AND (p.is_pinned = 0 OR p.is_pinned IS NULL)' : '';
        
        if ($tab === 'following' && $userId) {
            $sql = "SELECT COUNT(*) FROM __PREFIX__posts p 
                    LEFT JOIN __PREFIX__follows f ON p.user_id = f.follow_id AND f.user_id = ?
                    INNER JOIN __PREFIX__users u ON p.user_id = u.id
                    WHERE p.status = 1 AND (f.user_id = ? OR p.user_id = ?)
                    AND (u.ban_type = 0 OR u.ban_type = 1 OR u.ban_type IS NULL OR (u.ban_type = 1 AND u.ban_until > ?))
                    {$pinnedCondition}";
            return (int)$this->db->fetchColumn($sql, [$userId, $userId, $userId, time()]);
        }
        
        $sql = "SELECT COUNT(*) FROM __PREFIX__posts p 
                INNER JOIN __PREFIX__users u ON p.user_id = u.id
                WHERE p.status = 1
                AND (u.ban_type = 0 OR u.ban_type = 1 OR u.ban_type IS NULL OR (u.ban_type = 1 AND u.ban_until > ?))
                {$pinnedCondition}";
        return (int)$this->db->fetchColumn($sql, [time()]);
    }

    public function getPinnedPosts($currentUserId = 0)
    {
        try {
            $sql = "SELECT p.*, u.username, u.avatar 
                    FROM __PREFIX__posts p 
                    INNER JOIN __PREFIX__users u ON p.user_id = u.id 
                    WHERE p.status = 1 AND p.is_pinned = 1 
                    ORDER BY p.created_at DESC";
            $posts = $this->db->fetchAll($sql);
            return $this->formatPosts($posts);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getPinnedPostForDisplay($currentUserId = 0)
    {
        $pinnedPosts = $this->getPinnedPosts($currentUserId);
        
        if (empty($pinnedPosts)) {
            return null;
        }
        
        if (count($pinnedPosts) == 1) {
            return $pinnedPosts[0];
        }
        
        $index = array_rand($pinnedPosts);
        return $pinnedPosts[$index];
    }

    public function getPinnedPostIds()
    {
        try {
            $sql = "SELECT id FROM __PREFIX__posts WHERE status = 1 AND is_pinned = 1";
            $results = $this->db->fetchAll($sql);
            return array_column($results, 'id');
        } catch (Exception $e) {
            return [];
        }
    }

    public function getFeaturedPosts($page = 1, $pageSize = 10)
    {
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT p.*, u.username, u.avatar 
                FROM __PREFIX__posts p 
                INNER JOIN __PREFIX__users u ON p.user_id = u.id 
                WHERE p.status = 1 AND p.is_featured = 1
                AND (u.ban_type = 0 OR u.ban_type = 1 OR u.ban_type IS NULL OR (u.ban_type = 1 AND u.ban_until > ?))
                ORDER BY p.created_at DESC 
                LIMIT {$offset}, {$pageSize}";
        $posts = $this->db->fetchAll($sql, [time()]);
        
        return $this->formatPosts($posts);
    }

    public function countFeaturedPosts()
    {
        $sql = "SELECT COUNT(*) FROM __PREFIX__posts p 
                INNER JOIN __PREFIX__users u ON p.user_id = u.id
                WHERE p.status = 1 AND p.is_featured = 1
                AND (u.ban_type = 0 OR u.ban_type = 1 OR u.ban_type IS NULL OR (u.ban_type = 1 AND u.ban_until > ?))";
        return (int)$this->db->fetchColumn($sql, [time()]);
    }

    public function togglePin($postId)
    {
        $post = $this->find($postId);
        if (!$post) {
            return false;
        }
        
        $newStatus = isset($post['is_pinned']) && $post['is_pinned'] ? 0 : 1;
        return $this->update($postId, ['is_pinned' => $newStatus]);
    }

    public function toggleFeature($postId)
    {
        $post = $this->find($postId);
        if (!$post) {
            return false;
        }
        
        $newStatus = isset($post['is_featured']) && $post['is_featured'] ? 0 : 1;
        return $this->update($postId, ['is_featured' => $newStatus]);
    }

    public function getUserPosts($userId, $page = 1, $pageSize = 10)
    {
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT p.*, u.username, u.avatar 
                FROM __PREFIX__posts p 
                INNER JOIN __PREFIX__users u ON p.user_id = u.id 
                WHERE p.user_id = ? AND p.status = 1 
                ORDER BY p.created_at DESC 
                LIMIT {$offset}, {$pageSize}";
        
        $posts = $this->db->fetchAll($sql, [$userId]);
        $posts = $this->formatPosts($posts);
        
        $total = $this->count('user_id = ? AND status = 1', [$userId]);
        $totalPages = ceil($total / $pageSize);
        
        return [
            'items' => $posts,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages
        ];
    }

    public function getPost($id, $currentUserId = 0)
    {
        $sql = "SELECT p.*, u.username, u.avatar 
                FROM __PREFIX__posts p 
                INNER JOIN __PREFIX__users u ON p.user_id = u.id 
                WHERE p.id = ?";
        
        $post = $this->db->fetch($sql, [$id]);
        
        if ($post) {
            $post['images'] = is_array($post['images']) ? $post['images'] : ($post['images'] ? json_decode($post['images'], true) : []);
            $post['attachments'] = is_array($post['attachments']) ? $post['attachments'] : ($post['attachments'] ? json_decode($post['attachments'], true) : []);
            $post['videos'] = is_array($post['videos']) ? $post['videos'] : ($post['videos'] ? json_decode($post['videos'], true) : []);
            $post['time_ago'] = Helper::formatTime($post['created_at']);
            
            if (!empty($post['repost_id'])) {
                $post['original_post'] = $this->getOriginalPost($post['repost_id'], $currentUserId);
            }
        }
        
        return $post;
    }

    public function getOriginalPost($id, $currentUserId = 0)
    {
        $sql = "SELECT p.*, u.username, u.avatar 
                FROM __PREFIX__posts p 
                INNER JOIN __PREFIX__users u ON p.user_id = u.id 
                WHERE p.id = ? AND p.status = 1";
        
        $post = $this->db->fetch($sql, [$id]);
        
        if ($post) {
            $post['images'] = is_array($post['images']) ? $post['images'] : ($post['images'] ? json_decode($post['images'], true) : []);
            $post['attachments'] = is_array($post['attachments']) ? $post['attachments'] : ($post['attachments'] ? json_decode($post['attachments'], true) : []);
            $post['videos'] = is_array($post['videos']) ? $post['videos'] : ($post['videos'] ? json_decode($post['videos'], true) : []);
            $post['time_ago'] = Helper::formatTime($post['created_at']);
            $post['content'] = Security::escape($post['content']);
            $post['content'] = preg_replace('/#(.+?)#/', '<a href="' . Helper::url('post/topic?keyword=$1') . '">#$1#</a>', $post['content']);
            $post['content'] = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|:|$|\/\/)/u', '<a href="' . Helper::url('user/profile?username=$1') . '">@$1</a>', $post['content']);
            $post['content'] = preg_replace('/(https?:\/\/[^\s<]+)/i', '<a href="$1" target="_blank" rel="noopener">$1</a>', $post['content']);
            $post['content'] = $this->parseHideContent($post['content'], $id, $currentUserId, $post['user_id']);
        } else {
            $post = ['deleted' => true];
        }
        
        return $post;
    }

    public function publish($data)
    {
        $data['created_at'] = time();
        $data['updated_at'] = time();
        $data['status'] = 1;
        $data['ip'] = Helper::getIp();
        
        if (isset($data['images']) && is_array($data['images'])) {
            $data['images'] = json_encode($data['images']);
        }
        
        if (isset($data['attachments']) && is_array($data['attachments'])) {
            $data['attachments'] = json_encode($data['attachments']);
        }
        
        if (isset($data['videos']) && is_array($data['videos'])) {
            $data['videos'] = json_encode($data['videos']);
        }
        
        $data = Hook::trigger('post_before_publish', $data);
        
        return $this->insert($data);
    }

    public function repost($userId, $originalPostId, $content = '', $alsoComment = false)
    {
        $originalPost = $this->getPost($originalPostId, $userId);
        if (!$originalPost || $originalPost['status'] != 1) {
            return false;
        }

        $finalOriginalId = $originalPostId;
        $repostUserId = $originalPost['user_id'];
        $originalContent = $content; // 保存原始内容用于评论
        
        if (!empty($originalPost['repost_id'])) {
            $finalOriginalId = $originalPost['repost_id'];
            $repostUserId = $originalPost['repost_user_id'];
            
            $chainContent = '@' . $originalPost['username'] . ': ' . strip_tags($originalPost['content']);
            if (!empty($content)) {
                $content = $content . ' // ' . $chainContent;
            } else {
                $content = $chainContent;
            }
        }

        $postId = $this->insert([
            'user_id' => $userId,
            'content' => $content,
            'repost_id' => $finalOriginalId,
            'repost_user_id' => $repostUserId,
            'created_at' => time(),
            'updated_at' => time(),
            'status' => 1,
            'ip' => Helper::getIp()
        ]);

        if ($postId) {
            $this->db->query(
                "UPDATE __PREFIX__posts SET reposts = reposts + 1 WHERE id = ?",
                [$originalPostId]
            );

            if ($alsoComment) {
                $this->addComment($originalPostId, $userId, $originalContent);
            }
        }

        return $postId;
    }

    public function deletePost($id, $userId, $isAdmin = false)
    {
        $post = $this->find($id);
        
        if (!$post) {
            return false;
        }
        
        if (!$isAdmin && $post['user_id'] != $userId) {
            return false;
        }
        
        Hook::trigger('post_before_delete', $post);

        $this->deletePostFiles($post);

        if (!empty($post['repost_id'])) {
            $this->db->query(
                "UPDATE __PREFIX__posts SET reposts = GREATEST(0, reposts - 1) WHERE id = ?",
                [$post['repost_id']]
            );
        }
        
        return $this->update($id, ['status' => 0, 'updated_at' => time()]);
    }

    private function deletePostFiles($post)
    {
        if (!empty($post['images'])) {
            $images = is_array($post['images']) ? $post['images'] : json_decode($post['images'], true);
            if (is_array($images)) {
                foreach ($images as $image) {
                    $filePath = UPLOAD_PATH . $image;
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
        }

        if (!empty($post['attachments'])) {
            $attachments = is_array($post['attachments']) ? $post['attachments'] : json_decode($post['attachments'], true);
            if (is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        $filePath = UPLOAD_PATH . $attachment['path'];
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }
                    }
                }
            }
        }
    }

    public function like($postId, $userId)
    {
        $exists = $this->db->fetch(
            "SELECT * FROM __PREFIX__likes WHERE post_id = ? AND user_id = ?",
            [$postId, $userId]
        );
        
        if ($exists) {
            return false;
        }
        
        $this->db->insert('likes', [
            'post_id' => $postId,
            'user_id' => $userId,
            'created_at' => time()
        ]);
        
        $this->db->query(
            "UPDATE __PREFIX__posts SET likes = likes + 1 WHERE id = ?",
            [$postId]
        );
        
        return true;
    }

    public function unlike($postId, $userId)
    {
        $result = $this->db->delete('likes', 'post_id = ? AND user_id = ?', [$postId, $userId]);
        
        if ($result) {
            $this->db->query(
                "UPDATE __PREFIX__posts SET likes = GREATEST(0, likes - 1) WHERE id = ?",
                [$postId]
            );
        }
        
        return $result;
    }

    public function isLiked($postId, $userId)
    {
        return $this->db->count('likes', 'post_id = ? AND user_id = ?', [$postId, $userId]) > 0;
    }

    public function isReposted($postId, $userId)
    {
        return $this->db->count('posts', 'repost_id = ? AND user_id = ? AND status = 1', [$postId, $userId]) > 0;
    }

    public function getComments($postId, $page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT c.*, u.username, u.avatar 
                FROM __PREFIX__comments c 
                INNER JOIN __PREFIX__users u ON c.user_id = u.id 
                WHERE c.post_id = ? AND c.status = 1 AND c.parent_id = 0 
                AND (u.ban_type = 0 OR u.ban_type = 1 OR u.ban_type IS NULL OR (u.ban_type = 1 AND u.ban_until > ?))
                ORDER BY c.created_at DESC 
                LIMIT {$offset}, {$pageSize}";
        
        $comments = $this->db->fetchAll($sql, [$postId, time()]);
        
        foreach ($comments as &$comment) {
            $comment['time_ago'] = Helper::formatTime($comment['created_at']);
            $comment['replies'] = $this->getReplies($comment['id']);
            $comment['reply_count'] = $this->countReplies($comment['id']);
        }
        
        return $comments;
    }

    
    public function getReplies($parentId, $limit = 3)
    {
        $sql = "SELECT c.*, u.username, u.avatar, ru.username as reply_to_username
                FROM __PREFIX__comments c 
                INNER JOIN __PREFIX__users u ON c.user_id = u.id 
                LEFT JOIN __PREFIX__users ru ON c.reply_to_user_id = ru.id 
                WHERE c.parent_id = ? AND c.status = 1 
                AND (u.ban_type = 0 OR u.ban_type = 1 OR u.ban_type IS NULL OR (u.ban_type = 1 AND u.ban_until > ?))
                ORDER BY c.created_at ASC 
                LIMIT {$limit}";
        
        $replies = $this->db->fetchAll($sql, [$parentId, time()]);
        
        foreach ($replies as &$reply) {
            $reply['time_ago'] = Helper::formatTime($reply['created_at']);
        }
        
        return $replies;
    }

    public function countReplies($parentId)
    {
        $sql = "SELECT COUNT(*) FROM __PREFIX__comments c 
                INNER JOIN __PREFIX__users u ON c.user_id = u.id 
                WHERE c.parent_id = ? AND c.status = 1 
                AND (u.ban_type = 0 OR u.ban_type = 1 OR u.ban_type IS NULL OR (u.ban_type = 1 AND u.ban_until > ?))";
        return (int)$this->db->fetchColumn($sql, [$parentId, time()]);
    }

    public function addComment($postId, $userId, $content, $parentId = 0, $replyToUserId = 0)
    {
        $content = Security::xssClean($content);
        $content = Hook::trigger('comment_before_add', $content);
        
        $this->db->insert('comments', [
            'post_id' => $postId,
            'user_id' => $userId,
            'content' => $content,
            'parent_id' => $parentId,
            'reply_to_user_id' => $replyToUserId,
            'status' => 1,
            'ip' => Helper::getIp(),
            'created_at' => time()
        ]);
        
        $this->db->query(
            "UPDATE __PREFIX__posts SET comments = comments + 1 WHERE id = ?",
            [$postId]
        );
        
        return $this->db->lastInsertId();
    }

    public function countUserPosts($userId)
    {
        return $this->count('user_id = ? AND status = 1', [$userId]);
    }

    public function getPostsByTopic($topic, $page = 1, $pageSize = 10)
    {
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT p.*, u.username, u.avatar 
                FROM __PREFIX__posts p 
                INNER JOIN __PREFIX__users u ON p.user_id = u.id 
                WHERE p.content LIKE ? AND p.status = 1 
                ORDER BY p.created_at DESC 
                LIMIT {$offset}, {$pageSize}";
        
        $posts = $this->db->fetchAll($sql, ['%#' . $topic . '#%']);
        return $this->formatPosts($posts);
    }

    public function countPostsByTopic($topic)
    {
        return $this->db->count('posts', 'content LIKE ? AND status = 1', ['%#' . $topic . '#%']);
    }

    public function getHotTopics($limit = 10)
    {
        $statDays = (int)Setting::get('topic_stat_days', 7);
        $startTime = time() - ($statDays * 86400);
        
        $sql = "SELECT content, likes, comments, reposts FROM __PREFIX__posts WHERE status = 1 AND created_at >= ?";
        $posts = $this->db->fetchAll($sql, [$startTime]);
        
        $topics = [];
        foreach ($posts as $post) {
            preg_match_all('/#([^#]+)#/', $post['content'], $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $topic) {
                    $topic = trim($topic);
                    if ($topic) {
                        if (!isset($topics[$topic])) {
                            $topics[$topic] = [
                                'count' => 0,
                                'engagement' => 0
                            ];
                        }
                        $topics[$topic]['count']++;
                        $topics[$topic]['engagement'] += (int)$post['likes'] + (int)$post['comments'] + (int)$post['reposts'];
                    }
                }
            }
        }
        
        $blockedTopics = $this->db->fetchAll("SELECT name FROM __PREFIX__topics WHERE is_blocked = 1");
        $blockedNames = array_column($blockedTopics, 'name');
        
        foreach ($blockedNames as $blockedName) {
            unset($topics[$blockedName]);
        }
        
        uasort($topics, function($a, $b) {
            return $b['engagement'] - $a['engagement'];
        });
        
        $autoTopics = [];
        foreach ($topics as $topic => $data) {
            $autoTopics[] = [
                'name' => $topic,
                'count' => $data['count'],
                'engagement' => $data['engagement'],
                'is_pinned' => false
            ];
        }
        
        $pinnedTopics = $this->db->fetchAll(
            "SELECT name, sort_order FROM __PREFIX__topics WHERE is_pinned = 1 AND is_blocked = 0 ORDER BY sort_order ASC"
        );
        
        $pinnedNames = array_column($pinnedTopics, 'name');
        $autoTopics = array_filter($autoTopics, function($topic) use ($pinnedNames) {
            return !in_array($topic['name'], $pinnedNames);
        });
        $autoTopics = array_values($autoTopics);
        
        $result = [];
        foreach ($pinnedTopics as $pinned) {
            $result[] = [
                'name' => $pinned['name'],
                'count' => 0,
                'engagement' => 0,
                'is_pinned' => true
            ];
        }
        
        $remainingSlots = $limit - count($result);
        if ($remainingSlots > 0) {
            $autoTopicsSlice = array_slice($autoTopics, 0, $remainingSlots);
            $result = array_merge($result, $autoTopicsSlice);
        }
        
        return array_slice($result, 0, $limit);
    }

    private function formatPosts($posts)
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $hideTagAdminOnly = Setting::isHideTagAdminOnly();
        
        foreach ($posts as &$post) {
            $post['images'] = is_array($post['images']) ? $post['images'] : ($post['images'] ? json_decode($post['images'], true) : []);
            $post['time_ago'] = Helper::formatTime($post['created_at']);
            $post['content'] = ltrim($post['content']);
            $post['content'] = Security::escape($post['content']);
            $post['content'] = preg_replace('/#(.+?)#/', '<a href="' . Helper::url('post/topic?keyword=$1') . '">#$1#</a>', $post['content']);
            $post['content'] = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|:|$|\/\/)/u', '<a href="' . Helper::url('user/profile?username=$1') . '">@$1</a>', $post['content']);
            $post['content'] = preg_replace('/(https?:\/\/[^\s<]+)/i', '<a href="$1" target="_blank" rel="noopener">$1</a>', $post['content']);
            
            $post['attachments'] = is_array($post['attachments']) ? $post['attachments'] : ($post['attachments'] ? json_decode($post['attachments'], true) : []);
            $post['videos'] = is_array($post['videos']) ? $post['videos'] : ($post['videos'] ? json_decode($post['videos'], true) : []);
            
            $parseHide = true;
            if ($hideTagAdminOnly) {
                $authorIsAdmin = $this->isUserAdmin($post['user_id']);
                if (!$authorIsAdmin) {
                    $parseHide = false;
                    $post['content'] = str_replace(['[hide]', '[/hide]'], ['&#91;hide&#93;', '&#91;/hide&#93;'], $post['content']);
                }
            }
            
            if ($parseHide) {
                $post['content'] = $this->parseHideContent($post['content'], $post['id'], $userId, $post['user_id']);
            }
            
            if (!empty($post['repost_id'])) {
                $post['original_post'] = $this->getOriginalPost($post['repost_id'], $userId);
            }
        }
        
        return $posts;
    }
    
    private function isUserAdmin($userId)
    {
        $sql = "SELECT is_admin FROM __PREFIX__users WHERE id = ?";
        $result = $this->db->fetch($sql, [$userId]);
        return $result && (int)$result['is_admin'] === 1;
    }
    
    public function hasUserCommented($postId, $userId)
    {
        if (!$userId) {
            return false;
        }
        
        $sql = "SELECT COUNT(*) as count FROM __PREFIX__comments 
                WHERE post_id = ? AND user_id = ? AND status = 1";
        
        $result = $this->db->fetch($sql, [$postId, $userId]);
        
        return $result['count'] > 0;
    }
    
    public function parseHideContent($content, $postId, $userId, $postAuthorId)
    {
        $hasHide = preg_match('/\[hide\](.*?)\[\/hide\]/is', $content);
        
        if (!$hasHide) {
            return $content;
        }
        
        $hasCommented = $this->hasUserCommented($postId, $userId);
        
        if ($hasCommented || ($userId && $userId == $postAuthorId)) {
            $content = preg_replace('/\[hide\](.*?)\[\/hide\]/is', '<div class="hide-content-revealed">$1</div>', $content);
        } else {
            $content = preg_replace('/\[hide\].*?\[\/hide\]/is', '<div class="hide-content-locked">🔒 此内容需要评论后才能查看</div>', $content);
        }
        
        return $content;
    }
}

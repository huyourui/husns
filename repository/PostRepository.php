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

namespace Repository;

use Core\BaseRepository;
use Helper;
use Hook;
use Security;

/**
 * 帖子数据仓库
 * 
 * 处理帖子相关的数据操作，包括发布、查询、互动等
 * 
 * @package Repository
 */
class PostRepository extends BaseRepository
{
    /**
     * 表名
     * @var string
     */
    protected $table = 'posts';

    /**
     * 获取时间线数据
     *
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param int|null $userId 当前用户ID
     * @param string $tab 标签（all/following）
     * @param bool $excludePinned 是否排除置顶
     * @return array
     */
    public function getTimeline(int $page = 1, int $pageSize = 10, ?int $userId = null, string $tab = 'all', bool $excludePinned = true): array
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

    /**
     * 获取时间线总数
     *
     * @param int|null $userId 当前用户ID
     * @param string $tab 标签
     * @param bool $excludePinned 是否排除置顶
     * @return int
     */
    public function getTimelineCount(?int $userId = null, string $tab = 'all', bool $excludePinned = true): int
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

    /**
     * 发布帖子
     *
     * @param array $data 帖子数据
     * @return int 帖子ID
     */
    public function publish(array $data): int
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
        
        return (int)$this->insert($data);
    }

    /**
     * 点赞
     *
     * @param int $postId 帖子ID
     * @param int $userId 用户ID
     * @return bool
     */
    public function like(int $postId, int $userId): bool
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

    /**
     * 取消点赞
     *
     * @param int $postId 帖子ID
     * @param int $userId 用户ID
     * @return bool
     */
    public function unlike(int $postId, int $userId): bool
    {
        $result = $this->db->delete('likes', 'post_id = ? AND user_id = ?', [$postId, $userId]);
        
        if ($result) {
            $this->db->query(
                "UPDATE __PREFIX__posts SET likes = GREATEST(0, likes - 1) WHERE id = ?",
                [$postId]
            );
        }
        
        return $result > 0;
    }

    /**
     * 检查是否已点赞
     *
     * @param int $postId 帖子ID
     * @param int $userId 用户ID
     * @return bool
     */
    public function isLiked(int $postId, int $userId): bool
    {
        return $this->db->count('likes', 'post_id = ? AND user_id = ?', [$postId, $userId]) > 0;
    }

    /**
     * 获取帖子详情
     *
     * @param int $id 帖子ID
     * @param int $currentUserId 当前用户ID
     * @return array|null
     */
    public function getPost(int $id, int $currentUserId = 0): ?array
    {
        $sql = "SELECT p.*, u.username, u.avatar 
                FROM __PREFIX__posts p 
                INNER JOIN __PREFIX__users u ON p.user_id = u.id 
                WHERE p.id = ?";
        
        $post = $this->db->fetch($sql, [$id]);
        
        if (!$post) {
            return null;
        }
        
        $post['images'] = is_array($post['images']) ? $post['images'] : ($post['images'] ? json_decode($post['images'], true) : []);
        $post['attachments'] = is_array($post['attachments']) ? $post['attachments'] : ($post['attachments'] ? json_decode($post['attachments'], true) : []);
        $post['videos'] = is_array($post['videos']) ? $post['videos'] : ($post['videos'] ? json_decode($post['videos'], true) : []);
        $post['time_ago'] = Helper::formatTime($post['created_at']);
        
        if (!empty($post['repost_id'])) {
            $post['original_post'] = $this->getOriginalPost($post['repost_id'], $currentUserId);
        }
        
        return $post;
    }

    /**
     * 获取原帖
     *
     * @param int $id 帖子ID
     * @param int $currentUserId 当前用户ID
     * @return array
     */
    protected function getOriginalPost(int $id, int $currentUserId = 0): array
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
            $post['content'] = Helper::parseEmojis($post['content']);
            $post['content'] = $this->parseHideContent($post['content'], $id, $currentUserId, $post['user_id']);
        } else {
            $post = ['deleted' => true];
        }
        
        return $post;
    }

    /**
     * 格式化帖子列表
     *
     * @param array $posts 帖子数组
     * @return array
     */
    protected function formatPosts(array $posts): array
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $hideTagAdminOnly = \Setting::isHideTagAdminOnly();
        
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

    /**
     * 检查用户是否为管理员
     *
     * @param int $userId 用户ID
     * @return bool
     */
    protected function isUserAdmin(int $userId): bool
    {
        $sql = "SELECT is_admin FROM __PREFIX__users WHERE id = ?";
        $result = $this->db->fetch($sql, [$userId]);
        return $result && (int)$result['is_admin'] === 1;
    }

    /**
     * 检查用户是否已评论
     *
     * @param int $postId 帖子ID
     * @param int $userId 用户ID
     * @return bool
     */
    public function hasUserCommented(int $postId, int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        
        $sql = "SELECT COUNT(*) as count FROM __PREFIX__comments 
                WHERE post_id = ? AND user_id = ? AND status = 1";
        
        $result = $this->db->fetch($sql, [$postId, $userId]);
        
        return $result['count'] > 0;
    }

    /**
     * 解析隐藏内容
     *
     * @param string $content 内容
     * @param int $postId 帖子ID
     * @param int $userId 用户ID
     * @param int $postAuthorId 帖子作者ID
     * @return string
     */
    public function parseHideContent(string $content, int $postId, int $userId, int $postAuthorId): string
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

    /**
     * 获取热门话题
     *
     * @param int $limit 数量限制
     * @return array
     */
    public function getHotTopics(int $limit = 10): array
    {
        $statDays = (int)\Setting::get('topic_stat_days', 7);
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
            $topicName = $pinned['name'];
            $count = isset($topics[$topicName]) ? $topics[$topicName]['count'] : 0;
            $engagement = isset($topics[$topicName]) ? $topics[$topicName]['engagement'] : 0;
            $result[] = [
                'name' => $topicName,
                'count' => $count,
                'engagement' => $engagement,
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
}

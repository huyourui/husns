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

/**
 * 帖子列表相关 Trait
 * 
 * 包含首页、热门、话题、精华等功能
 * 
 * @package Controller\Traits
 */
trait PostListTrait
{
    /**
     * 首页
     *
     * @return void
     */
    public function index(): void
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->view->setLayout(null);
            $this->render('post/guest', [
                'siteName' => Setting::getSiteName(),
                'subtitle' => Setting::getSubtitle(),
                'registrationOpen' => Setting::isRegistrationOpen(),
                'emailVerifyEnabled' => Setting::isRegistrationEmailVerifyEnabled(),
                'icpNumber' => Setting::getIcpNumber(),
                'icpUrl' => Setting::getIcpUrl()
            ]);
            return;
        }
        
        $page = (int)Helper::get('page', 1);
        $tab = Helper::get('tab', '');
        $pageSize = Setting::getPostsPerPage();
        
        $userModel = new \UserModel();
        $totalUsers = $userModel->count();
        $threshold = Setting::getDefaultAllPostsThreshold();
        
        if (empty($tab)) {
            if ($userId) {
                $tab = ($totalUsers < $threshold) ? 'all' : 'following';
            } else {
                $tab = 'all';
            }
        }
        
        $pinnedPost = $this->postModel->getPinnedPostForDisplay($userId ? $userId : 0);
        if ($pinnedPost) {
            $pinnedPost['content'] = Helper::parseEmojis($pinnedPost['content']);
        }
        
        $posts = $this->postModel->getTimeline($page, $pageSize, $userId, $tab);
        foreach ($posts as &$post) {
            $post['content'] = Helper::parseEmojis($post['content']);
        }
        unset($post);
        
        $totalPosts = $this->postModel->getTimelineCount($userId, $tab);
        $totalPages = ceil($totalPosts / $pageSize);
        
        $announcementModel = new \AnnouncementModel();
        $announcements = $announcementModel->getActive();
        
        $userStats = null;
        if ($userId) {
            $userStats = $this->getUserStats($userId);
        }
        
        $hotTopics = $this->postModel->getHotTopics(10);
        
        $this->render('post/index', [
            'posts' => $posts,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages,
            'announcements' => $announcements,
            'tab' => $tab,
            'totalUsers' => $totalUsers,
            'threshold' => $threshold,
            'pinnedPost' => $pinnedPost,
            'userStats' => $userStats,
            'hotTopics' => $hotTopics
        ]);
    }

    /**
     * 帖子详情
     *
     * @return void
     */
    public function detail(): void
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url());
        }
        
        $id = (int)Helper::get('id');
        
        if (!$id) {
            $this->redirect(Helper::url());
        }

        $post = $this->postModel->getPost($id, $userId ? $userId : 0);
        
        if (!$post || $post['status'] != 1) {
            Helper::setFlash('error', '动态不存在');
            $this->redirect(Helper::url());
        }

        $post['formatted_content'] = Security::escape($post['content']);
        $post['formatted_content'] = preg_replace('/#(.+?)#/', '<a href="' . Helper::url('post/topic?keyword=$1') . '">#$1#</a>', $post['formatted_content']);
        $post['formatted_content'] = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|$)/u', '<a href="' . Helper::url('user/profile?username=$1') . '">@$1</a>', $post['formatted_content']);
        $post['formatted_content'] = preg_replace('/(https?:\/\/[^\s<]+)/i', '<a href="$1" target="_blank" rel="noopener">$1</a>', $post['formatted_content']);
        $post['formatted_content'] = Helper::parseEmojis($post['formatted_content']);
        
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $post['formatted_content'] = $this->postModel->parseHideContent($post['formatted_content'], $id, $userId, $post['user_id']);

        $comments = $this->postModel->getComments($id);
        $isLiked = isset($_SESSION['user_id']) ? $this->postModel->isLiked($id, $_SESSION['user_id']) : false;
        $isReposted = isset($_SESSION['user_id']) ? $this->postModel->isReposted($id, $_SESSION['user_id']) : false;

        $plainContent = strip_tags($post['content']);
        $plainContent = preg_replace('/\[hide\].*?\[\/hide\]/is', '[隐藏内容]', $plainContent);
        $plainContent = preg_replace('/#([^#]+)#/', '$1', $plainContent);
        $plainContent = trim($plainContent);
        
        $title = mb_substr($plainContent, 0, 40, 'UTF-8');
        if (mb_strlen($plainContent, 'UTF-8') > 40) {
            $title .= '...';
        }
        
        $description = mb_substr($plainContent, 0, 200, 'UTF-8');
        if (mb_strlen($plainContent, 'UTF-8') > 200) {
            $description .= '...';
        }

        $this->render('post/detail', [
            'post' => $post,
            'comments' => $comments,
            'isLiked' => $isLiked,
            'isReposted' => $isReposted,
            'title' => $title,
            'description' => $description
        ]);
    }

    /**
     * 热门帖子
     *
     * @return void
     */
    public function hot(): void
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url());
        }
        
        $page = max(1, (int)Helper::get('page', 1));
        $pageSize = max(1, min(100, (int)Setting::getPostsPerPage()));
        $threshold = max(0, (int)Setting::getHotThreshold());
        
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT p.*, u.username, u.avatar,
                    (
                        (SELECT COUNT(*) FROM __PREFIX__likes l WHERE l.post_id = p.id AND l.user_id != p.user_id) +
                        (SELECT COUNT(*) FROM __PREFIX__comments c WHERE c.post_id = p.id AND c.user_id != p.user_id AND c.status = 1) +
                        (SELECT COUNT(*) FROM __PREFIX__posts rp WHERE rp.repost_id = p.id AND rp.user_id != p.user_id AND rp.status = 1) +
                        (SELECT COUNT(*) FROM __PREFIX__favorites f WHERE f.post_id = p.id AND f.user_id != p.user_id)
                    ) as hot_score
                FROM __PREFIX__posts p 
                INNER JOIN __PREFIX__users u ON p.user_id = u.id 
                WHERE p.status = 1 
                HAVING hot_score >= ?
                ORDER BY p.created_at DESC 
                LIMIT ?, ?";
        
        $posts = $this->db->fetchAll($sql, [$threshold, $offset, $pageSize]);
        
        foreach ($posts as &$post) {
            $post['images'] = is_array($post['images']) ? $post['images'] : ($post['images'] ? json_decode($post['images'], true) : []);
            $post['attachments'] = is_array($post['attachments']) ? $post['attachments'] : ($post['attachments'] ? json_decode($post['attachments'], true) : []);
            $post['videos'] = is_array($post['videos']) ? $post['videos'] : ($post['videos'] ? json_decode($post['videos'], true) : []);
            $post['time_ago'] = Helper::formatTime($post['created_at']);
            $post['content'] = Security::escape($post['content']);
            $post['content'] = preg_replace('/#(.+?)#/', '<a href="' . Helper::url('post/topic?keyword=$1') . '">#$1#</a>', $post['content']);
            $post['content'] = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|:|$|\/\/)/u', '<a href="' . Helper::url('user/profile?username=$1') . '">@$1</a>', $post['content']);
            $post['content'] = Helper::parseEmojis($post['content']);
            
            if ($userId) {
                $post['is_liked'] = $this->postModel->isLiked($post['id'], $userId);
                $favoriteModel = new \FavoriteModel();
                $post['is_favorited'] = $favoriteModel->isFavorited($post['id'], $userId);
            } else {
                $post['is_liked'] = false;
                $post['is_favorited'] = false;
            }
        }
        
        $countSql = "SELECT COUNT(*) as total FROM (
                        SELECT p.id,
                            (
                                (SELECT COUNT(*) FROM __PREFIX__likes l WHERE l.post_id = p.id AND l.user_id != p.user_id) +
                                (SELECT COUNT(*) FROM __PREFIX__comments c WHERE c.post_id = p.id AND c.user_id != p.user_id AND c.status = 1) +
                                (SELECT COUNT(*) FROM __PREFIX__posts rp WHERE rp.repost_id = p.id AND rp.user_id != p.user_id AND rp.status = 1) +
                                (SELECT COUNT(*) FROM __PREFIX__favorites f WHERE f.post_id = p.id AND f.user_id != p.user_id)
                            ) as hot_score
                        FROM __PREFIX__posts p 
                        WHERE p.status = 1 
                        HAVING hot_score >= ?
                    ) as hot_posts";
        $countResult = $this->db->fetch($countSql, [$threshold]);
        $total = (int)($countResult['total'] ?? 0);
        $totalPages = ceil($total / $pageSize);
        
        $this->render('post/hot', [
            'posts' => $posts,
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'totalPages' => $totalPages,
            'threshold' => $threshold,
            'title' => '热门'
        ]);
    }

    /**
     * 话题页面
     *
     * @return void
     */
    public function topic(): void
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url());
        }
        
        $keyword = trim(Helper::get('keyword'));
        $page = (int)Helper::get('page', 1);
        
        if (empty($keyword)) {
            $this->redirect(Helper::url());
        }
        
        $keyword = Security::xssClean($keyword);
        $pageSize = Setting::getPostsPerPage();
        
        $posts = $this->postModel->getPostsByTopic($keyword, $page, $pageSize);
        foreach ($posts as &$post) {
            $post['content'] = Helper::parseEmojis($post['content']);
        }
        unset($post);
        
        $total = $this->postModel->countPostsByTopic($keyword);
        
        $title = '#' . $keyword . '# - 话题';
        
        $this->render('post/topic', [
            'posts' => $posts,
            'keyword' => $keyword,
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'title' => $title
        ]);
    }

    /**
     * 精华帖子
     *
     * @return void
     */
    public function featured(): void
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url());
        }
        
        $page = (int)Helper::get('page', 1);
        $pageSize = Setting::getPostsPerPage();
        
        $posts = $this->postModel->getFeaturedPosts($page, $pageSize);
        foreach ($posts as &$post) {
            $post['content'] = Helper::parseEmojis($post['content']);
        }
        unset($post);
        
        $total = $this->postModel->countFeaturedPosts();
        $totalPages = ceil($total / $pageSize);
        
        $this->render('post/featured', [
            'posts' => $posts,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages,
            'total' => $total
        ]);
    }

    /**
     * 置顶帖子
     *
     * @return void
     */
    public function pin(): void
    {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            Helper::jsonError('无权限操作');
        }
        
        $id = (int)Helper::post('id');
        if (!$id) {
            Helper::jsonError('参数错误');
        }
        
        $result = $this->postModel->togglePin($id);
        if ($result) {
            $post = $this->postModel->find($id);
            Helper::jsonSuccess(['pinned' => $post['is_pinned'] ? 1 : 0]);
        }
        
        Helper::jsonError('操作失败');
    }

    /**
     * 加精帖子
     *
     * @return void
     */
    public function feature(): void
    {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            Helper::jsonError('无权限操作');
        }
        
        $id = (int)Helper::post('id');
        if (!$id) {
            Helper::jsonError('参数错误');
        }
        
        $result = $this->postModel->toggleFeature($id);
        if ($result) {
            $post = $this->postModel->find($id);
            Helper::jsonSuccess(['featured' => $post['is_featured'] ? 1 : 0]);
        }
        
        Helper::jsonError('操作失败');
    }

    /**
     * 获取用户统计
     *
     * @param int $userId 用户ID
     * @return array|null
     */
    protected function getUserStats(int $userId): ?array
    {
        $user = $this->userModel->find($userId);
        if (!$user) {
            return null;
        }
        
        $postCount = $this->postModel->count('user_id = ? AND status = 1', [$userId]);
        
        $sql = "SELECT 
                    COALESCE(SUM(likes), 0) + COALESCE(SUM(comments), 0) + COALESCE(SUM(reposts), 0) as total_engagement
                FROM __PREFIX__posts 
                WHERE user_id = ? AND status = 1";
        $result = $this->db->fetch($sql, [$userId]);
        $totalEngagement = (int)($result['total_engagement'] ?? 0);
        
        $points = (int)($user['points'] ?? 0);
        
        return [
            'post_count' => $postCount,
            'total_engagement' => $totalEngagement,
            'points' => $points,
            'user' => $user
        ];
    }
}

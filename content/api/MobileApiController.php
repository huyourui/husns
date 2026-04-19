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

/**
 * 移动端API控制器
 * 
 * 提供移动端专用的RESTful API接口
 * 所有接口返回JSON格式数据
 */
class MobileApiController extends Controller
{
    private $postModel;
    private $userModel;
    private $notificationModel;

    public function __construct()
    {
        parent::__construct();
        $this->postModel = new PostModel();
        $this->userModel = new UserModel();
        $this->notificationModel = new NotificationModel();
        
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    protected function requireLogin()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            $this->error('请先登录', 401);
        }
        return $_SESSION['user_id'];
    }

    protected function success($data = null, $message = '操作成功')
    {
        echo json_encode([
            'code' => 0,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function error($message = '操作失败', $code = 1)
    {
        echo json_encode([
            'code' => $code,
            'message' => $message,
            'data' => null,
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function paginate($items, $total, $page, $pageSize, $hasMore = null)
    {
        if ($hasMore === null) {
            $hasMore = count($items) >= $pageSize;
        }
        $this->success([
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'has_more' => $hasMore
            ]
        ]);
    }

    protected function input($key, $default = null)
    {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (is_array($data) && isset($data[$key])) {
            return $data[$key];
        }
        return $default;
    }

    private function formatPost($post, $userId = null)
    {
        $post['images'] = is_array($post['images']) ? $post['images'] : 
            ($post['images'] ? json_decode($post['images'], true) : []);
        $post['videos'] = is_array($post['videos']) ? $post['videos'] : 
            ($post['videos'] ? json_decode($post['videos'], true) : []);
        $post['attachments'] = is_array($post['attachments']) ? $post['attachments'] : 
            ($post['attachments'] ? json_decode($post['attachments'], true) : []);
        $post['time_ago'] = Helper::formatTime($post['created_at']);
        $post['content'] = Helper::parseContent($post['content']);
        
        $post['is_liked'] = false;
        $post['is_favorited'] = false;
        $post['is_following'] = false;
        
        if ($userId) {
            $post['is_liked'] = $this->postModel->isLiked($post['id'], $userId);
            $favoriteModel = new FavoriteModel();
            $post['is_favorited'] = $favoriteModel->isFavorited($post['id'], $userId);
            if ($post['user_id'] != $userId) {
                $post['is_following'] = $this->userModel->isFollowing($userId, $post['user_id']);
            }
        }
        
        $post['avatar'] = Helper::avatar($post['avatar'] ?? null, $post['username']);
        
        // 处理转发微博的原文
        $post['is_repost'] = false;
        if (!empty($post['repost_id'])) {
            if (!empty($post['original_post']) && is_array($post['original_post']) && !isset($post['original_post']['deleted'])) {
                $post['is_repost'] = true;
                $post['original_post'] = $this->formatOriginalPost($post['original_post']);
            }
        }
        
        return $post;
    }
    
    private function formatOriginalPost($post)
    {
        $post['images'] = is_array($post['images']) ? $post['images'] : 
            ($post['images'] ? json_decode($post['images'], true) : []);
        $post['videos'] = is_array($post['videos']) ? $post['videos'] : 
            ($post['videos'] ? json_decode($post['videos'], true) : []);
        $post['time_ago'] = Helper::formatTime($post['created_at']);
        $post['content'] = Helper::parseContent($post['content']);
        $post['avatar'] = Helper::avatar($post['avatar'] ?? null, $post['username']);
        
        return $post;
    }

    private function formatUser($user, $currentUserId = null)
    {
        $result = [
            'id' => $user['id'],
            'username' => $user['username'],
            'avatar' => Helper::avatar($user['avatar'] ?? null, $user['username']),
            'bio' => $user['bio'] ?? '',
            'created_at' => $user['created_at'] ?? 0
        ];
        
        if ($currentUserId && $user['id'] != $currentUserId) {
            $result['is_following'] = $this->userModel->isFollowing($currentUserId, $user['id']);
        }
        
        return $result;
    }

    private function formatNotification($notification)
    {
        $notification['time_ago'] = Helper::formatTime($notification['created_at']);
        $notification['sender_avatar'] = Helper::avatar(
            $notification['sender_avatar'] ?? null, 
            $notification['sender_name'] ?? ''
        );
        return $notification;
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('请求方法错误');
        }

        $username = trim($this->input('username'));
        $password = $this->input('password');

        if (empty($username) || empty($password)) {
            $this->error('请填写用户名和密码');
        }

        $user = $this->userModel->login($username, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            session_regenerate_id(true);

            $this->success([
                'user' => $this->formatUser($user, $user['id'])
            ], '登录成功');
        } else {
            $this->error('用户名或密码错误');
        }
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('请求方法错误');
        }

        if (!Setting::isRegistrationOpen()) {
            $this->error('注册功能已关闭');
        }

        $username = trim($this->input('username') ?? '');
        $password = $this->input('password') ?? '';
        $confirmPassword = $this->input('confirm_password') ?? '';
        $email = trim($this->input('email') ?? '');
        $emailCode = trim($this->input('email_code') ?? '');
        $inviteCode = trim($this->input('invite_code') ?? '');

        $errors = [];

        $minLength = Setting::getUsernameMinLength();
        $maxLength = Setting::getUsernameMaxLength();
        $usernameLength = mb_strlen($username, 'UTF-8');

        if ($usernameLength < $minLength) {
            $errors[] = '用户名至少需要' . $minLength . '个字符';
        } elseif ($usernameLength > $maxLength) {
            $errors[] = '用户名最多允许' . $maxLength . '个字符';
        } elseif (!preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]+$/u', $username)) {
            $errors[] = '用户名只能包含字母、数字、下划线和中文';
        }

        if ($this->userModel->findByUsername($username)) {
            $errors[] = '用户名已存在';
        }

        if (strlen($password) < 6) {
            $errors[] = '密码长度至少6位';
        }

        if ($password !== $confirmPassword) {
            $errors[] = '两次密码输入不一致';
        }

        $emailVerifyEnabled = Setting::isRegistrationEmailVerifyEnabled();
        $requireInviteCode = (int)Setting::get('require_invite_code', 0) === 1;

        if ($emailVerifyEnabled || !empty($email)) {
            if (!Security::validateEmail($email)) {
                $errors[] = '邮箱格式不正确';
            } else {
                $allowedSuffixes = Setting::getAllowedEmailSuffixes();
                if (!empty($allowedSuffixes)) {
                    $emailSuffix = substr($email, strrpos($email, '@') + 1);
                    if (!in_array($emailSuffix, $allowedSuffixes)) {
                        $errors[] = '只允许使用以下邮箱后缀注册：' . implode(', ', $allowedSuffixes);
                    }
                }
                if ($this->userModel->findByEmail($email)) {
                    $errors[] = '邮箱已被注册';
                }
            }
        }

        if ($emailVerifyEnabled) {
            if (empty($emailCode)) {
                $errors[] = '请输入邮箱验证码';
            } elseif (!Mailer::verifyCode($email, $emailCode, '注册')) {
                $errors[] = '验证码错误或已过期';
            }
        }

        if ($requireInviteCode) {
            if (empty($inviteCode)) {
                $errors[] = '请输入邀请码';
            } else {
                require_once ROOT_PATH . 'content/invite/InviteController.php';
                if (!InviteController::verifyCode($inviteCode)) {
                    $errors[] = '邀请码无效或已被使用';
                }
            }
        }

        if (!empty($errors)) {
            $this->error(implode('，', $errors));
        }

        $userId = $this->userModel->register([
            'username' => $username,
            'password' => $password,
            'email' => $email ?: ''
        ]);

        if ($userId) {
            if ($requireInviteCode && !empty($inviteCode)) {
                require_once ROOT_PATH . 'content/invite/InviteController.php';
                InviteController::useCode($inviteCode, $userId);
            }

            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = 0;
            session_regenerate_id(true);

            $user = $this->userModel->find($userId);
            $this->success([
                'user' => $this->formatUser($user, $userId)
            ], '注册成功');
        } else {
            $this->error('注册失败');
        }
    }

    public function logout()
    {
        session_destroy();
        $this->success(null, '退出成功');
    }

    public function userInfo()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId) {
            $this->success(['logged_in' => false]);
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            session_destroy();
            $this->success(['logged_in' => false]);
        }

        $postCount = $this->postModel->count('user_id = ? AND status = 1', [$userId]);
        $followCount = $this->userModel->getFollowCount($userId);
        $unreadCount = $this->notificationModel->getUnreadCount($userId);

        $this->success([
            'logged_in' => true,
            'user' => $this->formatUser($user, $userId),
            'stats' => [
                'post_count' => $postCount,
                'following' => $followCount['following'] ?? 0,
                'followers' => $followCount['followers'] ?? 0,
                'unread_count' => $unreadCount
            ]
        ]);
    }

    public function posts()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        // 调试日志
        error_log('posts API called, userId: ' . ($userId ?: 'null'));
        error_log('GET params: ' . json_encode($_GET));
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            error_log('posts API: guest access not allowed, returning 401');
            $this->error('请先登录', 401);
        }

        $page = (int)$this->input('page', 1);
        $tab = $this->input('tab', 'all');
        $pageSize = 15;
        
        error_log('posts API: page=' . $page . ', tab=' . $tab);

        $posts = $this->postModel->getTimeline($page, $pageSize, $userId, $tab);
        error_log('posts API: fetched ' . count($posts) . ' posts');
        
        // 调试：检查第一个转发微博的数据
        foreach ($posts as $i => $p) {
            if (!empty($p['repost_id'])) {
                error_log('posts API: post['.$i.'] repost_id=' . $p['repost_id'] . ', has original_post=' . (isset($p['original_post']) ? 'yes' : 'no'));
                if (isset($p['original_post'])) {
                    error_log('posts API: original_post keys=' . implode(',', array_keys($p['original_post'])));
                }
            }
        }
        
        $formattedPosts = [];
        foreach ($posts as $post) {
            $formattedPosts[] = $this->formatPost($post, $userId);
        }

        $total = $this->postModel->getTimelineCount($userId, $tab);
        error_log('posts API: total count=' . $total);
        
        $this->paginate($formattedPosts, $total, $page, $pageSize);
    }

    public function hotPosts()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->error('请先登录', 401);
        }

        $page = max(1, (int)$this->input('page', 1));
        $pageSize = 15;
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
                ORDER BY hot_score DESC, p.created_at DESC 
                LIMIT {$offset}, {$pageSize}";

        $posts = $this->db->fetchAll($sql, [$threshold]);
        
        $formattedPosts = [];
        foreach ($posts as $post) {
            $formattedPosts[] = $this->formatPost($post, $userId);
        }

        $this->success([
            'items' => $formattedPosts,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'has_more' => count($formattedPosts) >= $pageSize
            ]
        ]);
    }

    public function topicPosts()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->error('请先登录', 401);
        }

        $keyword = trim($this->input('keyword'));
        if (empty($keyword)) {
            $this->error('话题不能为空');
        }

        $page = (int)$this->input('page', 1);
        $pageSize = 15;

        $posts = $this->postModel->getPostsByTopic($keyword, $page, $pageSize);
        
        $formattedPosts = [];
        foreach ($posts as $post) {
            $formattedPosts[] = $this->formatPost($post, $userId);
        }

        $total = $this->postModel->countPostsByTopic($keyword);
        $this->paginate($formattedPosts, $total, $page, $pageSize);
    }

    public function postDetail()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->error('请先登录', 401);
        }

        $id = (int)$this->input('id');
        if (!$id) {
            $this->error('参数错误');
        }

        $post = $this->postModel->getPost($id, $userId ? $userId : 0);
        if (!$post || $post['status'] != 1) {
            $this->error('微博不存在');
        }

        $this->success([
            'post' => $this->formatPost($post, $userId)
        ]);
    }

    public function comments()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->error('请先登录', 401);
        }

        $postId = (int)$this->input('post_id');
        $page = (int)$this->input('page', 1);
        $pageSize = 20;

        if (!$postId) {
            $this->error('参数错误');
        }

        $comments = $this->postModel->getComments($postId, $page, $pageSize);
        
        $formattedComments = [];
        foreach ($comments as $comment) {
            $comment['time_ago'] = Helper::formatTime($comment['created_at']);
            $comment['avatar'] = Helper::avatar($comment['avatar'] ?? null, $comment['username']);
            $comment['content'] = Helper::parseContent($comment['content']);
            $formattedComments[] = $comment;
        }

        $this->success([
            'items' => $formattedComments,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'has_more' => count($formattedComments) >= $pageSize
            ]
        ]);
    }

    public function publish()
    {
        $userId = $this->requireLogin();

        $banInfo = $this->userModel->getBanInfo($userId);
        if ($banInfo) {
            $this->error('您已被' . $banInfo['type_text'] . '，无法发布内容');
        }

        $content = trim($this->input('content'));
        if (empty($content)) {
            $this->error('内容不能为空');
        }

        if (mb_strlen($content, 'UTF-8') > Setting::getMaxPostLength()) {
            $this->error('内容不能超过' . Setting::getMaxPostLength() . '字');
        }

        $content = Security::xssClean($content);

        $images = [];
        if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $images = $this->uploadImages();
        } elseif (!empty($_POST['images']) && is_array($_POST['images'])) {
            $images = array_values(array_filter($_POST['images'], function($path) {
                return !empty($path) && is_string($path) && preg_match('/^[a-zA-Z0-9_\-\/\.]+$/', $path);
            }));
        }

        $postId = $this->postModel->publish([
            'user_id' => $userId,
            'content' => $content,
            'images' => $images
        ]);

        if ($postId) {
            Point::change($userId, 'publish_post', 'post', $postId);
            
            try {
                $this->sendMentionNotifications($content, $postId, $userId, null);
            } catch (Exception $e) {}

            $post = $this->postModel->getPost($postId, $userId);
            $this->success([
                'post' => $this->formatPost($post, $userId)
            ], '发布成功');
        }

        $this->error('发布失败');
    }

    public function like()
    {
        $userId = $this->requireLogin();

        $banInfo = $this->userModel->getBanInfo($userId);
        if ($banInfo) {
            $this->error('您已被' . $banInfo['type_text'] . '，无法点赞');
        }

        $id = (int)$this->input('id');
        $result = $this->postModel->like($id, $userId);

        if ($result) {
            Point::change($userId, 'like_post', 'post', $id);
            
            $post = $this->postModel->getPost($id, $userId);
            if ($post && isset($post['user_id']) && $post['user_id'] != $userId) {
                $this->sendLikeNotification($post['user_id'], $id);
            }

            $this->success(['likes' => $post['likes'] ?? 0]);
        } else {
            $this->error('已点赞过');
        }
    }

    public function unlike()
    {
        $userId = $this->requireLogin();

        $id = (int)$this->input('id');
        $result = $this->postModel->unlike($id, $userId);

        if ($result) {
            $post = $this->postModel->getPost($id, $userId);
            $this->success(['likes' => $post['likes'] ?? 0]);
        } else {
            $this->error('取消点赞失败');
        }
    }

    public function favorite()
    {
        $userId = $this->requireLogin();

        $id = (int)$this->input('id');
        $post = $this->postModel->find($id);

        if (!$post || $post['status'] != 1) {
            $this->error('微博不存在');
        }

        if ($post['user_id'] == $userId) {
            $this->error('不能收藏自己的微博');
        }

        $favoriteModel = new FavoriteModel();
        $result = $favoriteModel->add($id, $userId);

        if ($result) {
            $this->sendFavoriteNotification($post['user_id'], $id);
            $this->success(null, '收藏成功');
        } else {
            $this->error('已收藏过');
        }
    }

    public function unfavorite()
    {
        $userId = $this->requireLogin();

        $id = (int)$this->input('id');
        $favoriteModel = new FavoriteModel();
        $result = $favoriteModel->remove($id, $userId);

        if ($result) {
            $this->success(null, '取消收藏成功');
        } else {
            $this->error('取消收藏失败');
        }
    }

    public function comment()
    {
        $userId = $this->requireLogin();

        $banInfo = $this->userModel->getBanInfo($userId);
        if ($banInfo) {
            $this->error('您已被' . $banInfo['type_text'] . '，无法发表评论');
        }

        $postId = (int)$this->input('post_id');
        $content = trim($this->input('content'));
        $parentId = (int)$this->input('parent_id', 0);
        $replyToUserId = (int)$this->input('reply_to_user_id', 0);

        if (empty($content)) {
            $this->error('评论内容不能为空');
        }

        if (mb_strlen($content, 'UTF-8') > Setting::getMaxCommentLength()) {
            $this->error('评论内容不能超过' . Setting::getMaxCommentLength() . '字');
        }

        $content = Security::xssClean($content);

        $db = Database::getInstance();
        $db->insert('comments', [
            'post_id' => $postId,
            'user_id' => $userId,
            'content' => $content,
            'parent_id' => $parentId,
            'reply_to_user_id' => $replyToUserId,
            'status' => 1,
            'ip' => Helper::getIp(),
            'created_at' => time()
        ]);

        $commentId = $db->lastInsertId();

        if ($commentId) {
            Point::change($userId, 'publish_comment', 'comment', $commentId);
            
            $db->query(
                "UPDATE __PREFIX__posts SET comments = comments + 1 WHERE id = ?",
                [$postId]
            );

            try {
                $post = $this->postModel->getPost($postId, $userId);
                if ($post && $post['user_id'] != $userId) {
                    $sender = $this->userModel->find($userId);
                    if ($sender) {
                        $this->notificationModel->sendCommentNotification(
                            $post['user_id'], $userId, $postId, $content, $sender['username']
                        );
                    }
                }

                if ($replyToUserId && $replyToUserId != $userId) {
                    $sender = $this->userModel->find($userId);
                    if ($sender) {
                        $this->notificationModel->send(
                            $replyToUserId,
                            NotificationModel::TYPE_COMMENT,
                            "{$sender['username']} 回复了你的评论",
                            mb_substr($content, 0, 50, 'UTF-8'),
                            [
                                'sender_id' => $userId,
                                'target_type' => NotificationModel::TARGET_POST,
                                'target_id' => $postId,
                                'data' => ['post_id' => $postId, 'comment_id' => $commentId]
                            ]
                        );
                    }
                }

                $this->sendMentionNotifications($content, $postId, $userId, $commentId);
            } catch (Exception $e) {}

            $comment = $db->fetch(
                "SELECT c.*, u.username, u.avatar 
                 FROM __PREFIX__comments c 
                 INNER JOIN __PREFIX__users u ON c.user_id = u.id 
                 WHERE c.id = ?",
                [$commentId]
            );

            if ($comment) {
                $comment['time_ago'] = Helper::formatTime($comment['created_at']);
                $comment['avatar'] = Helper::avatar($comment['avatar'] ?? null, $comment['username']);
                $comment['content'] = Helper::parseContent($comment['content']);
            }

            $this->success([
                'comment' => $comment
            ], '评论成功');
        }

        $this->error('评论失败');
    }

    public function deletePost()
    {
        $userId = $this->requireLogin();

        $id = (int)$this->input('id');
        $result = $this->postModel->deletePost($id, $userId);

        if ($result) {
            Point::change($userId, 'delete_post', 'post', $id);
            $this->success(null, '删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function repost()
    {
        $userId = $this->requireLogin();

        $banInfo = $this->userModel->getBanInfo($userId);
        if ($banInfo) {
            $this->error('您已被' . $banInfo['type_text'] . '，无法转发微博');
        }

        $originalPostId = (int)$this->input('post_id');
        $content = trim($this->input('content'));
        $alsoComment = (int)$this->input('also_comment', 0);

        if (!$originalPostId) {
            $this->error('参数错误');
        }

        $originalPost = $this->postModel->getPost($originalPostId, $userId);
        if (!$originalPost || $originalPost['status'] != 1) {
            $this->error('原微博不存在');
        }

        if (mb_strlen($content, 'UTF-8') > Setting::getMaxPostLength()) {
            $this->error('转发内容不能超过' . Setting::getMaxPostLength() . '字');
        }

        $content = Security::xssClean($content);

        $postId = $this->postModel->repost($userId, $originalPostId, $content, $alsoComment);

        if ($postId) {
            Point::change($userId, 'repost', 'post', $postId);

            try {
                $sender = $this->userModel->find($userId);
                if ($sender && $originalPost['user_id'] != $userId) {
                    $this->notificationModel->send(
                        $originalPost['user_id'],
                        NotificationModel::TYPE_FOLLOW,
                        "{$sender['username']} 转发了你的微博",
                        mb_substr($content ?: $originalPost['content'], 0, 50, 'UTF-8'),
                        [
                            'sender_id' => $userId,
                            'target_type' => NotificationModel::TARGET_POST,
                            'target_id' => $originalPostId,
                            'data' => ['post_id' => $postId, 'original_post_id' => $originalPostId]
                        ]
                    );
                }

                if ($content) {
                    $this->sendMentionNotifications($content, $postId, $userId, null);
                }
            } catch (Exception $e) {}

            $post = $this->postModel->getPost($postId, $userId);
            $this->success([
                'post' => $this->formatPost($post, $userId)
            ], '转发成功');
        }

        $this->error('转发失败');
    }

    public function notifications()
    {
        $userId = $this->requireLogin();

        $page = (int)$this->input('page', 1);
        $pageSize = 20;

        // 调试信息
        error_log('notifications API called, userId: ' . $userId);

        $notifications = $this->notificationModel->getUserNotifications($userId, $page, $pageSize);
        $unreadCount = $this->notificationModel->getUnreadCount($userId);

        // 调试信息
        error_log('notifications count: ' . count($notifications));
        error_log('unread count: ' . $unreadCount);

        $formattedNotifications = [];
        foreach ($notifications as $notification) {
            $formattedNotifications[] = $this->formatNotification($notification);
        }

        $this->success([
            'items' => $formattedNotifications,
            'unread_count' => $unreadCount,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'has_more' => count($formattedNotifications) >= $pageSize
            ]
        ]);
    }

    public function markRead()
    {
        $userId = $this->requireLogin();

        $id = (int)$this->input('id');
        if (!$id) {
            $this->error('参数错误');
        }

        $result = $this->notificationModel->markAsRead($id, $userId);

        if ($result) {
            $unreadCount = $this->notificationModel->getUnreadCount($userId);
            $this->success(['unread_count' => $unreadCount]);
        } else {
            $this->error('操作失败');
        }
    }

    public function markAllRead()
    {
        $userId = $this->requireLogin();

        $result = $this->notificationModel->markAllAsRead($userId);

        if ($result !== false) {
            $this->success(null, '全部已读');
        } else {
            $this->error('操作失败');
        }
    }

    public function userProfile()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->error('请先登录', 401);
        }

        $username = $this->input('username');
        $targetId = (int)$this->input('id');

        if ($username) {
            $targetUser = $this->userModel->findByUsername($username);
        } elseif ($targetId) {
            $targetUser = $this->userModel->find($targetId);
        } else {
            $this->error('参数错误');
        }

        if (!$targetUser) {
            $this->error('用户不存在');
        }

        $postCount = $this->postModel->count('user_id = ? AND status = 1', [$targetUser['id']]);
        $followCount = $this->userModel->getFollowCount($targetUser['id']);

        $isFollowing = false;
        if ($userId) {
            $isFollowing = $this->userModel->isFollowing($userId, $targetUser['id']);
        }

        $this->success([
            'user' => $this->formatUser($targetUser, $userId),
            'stats' => [
                'post_count' => $postCount,
                'following' => $followCount['following'] ?? 0,
                'followers' => $followCount['followers'] ?? 0
            ],
            'is_following' => $isFollowing
        ]);
    }

    public function userPosts()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->error('请先登录', 401);
        }

        $targetUserId = (int)$this->input('user_id');
        $page = (int)$this->input('page', 1);
        $pageSize = 15;

        if (!$targetUserId) {
            $this->error('参数错误');
        }

        $postsData = $this->postModel->getUserPosts($targetUserId, $page, $pageSize);
        $posts = $postsData['items'] ?? [];

        $formattedPosts = [];
        foreach ($posts as $post) {
            $formattedPosts[] = $this->formatPost($post, $userId);
        }

        $this->success([
            'items' => $formattedPosts,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'has_more' => count($formattedPosts) >= $pageSize
            ]
        ]);
    }

    public function follow()
    {
        $userId = $this->requireLogin();

        $targetId = (int)$this->input('user_id');
        if (!$targetId) {
            $this->error('参数错误');
        }

        if ($targetId == $userId) {
            $this->error('不能关注自己');
        }

        $targetUser = $this->userModel->find($targetId);
        if (!$targetUser) {
            $this->error('用户不存在');
        }

        $result = $this->userModel->follow($userId, $targetId);

        if ($result) {
            $this->success(null, '关注成功');
        } else {
            $this->error('已关注');
        }
    }

    public function unfollow()
    {
        $userId = $this->requireLogin();

        $targetId = (int)$this->input('user_id');
        if (!$targetId) {
            $this->error('参数错误');
        }

        $result = $this->userModel->unfollow($userId, $targetId);

        if ($result) {
            $this->success(null, '取消关注成功');
        } else {
            $this->error('取消关注失败');
        }
    }

    public function uploadImage()
    {
        $userId = $this->requireLogin();

        if (empty($_FILES['image'])) {
            $this->error('没有上传文件');
        }

        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = '上传失败';
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg = '文件大小超过限制';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg = '文件上传不完整';
                    break;
            }
            $this->error($errorMsg);
        }

        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowedExts)) {
            $this->error('只允许上传图片文件');
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedTypes)) {
                $this->error('文件类型不正确');
            }
        }

        $maxSize = Setting::getMaxImageSize() * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $this->error('文件大小超过' . Setting::getMaxImageSize() . 'MB限制');
        }

        $filename = 'images/' . date('Ymd') . '/' . uniqid() . '.' . $ext;
        $filepath = UPLOAD_PATH . $filename;

        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            chmod($filepath, 0644);
            $this->success([
                'path' => $filename,
                'url' => Helper::uploadUrl($filename)
            ], '上传成功');
        } else {
            $this->error('文件保存失败');
        }
    }

    public function searchUsers()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        $keyword = trim($this->input('keyword'));
        if (empty($keyword)) {
            $this->success(['items' => []]);
        }

        $users = $this->userModel->searchUsers($keyword, 10);
        
        $formattedUsers = [];
        foreach ($users as $user) {
            $formattedUsers[] = $this->formatUser($user, $userId);
        }

        $this->success(['items' => $formattedUsers]);
    }

    public function hotTopics()
    {
        $topics = $this->postModel->getHotTopics(10);
        $this->success(['items' => $topics]);
    }

    public function siteConfig()
    {
        $config = [
            'site_name' => Setting::getSiteName(),
            'subtitle' => Setting::getSubtitle(),
            'max_post_length' => Setting::getMaxPostLength(),
            'max_comment_length' => Setting::getMaxCommentLength(),
            'registration_open' => Setting::isRegistrationOpen(),
            'email_verify_enabled' => Setting::isRegistrationEmailVerifyEnabled(),
            'require_invite_code' => (int)Setting::get('require_invite_code', 0) === 1,
            'guest_access' => Setting::isGuestAccessAllowed()
        ];

        $this->success($config);
    }

    public function sendEmailCode()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('请求方法错误');
        }

        $email = trim($this->input('email'));
        if (empty($email)) {
            $this->error('请输入邮箱地址');
        }

        if (!Security::validateEmail($email)) {
            $this->error('邮箱格式不正确');
        }

        $allowedSuffixes = Setting::getAllowedEmailSuffixes();
        if (!empty($allowedSuffixes)) {
            $emailSuffix = substr($email, strrpos($email, '@') + 1);
            if (!in_array($emailSuffix, $allowedSuffixes)) {
                $this->error('只允许使用以下邮箱后缀：' . implode(', ', $allowedSuffixes));
            }
        }

        $code = rand(100000, 999999);
        
        $mailer = new Mailer();
        $result = $mailer->sendTemplate($email, '注册验证码', 'register_code', [
            'code' => $code,
            'site_name' => Setting::getSiteName()
        ]);

        if ($result) {
            $db = Database::getInstance();
            $db->insert('email_codes', [
                'email' => $email,
                'code' => $code,
                'type' => '注册',
                'created_at' => time(),
                'expires_at' => time() + 600
            ]);
            $this->success(null, '验证码已发送');
        } else {
            $this->error('验证码发送失败');
        }
    }

    private function uploadImages()
    {
        $images = [];
        $files = $_FILES['images'];
        $count = count($files['name']);
        $maxSize = Setting::getMaxImageSize() * 1024 * 1024;

        for ($i = 0; $i < $count && $i < 9; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            if ($files['size'][$i] > $maxSize) {
                continue;
            }

            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (empty($ext) || !in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    
                    $mimeToExt = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'image/webp' => 'webp'
                    ];
                    
                    if (isset($mimeToExt[$mimeType])) {
                        $ext = $mimeToExt[$mimeType];
                        $file['name'] = 'paste_' . time() . '.' . $ext;
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            }

            $filename = 'images/' . date('Ymd') . '/' . uniqid() . '.' . $ext;
            $savePath = UPLOAD_PATH . $filename;

            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $savePath)) {
                chmod($savePath, 0644);
                $images[] = $filename;
            }
        }

        return $images;
    }

    private function sendMentionNotifications($content, $postId, $senderId, $commentId = null)
    {
        $usernames = Helper::extractMentions($content);
        if (empty($usernames)) {
            return;
        }

        $sender = $this->userModel->find($senderId);
        if (!$sender) {
            return;
        }

        $senderName = $sender['username'];

        foreach ($usernames as $username) {
            try {
                $mentionedUser = $this->userModel->findByUsername($username);
                if ($mentionedUser && $mentionedUser['id'] != $senderId) {
                    $this->notificationModel->sendMentionNotification(
                        $mentionedUser['id'],
                        $senderId,
                        $postId,
                        $senderName,
                        $commentId
                    );
                }
            } catch (Exception $e) {}
        }
    }

    private function sendLikeNotification($postUserId, $postId)
    {
        try {
            $sender = $this->userModel->find($_SESSION['user_id']);
            if (!$sender) {
                return;
            }

            $this->notificationModel->sendLikeNotification(
                $postUserId, 
                $_SESSION['user_id'], 
                $postId, 
                $sender['username']
            );
        } catch (\Exception $e) {}
    }

    private function sendFavoriteNotification($postUserId, $postId)
    {
        try {
            $sender = $this->userModel->find($_SESSION['user_id']);
            if (!$sender) {
                return;
            }

            $this->notificationModel->sendFavoriteNotification(
                $postUserId, 
                $_SESSION['user_id'], 
                $postId, 
                $sender['username']
            );
        } catch (\Exception $e) {}
    }
}

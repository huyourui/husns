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
 * 移动端控制器
 * 
 * 提供移动端专属界面
 */
class MobileController extends Controller
{
    private $postModel;
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->postModel = new PostModel();
        $this->userModel = new UserModel();
        $this->view->setTheme('mobile');
    }

    /**
     * 移动端首页
     */
    public function index()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $page = (int)Helper::get('page', 1);
        $tab = Helper::get('tab', 'all');
        $pageSize = 15;

        $posts = $this->postModel->getTimeline($page, $pageSize, $userId, $tab);
        
        foreach ($posts as &$post) {
            $post['content'] = Helper::parseEmojis($post['content']);
            $post['images'] = is_array($post['images']) ? $post['images'] : ($post['images'] ? json_decode($post['images'], true) : []);
        }
        unset($post);

        $unreadCount = 0;
        if ($userId) {
            $notificationModel = new NotificationModel();
            $unreadCount = $notificationModel->getUnreadCount($userId);
        }

        $this->render('index', [
            'posts' => $posts,
            'page' => $page,
            'tab' => $tab,
            'unreadCount' => $unreadCount,
            'currentPage' => 'home',
            'headerTitle' => Setting::getSiteName()
        ]);
    }

    /**
     * 热门页面
     */
    public function hot()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $page = max(1, (int)Helper::get('page', 1));
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
                ORDER BY p.created_at DESC 
                LIMIT ?, ?";

        $posts = $this->db->fetchAll($sql, [$threshold, $offset, $pageSize]);

        foreach ($posts as &$post) {
            $post['images'] = is_array($post['images']) ? $post['images'] : ($post['images'] ? json_decode($post['images'], true) : []);
            $post['time_ago'] = Helper::formatTime($post['created_at']);
            $post['content'] = Helper::parseEmojis(Security::escape($post['content']));
        }
        unset($post);

        $unreadCount = 0;
        if ($userId) {
            $notificationModel = new NotificationModel();
            $unreadCount = $notificationModel->getUnreadCount($userId);
        }

        $this->render('hot', [
            'posts' => $posts,
            'page' => $page,
            'unreadCount' => $unreadCount,
            'currentPage' => 'hot',
            'headerTitle' => '热门'
        ]);
    }

    /**
     * 登录页面
     */
    public function login()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect(Helper::url('mobile'));
            return;
        }

        if (Helper::isPost()) {
            $username = trim(Helper::post('username'));
            $password = Helper::post('password');

            if (empty($username) || empty($password)) {
                Helper::setFlash('error', '请填写用户名和密码');
                $this->redirect(Helper::url('mobile/login'));
                return;
            }

            $user = $this->userModel->login($username, $password);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];

                session_regenerate_id(true);

                Helper::setFlash('success', '登录成功');
                $this->redirect(Helper::url('mobile'));
            } else {
                Helper::setFlash('error', '用户名或密码错误');
                $this->redirect(Helper::url('mobile/login'));
            }
            return;
        }

        $this->view->setLayout('main');
        $this->render('login', [
            'hideTabBar' => true,
            'headerTitle' => '登录'
        ]);
    }

    /**
     * 注册页面
     */
    public function register()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect(Helper::url('mobile'));
            return;
        }

        if (!Setting::isRegistrationOpen()) {
            Helper::setFlash('error', '注册功能已关闭');
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $emailVerifyEnabled = Setting::isRegistrationEmailVerifyEnabled();
        $requireInviteCode = (int)Setting::get('require_invite_code', 0) === 1;

        if (Helper::isPost()) {
            $username = trim(Helper::post('username'));
            $password = Helper::post('password');
            $confirmPassword = Helper::post('confirm_password');
            $email = trim(Helper::post('email'));
            $emailCode = trim(Helper::post('email_code'));
            $inviteCode = trim(Helper::post('invite_code'));

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

            $bannedUsernames = Setting::getBannedUsernames();
            if (!empty($bannedUsernames)) {
                $usernameLower = strtolower($username);
                foreach ($bannedUsernames as $banned) {
                    if (strpos($usernameLower, strtolower($banned)) !== false) {
                        $errors[] = '用户名包含禁用字符';
                        break;
                    }
                }
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
                    $validCode = InviteController::verifyCode($inviteCode);
                    if (!$validCode) {
                        $errors[] = '邀请码无效或已被使用';
                    }
                }
            }

            if (!empty($errors)) {
                Helper::setFlash('error', implode('，', $errors));
                $this->redirect(Helper::url('mobile/register'));
                return;
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

                Hook::trigger('user_register', ['id' => $userId, 'username' => $username, 'email' => $email]);
                Hook::trigger('user_login', ['id' => $userId, 'username' => $username]);

                Helper::setFlash('success', '注册成功');
                $this->redirect(Helper::url('mobile'));
            } else {
                Helper::setFlash('error', '注册失败');
                $this->redirect(Helper::url('mobile/register'));
            }
            return;
        }

        $this->render('register', [
            'hideTabBar' => true,
            'headerTitle' => '注册',
            'emailVerifyEnabled' => $emailVerifyEnabled,
            'requireInviteCode' => $requireInviteCode
        ]);
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        session_destroy();
        $this->redirect(Helper::url('mobile/login'));
    }

    /**
     * 发布微博页面
     */
    public function publish()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $this->render('publish', [
            'currentPage' => 'publish',
            'headerTitle' => '发布微博',
            'headerRight' => '<button type="submit" form="publishForm" class="m-btn-primary" style="background:none;border:none;color:var(--primary-color);font-weight:600;">发布</button>'
        ]);
    }

    /**
     * 微博详情页
     */
    public function detail()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $id = (int)Helper::get('id');

        if (!$id) {
            $this->redirect(Helper::url('mobile'));
            return;
        }

        $post = $this->postModel->getPost($id, $userId ? $userId : 0);

        if (!$post || $post['status'] != 1) {
            Helper::setFlash('error', '微博不存在');
            $this->redirect(Helper::url('mobile'));
            return;
        }

        $post['formatted_content'] = Security::escape($post['content']);
        $post['formatted_content'] = preg_replace('/#(.+?)#/', '<a href="' . Helper::url('mobile/topic?keyword=$1') . '">#$1#</a>', $post['formatted_content']);
        $post['formatted_content'] = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|$)/u', '<a href="' . Helper::url('mobile/user?username=$1') . '">@$1</a>', $post['formatted_content']);
        $post['formatted_content'] = Helper::parseEmojis($post['formatted_content']);
        $post['images'] = is_array($post['images']) ? $post['images'] : ($post['images'] ? json_decode($post['images'], true) : []);

        $comments = $this->postModel->getComments($id);

        $unreadCount = 0;
        if ($userId) {
            $notificationModel = new NotificationModel();
            $unreadCount = $notificationModel->getUnreadCount($userId);
        }

        $this->render('detail', [
            'post' => $post,
            'comments' => $comments,
            'unreadCount' => $unreadCount,
            'showBack' => true,
            'headerTitle' => '微博详情'
        ]);
    }

    /**
     * 消息通知页面
     */
    public function notification()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $page = (int)Helper::get('page', 1);
        $pageSize = 20;

        $notificationModel = new NotificationModel();
        $notifications = $notificationModel->getUserNotifications($_SESSION['user_id'], $page, $pageSize);
        $unreadCount = $notificationModel->getUnreadCount($_SESSION['user_id']);

        $this->render('notification', [
            'notifications' => $notifications,
            'page' => $page,
            'unreadCount' => $unreadCount,
            'currentPage' => 'notification',
            'headerTitle' => '消息'
        ]);
    }

    /**
     * 个人中心页面
     */
    public function profile()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $user = $this->userModel->find($_SESSION['user_id']);
        $postCount = $this->postModel->count('user_id = ? AND status = 1', [$_SESSION['user_id']]);
        $followCount = $this->userModel->getFollowCount($_SESSION['user_id']);

        $page = (int)Helper::get('page', 1);
        $pageSize = 10;
        $posts = $this->postModel->getUserPosts($_SESSION['user_id'], $page, $pageSize);

        foreach ($posts as &$post) {
            $post['content'] = Helper::parseEmojis($post['content']);
            $post['images'] = is_array($post['images']) ? $post['images'] : ($post['images'] ? json_decode($post['images'], true) : []);
        }
        unset($post);

        $notificationModel = new NotificationModel();
        $unreadCount = $notificationModel->getUnreadCount($_SESSION['user_id']);

        $this->render('profile', [
            'user' => $user,
            'postCount' => $postCount,
            'followCount' => $followCount,
            'posts' => $posts,
            'page' => $page,
            'unreadCount' => $unreadCount,
            'currentPage' => 'profile',
            'headerTitle' => '我的'
        ]);
    }

    /**
     * 用户主页
     */
    public function user()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $username = Helper::get('username');
        $targetId = (int)Helper::get('id');

        if ($username) {
            $targetUser = $this->userModel->findByUsername($username);
        } elseif ($targetId) {
            $targetUser = $this->userModel->find($targetId);
        } else {
            $this->redirect(Helper::url('mobile'));
            return;
        }

        if (!$targetUser) {
            Helper::setFlash('error', '用户不存在');
            $this->redirect(Helper::url('mobile'));
            return;
        }

        $postCount = $this->postModel->count('user_id = ? AND status = 1', [$targetUser['id']]);
        $followCount = $this->userModel->getFollowCount($targetUser['id']);

        $page = (int)Helper::get('page', 1);
        $pageSize = 10;
        $posts = $this->postModel->getUserPosts($targetUser['id'], $page, $pageSize);

        foreach ($posts as &$post) {
            $post['content'] = Helper::parseEmojis($post['content']);
            $post['images'] = is_array($post['images']) ? $post['images'] : ($post['images'] ? json_decode($post['images'], true) : []);
        }
        unset($post);

        $isFollowing = false;
        if ($userId) {
            $isFollowing = $this->userModel->isFollowing($userId, $targetUser['id']);
        }

        $unreadCount = 0;
        if ($userId) {
            $notificationModel = new NotificationModel();
            $unreadCount = $notificationModel->getUnreadCount($userId);
        }

        $this->render('user', [
            'targetUser' => $targetUser,
            'postCount' => $postCount,
            'followCount' => $followCount,
            'posts' => $posts,
            'page' => $page,
            'isFollowing' => $isFollowing,
            'unreadCount' => $unreadCount,
            'showBack' => true,
            'headerTitle' => $targetUser['username']
        ]);
    }

    /**
     * 话题页面
     */
    public function topic()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $keyword = trim(Helper::get('keyword'));
        
        if (empty($keyword)) {
            $this->redirect(Helper::url('mobile'));
            return;
        }

        $page = (int)Helper::get('page', 1);
        $pageSize = 15;

        $posts = $this->postModel->getPostsByTopic($keyword, $page, $pageSize);

        foreach ($posts as &$post) {
            $post['content'] = Helper::parseEmojis($post['content']);
            $post['images'] = is_array($post['images']) ? $post['images'] : ($post['images'] ? json_decode($post['images'], true) : []);
        }
        unset($post);

        $unreadCount = 0;
        if ($userId) {
            $notificationModel = new NotificationModel();
            $unreadCount = $notificationModel->getUnreadCount($userId);
        }

        $this->render('topic', [
            'keyword' => $keyword,
            'posts' => $posts,
            'page' => $page,
            'unreadCount' => $unreadCount,
            'showBack' => true,
            'headerTitle' => '#' . $keyword . '#'
        ]);
    }

    /**
     * 设置页面
     */
    public function settings()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $user = $this->userModel->find($_SESSION['user_id']);

        $this->render('settings', [
            'user' => $user,
            'showBack' => true,
            'headerTitle' => '设置'
        ]);
    }

    /**
     * 渲染视图
     */
    protected function render($template, $data = [])
    {
        echo $this->view->render($template, $data);
    }
}

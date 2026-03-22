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
class UserController extends Controller
{
    private $userModel;
    private $notificationModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->notificationModel = new NotificationModel();
    }

    public function login()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect(Helper::url());
        }

        if ($this->isPost()) {
            if (!Helper::verifyCsrf()) {
                $this->setFlash('error', '安全验证失败，请重试');
                $this->redirect(Helper::url('user/login'));
            }

            $username = trim(Helper::post('username'));
            $password = Helper::post('password');
            $remember = (int)Helper::post('remember', 0);

            if (empty($username) || empty($password)) {
                $this->setFlash('error', '请填写用户名和密码');
                $this->redirect(Helper::url('user/login'));
            }

            $user = $this->userModel->login($username, $password);

            if (!$user) {
                $this->setFlash('error', '用户名或密码错误');
                $this->redirect(Helper::url('user/login'));
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            unset($_SESSION['admin_auth_time_' . $user['id']]);

            if ($remember) {
                $this->setRememberCookie($user['id']);
            }

            Hook::trigger('user_login', $user);

            $this->setFlash('success', '登录成功');
            $this->redirect(Helper::url());
        }

        $this->render('user/login');
    }

    private function setRememberCookie($userId)
    {
        $token = bin2hex(random_bytes(32));
        $expires = time() + 30 * 24 * 3600;
        
        $this->userModel->update($userId, [
            'remember_token' => $token,
            'updated_at' => time()
        ]);
        
        setcookie('remember_token', $userId . ':' . $token, $expires, '/', '', false, true);
    }

    public function register()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect(Helper::url());
        }

        if (!Setting::isRegistrationOpen()) {
            $this->setFlash('error', '注册功能已关闭');
            $this->redirect(Helper::url('user/login'));
        }

        if ($this->isPost()) {
            if (!Helper::verifyCsrf()) {
                $this->setFlash('error', '安全验证失败，请重试');
                $this->redirect(Helper::url('user/register'));
            }

            $username = trim(Helper::post('username'));
            $password = Helper::post('password');
            $confirmPassword = Helper::post('confirm_password');
            $email = trim(Helper::post('email'));
            $emailCode = trim(Helper::post('email_code'));

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

            if (!Security::validateEmail($email)) {
                $errors[] = '邮箱格式不正确';
            }

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

            if (Setting::isRegistrationEmailVerifyEnabled()) {
                if (empty($emailCode)) {
                    $errors[] = '请输入邮箱验证码';
                } elseif (!Mailer::verifyCode($email, $emailCode, '注册')) {
                    $errors[] = '验证码错误或已过期';
                }
            }
            
            $inviteCode = trim(Helper::post('invite_code', ''));
            $requireInviteCode = (int)Setting::get('require_invite_code', 0) === 1;
            
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
                $this->setFlash('error', implode('，', $errors));
                $this->redirect(Helper::url('user/register'));
            }

            $userId = $this->userModel->register([
                'username' => $username,
                'password' => $password,
                'email' => $email
            ]);

            if ($userId) {
                if ($requireInviteCode && !empty($inviteCode)) {
                    InviteController::useCode($inviteCode, $userId);
                }
                
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['is_admin'] = 0;
                
                Hook::trigger('user_register', ['id' => $userId, 'username' => $username, 'email' => $email]);
                Hook::trigger('user_login', ['id' => $userId, 'username' => $username]);
                
                $this->setFlash('success', '注册成功');
                $this->redirect(Helper::url());
            } else {
                $this->setFlash('error', '注册失败，请重试');
                $this->redirect(Helper::url('user/register'));
            }
        }

        $emailVerifyEnabled = Setting::isRegistrationEmailVerifyEnabled();
        $requireInviteCode = (int)Setting::get('require_invite_code', 0) === 1;
        $this->render('user/register', ['emailVerifyEnabled' => $emailVerifyEnabled, 'requireInviteCode' => $requireInviteCode]);
    }

    
    public function sendEmailCode()
    {
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        if (!Mailer::isEnabled()) {
            Helper::jsonError('邮件服务未启用，请联系管理员');
        }
        
        $email = trim(Helper::post('email'));
        
        if (!Security::validateEmail($email)) {
            Helper::jsonError('邮箱格式不正确');
        }
        
        $allowedSuffixes = Setting::getAllowedEmailSuffixes();
        if (!empty($allowedSuffixes)) {
            $emailSuffix = substr($email, strrpos($email, '@') + 1);
            if (!in_array($emailSuffix, $allowedSuffixes)) {
                Helper::jsonError('只允许使用以下邮箱后缀：' . implode(', ', $allowedSuffixes));
            }
        }
        
        if ($this->userModel->findByEmail($email)) {
            Helper::jsonError('邮箱已被注册');
        }
        
        try {
            $result = Mailer::sendVerificationCode($email, '注册');
            
            if ($result['success']) {
                Helper::jsonSuccess(null, '验证码已发送');
            } else {
                Helper::jsonError($result['message']);
            }
        } catch (Exception $e) {
            Helper::jsonError('发送失败：' . $e->getMessage());
        }
    }

    public function logout()
    {
        Hook::trigger('user_logout', ['user_id' => $_SESSION['user_id'] ?? null]);
        
        if (isset($_SESSION['user_id'])) {
            $this->userModel->update($_SESSION['user_id'], ['remember_token' => '']);
        }
        
        setcookie('remember_token', '', time() - 3600, '/');
        session_destroy();
        $this->redirect(Helper::url());
    }

    public function banned()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect(Helper::url());
        }
        
        $banInfo = $this->userModel->getBanInfo($_SESSION['user_id']);
        
        if (!$banInfo || $banInfo['type'] !== 2) {
            $this->redirect(Helper::url());
        }
        
        $this->view->setLayout(null);
        $this->render('user/banned', ['banInfo' => $banInfo]);
    }

    public function profile()
    {
        $currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$currentUserId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url());
        }
        
        $userId = Helper::get('id');
        $username = Helper::get('username');
        
        if ($userId) {
            $user = $this->userModel->find($userId);
        } elseif ($username) {
            $user = $this->userModel->findByUsername($username);
        } elseif ($currentUserId) {
            $user = $this->userModel->find($currentUserId);
        } else {
            $this->redirect(Helper::url());
        }
        
        if (!$user) {
            $this->setFlash('error', '用户不存在');
            $this->redirect(Helper::url());
        }
        
        $banInfo = $this->userModel->getBanInfo($user['id']);
        if ($banInfo && $banInfo['type'] === 2) {
            $this->render('user/profile_banned', ['user' => $user, 'banInfo' => $banInfo]);
            return;
        }
        
        $userId = $user['id'];

        $followCount = $this->userModel->getFollowCount($userId);
        
        $postModel = new PostModel();
        $page = (int)Helper::get('page', 1);
        $posts = $postModel->getUserPosts($userId, $page);

        $isSelf = $currentUserId && ($userId == $currentUserId);
        $isFollowing = $currentUserId && !$isSelf && $this->userModel->isFollowing($currentUserId, $userId);

        $this->render('user/profile', [
            'user' => $user,
            'followCount' => $followCount,
            'posts' => $posts,
            'isSelf' => $isSelf,
            'isFollowing' => $isFollowing
        ]);
    }

    public function settings()
    {
        $this->checkLogin();
        
        $user = $this->getCurrentUser();

        if ($this->isPost()) {
            if (!Helper::verifyCsrf()) {
                $this->setFlash('error', '安全验证失败');
                $this->redirect(Helper::url('user/settings'));
            }

            $username = trim(Helper::post('username'));
            $bio = trim(Helper::post('bio'));

            if (empty($username)) {
                $this->setFlash('error', '用户名不能为空');
                $this->redirect(Helper::url('user/settings'));
            }

            $minLength = Setting::getUsernameMinLength();
            $maxLength = Setting::getUsernameMaxLength();
            $usernameLength = mb_strlen($username, 'UTF-8');
            
            if ($usernameLength < $minLength || $usernameLength > $maxLength) {
                $this->setFlash('error', '用户名长度需在' . $minLength . '-' . $maxLength . '个字符之间');
                $this->redirect(Helper::url('user/settings'));
            }
            
            if (!preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]+$/u', $username)) {
                $this->setFlash('error', '用户名只能包含字母、数字、下划线和中文');
                $this->redirect(Helper::url('user/settings'));
            }

            if ($username !== $user['username']) {
                $existing = $this->userModel->findByUsername($username);
                if ($existing) {
                    $this->setFlash('error', '用户名已被使用');
                    $this->redirect(Helper::url('user/settings'));
                }

                $bannedUsernames = Setting::getBannedUsernames();
                if (!empty($bannedUsernames)) {
                    $usernameLower = strtolower($username);
                    foreach ($bannedUsernames as $banned) {
                        if (strpos($usernameLower, strtolower($banned)) !== false) {
                            $this->setFlash('error', '用户名包含禁用字符');
                            $this->redirect(Helper::url('user/settings'));
                        }
                    }
                }
            }

            $data = [
                'username' => $username,
                'bio' => $bio
            ];

            if (isset($_FILES['avatar']) && !empty($_FILES['avatar']['tmp_name'])) {
                $upload = $this->uploadAvatar();
                if ($upload['success']) {
                    $data['avatar'] = $upload['path'];
                } else {
                    $this->setFlash('error', '头像上传失败：' . $upload['error']);
                    $this->redirect(Helper::url('user/settings'));
                    return;
                }
            }

            $result = $this->userModel->updateProfile($user['id'], $data);
            if ($result) {
                $this->setFlash('success', '资料更新成功');
            } else {
                $this->setFlash('error', '资料更新失败');
            }
            $this->redirect(Helper::url('user/settings'));
        }

        $this->render('user/settings', ['user' => $user]);
    }

    public function password()
    {
        $this->checkLogin();
        
        if ($this->isPost()) {
            if (!Helper::verifyCsrf()) {
                $this->setFlash('error', '安全验证失败');
                $this->redirect(Helper::url('user/password'));
            }

            $oldPassword = Helper::post('old_password');
            $newPassword = Helper::post('new_password');
            $confirmPassword = Helper::post('confirm_password');

            if (strlen($newPassword) < 6) {
                $this->setFlash('error', '新密码长度至少6位');
                $this->redirect(Helper::url('user/password'));
            }

            if ($newPassword !== $confirmPassword) {
                $this->setFlash('error', '两次密码输入不一致');
                $this->redirect(Helper::url('user/password'));
            }

            $user = $this->getCurrentUser();
            $result = $this->userModel->updatePassword($user['id'], $oldPassword, $newPassword);

            if ($result) {
                $this->setFlash('success', '密码修改成功');
            } else {
                $this->setFlash('error', '原密码错误');
            }
            $this->redirect(Helper::url('user/password'));
        }

        $this->render('user/password');
    }

    public function follow()
    {
        $this->checkLogin();
        
        if (!$this->checkActionInterval()) {
            $remaining = $this->getRemainingInterval();
            $this->jsonError('操作过于频繁，请' . $remaining . '秒后再试');
        }
        
        $targetId = (int)Helper::post('user_id');
        $result = $this->userModel->follow($_SESSION['user_id'], $targetId);

        if ($result) {
            Point::change($_SESSION['user_id'], 'follow_user', 'user', $targetId);
            
            $sender = $this->userModel->find($_SESSION['user_id']);
            $this->notificationModel->sendFollowNotification($targetId, $_SESSION['user_id'], $sender['username']);
        }

        if ($this->isAjax()) {
            if ($result) {
                $this->jsonSuccess(['following' => true]);
            } else {
                $this->jsonError('关注失败');
            }
        }

        $this->redirect(Helper::url('user/profile?id=' . $targetId));
    }

    public function unfollow()
    {
        $this->checkLogin();
        
        $targetId = (int)Helper::post('user_id');
        $result = $this->userModel->unfollow($_SESSION['user_id'], $targetId);

        if ($this->isAjax()) {
            if ($result) {
                $this->jsonSuccess(['following' => false]);
            } else {
                $this->jsonError('取消关注失败');
            }
        }

        $this->redirect(Helper::url('user/profile?id=' . $targetId));
    }

    private function uploadAvatar()
    {
        $maxSize = Setting::getMaxAvatarSize() * 1024 * 1024;
        
        if ($_FILES['avatar']['size'] > $maxSize) {
            return ['success' => false, 'error' => '图片大小超过限制（最大' . Setting::getMaxAvatarSize() . 'MB）'];
        }

        $check = Security::checkFileUpload($_FILES['avatar']);
        
        if (!$check['valid']) {
            return ['success' => false, 'error' => $check['error']];
        }

        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $filename = 'avatars/' . date('Ymd') . '/' . uniqid() . '.' . $ext;
        $savePath = UPLOAD_PATH . $filename;
        
        $dir = dirname($savePath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                return ['success' => false, 'error' => '无法创建上传目录'];
            }
        }
        
        if (!is_writable($dir)) {
            chmod($dir, 0777);
        }

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $savePath)) {
            chmod($savePath, 0644);
            return ['success' => true, 'path' => $filename];
        }

        return ['success' => false, 'error' => '文件保存失败'];
    }

    public function favorites()
    {
        $this->checkLogin();
        
        $currentUserId = $_SESSION['user_id'];
        $userId = Helper::get('id');
        
        if (!$userId || $userId != $currentUserId) {
            $userId = $currentUserId;
        }
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            $this->setFlash('error', '用户不存在');
            $this->redirect(Helper::url());
        }
        
        $favoriteModel = new FavoriteModel();
        $page = (int)Helper::get('page', 1);
        $pageSize = 10;
        
        $favorites = $favoriteModel->getUserFavorites($userId, $page, $pageSize);
        $totalFavorites = $favoriteModel->countUserFavorites($userId);
        $totalPages = ceil($totalFavorites / $pageSize);
        
        $followCount = $this->userModel->getFollowCount($userId);
        
        $this->render('user/favorites', [
            'user' => $user,
            'favorites' => $favorites,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalFavorites' => $totalFavorites,
            'followCount' => $followCount,
            'isSelf' => ($userId == $currentUserId)
        ]);
    }

    public function suggest()
    {
        $this->checkLogin();
        
        $keyword = trim(Helper::get('keyword', ''));
        
        if (strlen($keyword) < 1) {
            $this->jsonSuccess(['users' => []]);
        }
        
        $scope = Setting::getMentionSuggestScope();
        $userId = $_SESSION['user_id'];
        
        if ($scope === 'following') {
            $users = $this->userModel->searchFollowing($userId, $keyword, 10);
        } else {
            $users = $this->userModel->searchUsers($keyword, 10);
        }
        
        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'avatar' => Helper::avatar($user['avatar'] ?? null, $user['username'], 'small')
            ];
        }
        
        $this->jsonSuccess(['users' => $result]);
    }
}

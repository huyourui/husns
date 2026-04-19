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
 * 移动端控制器（基于API的新版本）
 * 
 * 提供移动端页面渲染，数据通过前端JavaScript调用API获取
 */
class MobileController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->view->setTheme('mobile');
        $this->view->setLayout('app');
    }

    public function index()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $tab = Helper::get('tab', 'all');

        $this->render('app/index', [
            'tab' => $tab,
            'currentPage' => 'home',
            'headerTitle' => Setting::getSiteName()
        ]);
    }

    public function hot()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $this->render('app/hot', [
            'currentPage' => 'hot',
            'headerTitle' => '热门'
        ]);
    }

    public function login()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect(Helper::url('mobile'));
            return;
        }

        $this->render('app/login', [
            'hideTabBar' => true,
            'headerTitle' => '登录'
        ]);
    }

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

        $this->render('app/register', [
            'hideTabBar' => true,
            'headerTitle' => '注册',
            'emailVerifyEnabled' => $emailVerifyEnabled,
            'requireInviteCode' => $requireInviteCode
        ]);
    }

    public function logout()
    {
        session_destroy();
        $this->redirect(Helper::url('mobile/login'));
    }

    public function publish()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $this->render('app/publish', [
            'currentPage' => 'publish',
            'headerTitle' => '发布微博',
            'showBack' => true
        ]);
    }

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

        $this->render('app/detail', [
            'id' => $id,
            'showBack' => true,
            'headerTitle' => '微博详情'
        ]);
    }

    public function notification()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $this->render('app/notification', [
            'currentPage' => 'notification',
            'headerTitle' => '消息'
        ]);
    }

    public function profile()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $this->render('app/profile', [
            'currentPage' => 'profile',
            'headerTitle' => '我的'
        ]);
    }

    public function user()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url('mobile/login'));
            return;
        }

        $username = Helper::get('username');
        $targetId = (int)Helper::get('id');

        if (!$username && !$targetId) {
            $this->redirect(Helper::url('mobile'));
            return;
        }

        $this->render('app/user', [
            'userId' => $targetId,
            'username' => $username,
            'showBack' => true,
            'headerTitle' => $username ?: '用户主页'
        ]);
    }

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

        $this->render('app/topic', [
            'keyword' => $keyword,
            'showBack' => true,
            'headerTitle' => '#' . $keyword . '#'
        ]);
    }

    protected function render($template, $data = [])
    {
        echo $this->view->render($template, $data);
    }
}

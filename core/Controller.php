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
 * 控制器基类
 * 
 * 所有业务控制器都应继承此类，提供：
 * - 视图渲染
 * - 数据库访问
 * - 日志记录
 * - 通用辅助方法
 * 
 * 通过依赖注入容器获取服务实例，降低耦合度
 */
class Controller
{
    /**
     * 视图实例
     * @var View
     */
    protected $view;

    /**
     * 数据库实例
     * @var Database
     */
    protected $db;

    /**
     * 日志实例
     * @var Logger
     */
    protected $logger;

    /**
     * 构造函数
     * 
     * 通过依赖注入容器获取服务实例
     */
    public function __construct()
    {
        // 通过容器获取视图实例（如果容器中有则使用容器，否则直接创建）
        $this->view = Container::has('view') ? Container::make('view') : new View();
        
        // 通过容器获取数据库实例
        $this->db = Container::has('db') ? Container::make('db') : Database::getInstance();
        
        // 通过容器获取日志实例
        $this->logger = Container::has('logger') ? Container::make('logger') : null;
    }

    /**
     * 渲染视图模板
     * 
     * @param string $template 模板名称
     * @param array $data 模板数据
     * @return void
     */
    protected function render($template, $data = [])
    {
        // 触发渲染前钩子
        $data = Hook::trigger('before_render', $data);
        
        // 记录调试日志
        if ($this->logger) {
            $this->logger->debug('渲染模板: ' . $template, ['data_keys' => array_keys($data)]);
        }
        
        echo $this->view->render($template, $data);
    }

    protected function redirect($url)
    {
        Helper::redirect($url);
    }

    protected function json($data, $code = 200)
    {
        Helper::json($data, $code);
    }

    protected function jsonSuccess($data = null, $message = '操作成功')
    {
        Helper::jsonSuccess($data, $message);
    }

    protected function jsonError($message = '操作失败', $code = 1)
    {
        Helper::jsonError($message, $code);
    }

    protected function isPost()
    {
        return Helper::isPost();
    }

    protected function isAjax()
    {
        return Helper::isAjax();
    }

    protected function checkLogin()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            if ($this->isAjax()) {
                $this->jsonError('请先登录', 401);
            }
            $this->redirect(Helper::url('user/login'));
            return false;
        }
        return true;
    }

    protected function checkAdmin()
    {
        $this->checkLogin();
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            if ($this->isAjax()) {
                $this->jsonError('无权限访问', 403);
            }
            $this->redirect(Helper::url());
        }
        
        $this->checkAdminAuth();
    }
    
    protected function checkAdminAuth()
    {
        $authExpire = 1800;
        $authKey = 'admin_auth_time_' . $_SESSION['user_id'];
        
        if (isset($_SESSION[$authKey]) && (time() - $_SESSION[$authKey]) < $authExpire) {
            $_SESSION[$authKey] = time();
            return;
        }
        
        $currentRoute = Helper::get('r', '');
        if ($currentRoute === 'admin/verify') {
            return;
        }
        
        if ($this->isAjax()) {
            $this->jsonError('请重新验证密码', 403);
        }
        
        $this->redirect(Helper::url('admin/verify'));
    }

    protected function checkActionInterval()
    {
        $interval = Setting::getActionInterval();
        if ($interval <= 0) {
            return true;
        }

        $key = 'last_action_time_' . $_SESSION['user_id'];
        $lastTime = isset($_SESSION[$key]) ? $_SESSION[$key] : 0;
        $currentTime = time();

        if ($currentTime - $lastTime < $interval) {
            return false;
        }

        $_SESSION[$key] = $currentTime;
        return true;
    }

    protected function getRemainingInterval()
    {
        $interval = Setting::getActionInterval();
        if ($interval <= 0) {
            return 0;
        }

        $key = 'last_action_time_' . $_SESSION['user_id'];
        $lastTime = isset($_SESSION[$key]) ? $_SESSION[$key] : 0;
        $remaining = $interval - (time() - $lastTime);

        return max(0, $remaining);
    }

    protected function getCurrentUser()
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $userModel = new UserModel();
        return $userModel->find($_SESSION['user_id']);
    }

    protected function setFlash($type, $message)
    {
        Helper::setFlash($type, $message);
    }

    protected function getFlash($type)
    {
        return Helper::getFlash($type);
    }
}

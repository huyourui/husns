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
class App
{
    private $controller;
    private $action;
    private $params = [];
    private $controllerMap = [
        'IndexController' => 'content/post/PostController.php',
        'UserController' => 'content/user/UserController.php',
        'PostController' => 'content/post/PostController.php',
        'AdminController' => 'admin/AdminController.php',
        'PluginController' => 'admin/PluginController.php',
        'UpgradeController' => 'admin/UpgradeController.php',
        'NotificationController' => 'content/notification/NotificationController.php',
        'AnnouncementController' => 'content/announcement/AnnouncementController.php',
        'PointController' => 'content/point/PointController.php',
        'LinkController' => 'content/link/LinkController.php',
        'DownloadController' => 'content/download/DownloadController.php',
        'TopicController' => 'content/topic/TopicController.php',
    ];

    public function run()
    {
        Hook::loadPlugins();
        Hook::trigger('app_start');

        $this->autoLogin();
        $this->checkBanned();
        $this->parseUrl();
        $this->route();

        Hook::trigger('app_end');
    }
    
    private function checkBanned()
    {
        if (!isset($_SESSION['user_id'])) {
            return;
        }
        
        $userModel = new UserModel();
        $banInfo = $userModel->getBanInfo($_SESSION['user_id']);
        
        if (!$banInfo || $banInfo['type'] !== 2) {
            return;
        }
        
        $route = isset($_GET['r']) ? $_GET['r'] : '';
        
        if ($route === 'user/logout') {
            return;
        }
        
        if ($route === 'user/banned') {
            return;
        }
        
        header('Location: ' . Helper::url('user/banned'));
        exit;
    }

    private function autoLogin()
    {
        if (isset($_SESSION['user_id'])) {
            return;
        }

        if (!isset($_COOKIE['remember_token'])) {
            return;
        }

        $parts = explode(':', $_COOKIE['remember_token'], 2);
        if (count($parts) !== 2) {
            return;
        }

        $userId = (int)$parts[0];
        $token = $parts[1];

        if ($userId <= 0 || empty($token)) {
            return;
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user || $user['status'] != 1 || $user['remember_token'] !== $token) {
            setcookie('remember_token', '', time() - 3600, '/');
            return;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
    }

    private function parseUrl()
    {
        if (isset($_GET['r']) && !empty($_GET['r'])) {
            $path = trim($_GET['r'], '/');
        } else {
            $scriptName = dirname($_SERVER['SCRIPT_NAME']);
            $scriptName = str_replace('\\', '/', $scriptName);
            $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            
            $path = str_replace($scriptName, '', $requestUri);
            $path = trim($path, '/');
            
            if (strpos($path, 'index.php/') === 0) {
                $path = substr($path, strlen('index.php/'));
            } elseif ($path === 'index.php') {
                $path = '';
            }
        }
        
        if (empty($path)) {
            $this->controller = 'IndexController';
            $this->action = 'index';
            return;
        }

        $segments = explode('/', $path);
        
        $controllerName = ucfirst($segments[0]) . 'Controller';
        
        if (isset($this->controllerMap[$controllerName])) {
            $this->controller = $controllerName;
            array_shift($segments);
        } else {
            $controllerFile = ROOT_PATH . 'content/' . $segments[0] . '/' . $controllerName . '.php';
            
            if (file_exists($controllerFile)) {
                $this->controller = $controllerName;
                array_shift($segments);
            } else {
                $this->controller = 'IndexController';
            }
        }

        if (!empty($segments)) {
            $this->action = $segments[0];
            array_shift($segments);
        } else {
            $this->action = 'index';
        }

        $this->params = $segments;
    }

    private function route()
    {
        if (isset($this->controllerMap[$this->controller])) {
            $file = ROOT_PATH . $this->controllerMap[$this->controller];
        } else {
            $dir = strtolower(str_replace('Controller', '', $this->controller));
            $file = ROOT_PATH . 'content/' . $dir . '/' . $this->controller . '.php';
        }

        if (!file_exists($file)) {
            $this->notFound();
        }

        require_once $file;

        if (!class_exists($this->controller)) {
            $this->notFound();
        }

        $controller = new $this->controller();

        if (!method_exists($controller, $this->action)) {
            $this->notFound();
        }

        Hook::trigger('before_controller', [
            'controller' => $this->controller,
            'action' => $this->action
        ]);

        call_user_func_array([$controller, $this->action], $this->params);
    }

    private function notFound()
    {
        header('HTTP/1.0 404 Not Found');
        if (file_exists(ROOT_PATH . 'templates/default/error/404.php')) {
            $view = new View();
            echo $view->render('error/404');
        } else {
            echo '<h1>404 - 页面未找到</h1>';
        }
        exit;
    }
}

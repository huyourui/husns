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
 * 定义核心常量
 */
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)) . '/');
define('APP_VERSION', '2.2.2');
define('PHP_VERSION_MIN', '7.4.0');

/**
 * 检查PHP版本
 */
if (version_compare(PHP_VERSION, PHP_VERSION_MIN, '<')) {
    die('PHP版本需要' . PHP_VERSION_MIN . '以上，当前版本：' . PHP_VERSION);
}

/**
 * 检查是否需要安装
 */
if (!file_exists(ROOT_PATH . 'install.lock')) {
    $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    header('Location: ' . $baseUrl . '/install.php');
    exit;
}

/**
 * 加载配置文件
 */
require_once ROOT_PATH . 'config.php';

/**
 * 定义日志路径常量（如果配置文件中未定义）
 */
if (!defined('LOG_PATH')) {
    define('LOG_PATH', ROOT_PATH . 'logs' . DIRECTORY_SEPARATOR);
}

/**
 * 加载核心文件
 */
require_once ROOT_PATH . 'core/Helper.php';
require_once ROOT_PATH . 'core/Security.php';
require_once ROOT_PATH . 'core/Database.php';
require_once ROOT_PATH . 'core/Upgrade.php';
require_once ROOT_PATH . 'core/Setting.php';
require_once ROOT_PATH . 'core/Hook.php';
require_once ROOT_PATH . 'core/View.php';
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'core/Controller.php';
require_once ROOT_PATH . 'core/CommentHelper.php';
require_once ROOT_PATH . 'core/Point.php';
require_once ROOT_PATH . 'core/Mailer.php';

/**
 * 加载新组件
 */
require_once ROOT_PATH . 'core/Container.php';
require_once ROOT_PATH . 'core/Logger.php';
require_once ROOT_PATH . 'core/ExceptionHandler.php';
require_once ROOT_PATH . 'core/App.php';

/**
 * 注册异常处理器
 * 捕获所有未处理的异常和错误
 */
ExceptionHandler::register();

/**
 * 注册自动加载器
 */
spl_autoload_register(function($class) {
    $map = [
        'UserModel' => ROOT_PATH . 'content/user/UserModel.php',
        'UserController' => ROOT_PATH . 'content/user/UserController.php',
        'PostModel' => ROOT_PATH . 'content/post/PostModel.php',
        'PostController' => ROOT_PATH . 'content/post/PostController.php',
        'AdminController' => ROOT_PATH . 'admin/AdminController.php',
        'PluginController' => ROOT_PATH . 'admin/PluginController.php',
        'NotificationModel' => ROOT_PATH . 'content/notification/NotificationModel.php',
        'NotificationController' => ROOT_PATH . 'content/notification/NotificationController.php',
        'AnnouncementModel' => ROOT_PATH . 'content/announcement/AnnouncementModel.php',
        'AnnouncementController' => ROOT_PATH . 'content/announcement/AnnouncementController.php',
        'PointModel' => ROOT_PATH . 'content/point/PointModel.php',
        'PointController' => ROOT_PATH . 'content/point/PointController.php',
        'LinkModel' => ROOT_PATH . 'content/link/LinkModel.php',
        'LinkController' => ROOT_PATH . 'content/link/LinkController.php',
        'PostActionHelper' => ROOT_PATH . 'core/PostActionHelper.php',
        'TopicController' => ROOT_PATH . 'content/topic/TopicController.php',
        'InviteController' => ROOT_PATH . 'content/invite/InviteController.php',
        'IpLocation' => ROOT_PATH . 'core/IpLocation.php',
        'FavoriteModel' => ROOT_PATH . 'content/favorite/FavoriteModel.php',
    ];
    
    if (isset($map[$class])) {
        require_once $map[$class];
    }
});

/**
 * 初始化依赖注入容器
 */
Container::singleton('db', function() {
    return Database::getInstance();
});

Container::singleton('logger', function() {
    return Logger::getInstance();
});

Container::singleton('view', function() {
    return new View();
});

/**
 * 执行数据库升级（无感迁移）
 */
Upgrade::run();

/**
 * 运行应用
 */
$app = new App();
$app->run();

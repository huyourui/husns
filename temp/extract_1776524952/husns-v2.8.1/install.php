<?php
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)) . '/');
define('INSTALL_MODE', true);
define('APP_VERSION', '1.16.0');

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if (file_exists(ROOT_PATH . 'install.lock') && $step != 4) {
    die('系统已安装，如需重新安装请删除 install.lock 文件');
}

// 安装模式下不加载 config.php，避免会话冲突
date_default_timezone_set('Asia/Shanghai');
ini_set('display_errors', 'On');
error_reporting(E_ALL);

// 启动会话
session_name('HUSNS_INSTALL_SESSION');
session_start();

require_once ROOT_PATH . 'core/Helper.php';
require_once ROOT_PATH . 'core/Security.php';
require_once ROOT_PATH . 'core/Database.php';
require_once ROOT_PATH . 'core/View.php';
require_once ROOT_PATH . 'install/InstallController.php';

$controller = new InstallController();
$action = isset($_GET['action']) ? trim($_GET['action']) : 'index';

if (method_exists($controller, $action)) {
    $controller->$action();
} else {
    $controller->index();
}

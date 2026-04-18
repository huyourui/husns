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
 * 测试入口文件
 * 
 * 使用方法：
 *   php tests/run.php          运行所有测试
 *   php tests/run.php Helper   运行指定测试类
 */

// 定义测试环境
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
define('APP_VERSION', '2.8.1');
define('LOG_PATH', ROOT_PATH . 'logs' . DIRECTORY_SEPARATOR);

// 模拟配置
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'husns_test');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PREFIX', 'husns_');
define('DB_CHARSET', 'utf8mb4');
define('SITE_URL', '');
define('SITE_DEBUG', true);
define('URL_REWRITE', false);
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('UPLOAD_ALLOW_TYPES', 'jpg,jpeg,png,gif');
define('SESSION_NAME', 'HUSNS_TEST_SESSION');
define('SESSION_LIFETIME', 7200);
define('PASSWORD_SALT', 'test_salt_for_testing');

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// 启动会话
session_name(SESSION_NAME);
session_start();

// 加载核心文件
require_once ROOT_PATH . 'core/Helper.php';
require_once ROOT_PATH . 'core/Security.php';
require_once ROOT_PATH . 'core/Database.php';
require_once ROOT_PATH . 'core/Container.php';
require_once ROOT_PATH . 'core/Logger.php';
require_once ROOT_PATH . 'core/Queue.php';
require_once ROOT_PATH . 'core/View.php';
require_once ROOT_PATH . 'core/Hook.php';
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'core/Controller.php';

// 加载测试框架
require_once __DIR__ . '/TestRunner.php';
require_once __DIR__ . '/BasicTest.php';
require_once __DIR__ . '/ExtendedTest.php';

/**
 * 输出测试标题
 */
function printHeader()
{
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║                    HuSNS 单元测试                              ║\n";
    echo "║                    版本: " . APP_VERSION . "                                  ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    echo "\n";
}

// 运行测试
printHeader();

// 检查命令行参数
$specificTest = isset($argv[1]) ? $argv[1] : null;

if ($specificTest) {
    // 运行指定测试
    $className = $specificTest . 'Test';
    if (class_exists($className)) {
        echo "运行测试: {$className}\n\n";
        TestRunner::run($className);
    } else {
        echo "错误: 测试类 {$className} 不存在\n";
        exit(1);
    }
} else {
    // 运行所有测试
    echo "运行所有测试...\n\n";
    TestRunner::runAll();
}

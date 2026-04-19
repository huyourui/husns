<?php
/**
 * HuSNS - 一款免费开源的社交平台
 * 
 * 队列工作进程脚本
 * 
 * 使用方法：
 *   php queue.php work          # 启动队列处理
 *   php queue.php work --once   # 处理单个任务后退出
 *   php queue.php stats         # 查看队列统计
 *   php queue.php cleanup       # 清理过期任务
 * 
 * @author  HYR
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT
 */

if (php_sapi_name() !== 'cli') {
    die('此脚本只能在命令行模式下运行');
}

define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)) . '/');
define('APP_VERSION', '3.4.1');

require_once ROOT_PATH . 'config.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once ROOT_PATH . 'core/Helper.php';
require_once ROOT_PATH . 'core/Security.php';
require_once ROOT_PATH . 'core/Database.php';
require_once ROOT_PATH . 'core/Logger.php';
require_once ROOT_PATH . 'core/Queue.php';
require_once ROOT_PATH . 'core/Mailer.php';
require_once ROOT_PATH . 'content/notification/NotificationModel.php';

$command = isset($argv[1]) ? $argv[1] : 'help';
$options = [];
for ($i = 2; $i < count($argv); $i++) {
    if (strpos($argv[$i], '--') === 0) {
        $options[substr($argv[$i], 2)] = true;
    }
}

$logger = Logger::getInstance();

switch ($command) {
    case 'work':
        work($options, $logger);
        break;
    case 'stats':
        stats();
        break;
    case 'cleanup':
        cleanup();
        break;
    case 'help':
    default:
        help();
        break;
}

/**
 * 启动队列工作进程
 */
function work($options, $logger)
{
    $queue = isset($options['queue']) ? $options['queue'] : Queue::DEFAULT_QUEUE;
    $once = isset($options['once']);
    $sleep = 1;
    $maxJobs = isset($options['max-jobs']) ? (int)$options['max-jobs'] : 0;
    $processed = 0;
    
    echo "队列工作进程启动\n";
    echo "队列: {$queue}\n";
    echo "模式: " . ($once ? "单次处理" : "持续处理") . "\n";
    echo "时间: " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat('-', 50) . "\n";
    
    if (!Queue::tableExists()) {
        echo "错误: 队列表不存在，请先访问网站首页初始化数据库\n";
        exit(1);
    }
    
    while (true) {
        $job = Queue::pop($queue);
        
        if ($job) {
            echo "[" . date('H:i:s') . "] 处理任务: #{$job['id']} ({$job['job_type']})\n";
            
            $startTime = microtime(true);
            $result = Queue::process($job);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($result) {
                echo "  ✓ 完成 (耗时: {$duration}ms)\n";
                $logger->info("队列任务完成", [
                    'job_id' => $job['id'],
                    'job_type' => $job['job_type'],
                    'duration' => $duration
                ]);
            } else {
                echo "  ✗ 失败\n";
            }
            
            $processed++;
            
            if ($maxJobs > 0 && $processed >= $maxJobs) {
                echo "已达到最大处理数量 ({$maxJobs})，退出\n";
                break;
            }
        } else {
            if ($once) {
                echo "队列为空，退出\n";
                break;
            }
            
            sleep($sleep);
        }
        
        if (!$once && function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
    }
    
    echo str_repeat('-', 50) . "\n";
    echo "工作进程结束\n";
    echo "处理任务数: {$processed}\n";
}

/**
 * 显示队列统计信息
 */
function stats()
{
    echo "队列统计信息\n";
    echo str_repeat('-', 50) . "\n";
    
    if (!Queue::tableExists()) {
        echo "错误: 队列表不存在\n";
        return;
    }
    
    $stats = Queue::stats();
    
    echo "待处理: {$stats['pending']}\n";
    echo "处理中: {$stats['processing']}\n";
    echo "已完成: {$stats['completed']}\n";
    echo "已失败: {$stats['failed']}\n";
    echo "总  计: {$stats['total']}\n";
    
    $queues = Database::getInstance()->fetchAll(
        "SELECT queue, COUNT(*) as count, 
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending
         FROM __PREFIX__queue_jobs 
         GROUP BY queue"
    );
    
    if ($queues) {
        echo "\n各队列详情:\n";
        foreach ($queues as $q) {
            echo "  {$q['queue']}: {$q['pending']}/{$q['count']} (待处理/总数)\n";
        }
    }
}

/**
 * 清理过期任务
 */
function cleanup()
{
    global $options;
    $days = isset($options['days']) ? (int)$options['days'] : 7;
    
    echo "清理 {$days} 天前的已完成/失败任务\n";
    echo str_repeat('-', 50) . "\n";
    
    if (!Queue::tableExists()) {
        echo "错误: 队列表不存在\n";
        return;
    }
    
    $count = Queue::cleanup($days);
    echo "已清理 {$count} 条任务记录\n";
}

/**
 * 显示帮助信息
 */
function help()
{
    echo <<<HELP
HuSNS 队列处理脚本 v2.7.0

用法:
  php queue.php [命令] [选项]

命令:
  work      启动队列工作进程
  stats     显示队列统计信息
  cleanup   清理过期任务
  help      显示帮助信息

选项:
  --queue=NAME    指定队列名称 (默认: default)
  --once          处理单个任务后退出
  --max-jobs=N    最大处理任务数
  --days=N        清理N天前的任务 (默认: 7)

示例:
  php queue.php work
  php queue.php work --once
  php queue.php work --queue=emails --max-jobs=100
  php queue.php stats
  php queue.php cleanup --days=30

HELP;
}

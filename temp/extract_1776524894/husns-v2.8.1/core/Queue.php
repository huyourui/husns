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
 * 异步任务队列系统
 * 
 * 功能特性：
 * - 支持多种任务类型（邮件、通知、数据处理等）
 * - 延迟执行
 * - 失败重试机制
 * - 任务优先级
 * - 任务进度跟踪
 * 
 * 使用示例：
 *   // 推送任务到队列
 *   Queue::push('send_email', [
 *       'to' => 'user@example.com',
 *       'subject' => '欢迎',
 *       'body' => '...'
 *   ]);
 *   
 *   // 延迟执行
 *   Queue::later(3600, 'send_email', $data);
 *   
 *   // 处理队列
 *   php queue.php work
 */
class Queue
{
    /**
     * 默认队列名称
     */
    const DEFAULT_QUEUE = 'default';
    
    /**
     * 任务状态常量
     */
    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_FAILED = 3;
    
    /**
     * 最大重试次数
     */
    const MAX_RETRIES = 3;
    
    /**
     * 任务处理器映射
     * @var array
     */
    private static $handlers = [];
    
    /**
     * 数据库实例
     * @var Database
     */
    private static $db = null;
    
    /**
     * 初始化
     */
    private static function init()
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
    }
    
    /**
     * 注册任务处理器
     * 
     * @param string $jobType 任务类型
     * @param callable $handler 处理函数
     */
    public static function register($jobType, $handler)
    {
        self::$handlers[$jobType] = $handler;
    }
    
    /**
     * 推送任务到队列（立即执行）
     * 
     * @param string $jobType 任务类型
     * @param array $payload 任务数据
     * @param string $queue 队列名称
     * @param int $priority 优先级（越大越优先）
     * @return int|false 任务ID或false
     */
    public static function push($jobType, $payload = [], $queue = self::DEFAULT_QUEUE, $priority = 0)
    {
        return self::later(0, $jobType, $payload, $queue, $priority);
    }
    
    /**
     * 延迟推送任务
     * 
     * @param int $delay 延迟秒数
     * @param string $jobType 任务类型
     * @param array $payload 任务数据
     * @param string $queue 队列名称
     * @param int $priority 优先级
     * @return int|false 任务ID或false
     */
    public static function later($delay, $jobType, $payload = [], $queue = self::DEFAULT_QUEUE, $priority = 0)
    {
        self::init();
        
        $availableAt = time() + $delay;
        
        $data = [
            'queue' => $queue,
            'job_type' => $jobType,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => self::STATUS_PENDING,
            'priority' => $priority,
            'available_at' => $availableAt,
            'created_at' => time()
        ];
        
        try {
            return self::$db->insert('queue_jobs', $data);
        } catch (Exception $e) {
            if (self::$db) {
                $logger = Logger::getInstance();
                $logger->error('队列任务推送失败', [
                    'job_type' => $jobType,
                    'error' => $e->getMessage()
                ]);
            }
            return false;
        }
    }
    
    /**
     * 获取下一个待处理任务
     * 
     * @param string $queue 队列名称
     * @return array|null 任务数据或null
     */
    public static function pop($queue = self::DEFAULT_QUEUE)
    {
        self::init();
        
        $now = time();
        
        $sql = "SELECT * FROM __PREFIX__queue_jobs 
                WHERE queue = ? 
                AND status = ? 
                AND available_at <= ? 
                ORDER BY priority DESC, created_at ASC 
                LIMIT 1 FOR UPDATE";
        
        try {
            self::$db->beginTransaction();
            
            $job = self::$db->fetch($sql, [$queue, self::STATUS_PENDING, $now]);
            
            if (!$job) {
                self::$db->commit();
                return null;
            }
            
            self::$db->update('queue_jobs', [
                'status' => self::STATUS_PROCESSING,
                'started_at' => time(),
                'reserved_at' => time()
            ], 'id = ?', [$job['id']]);
            
            self::$db->commit();
            
            $job['payload'] = json_decode($job['payload'], true);
            return $job;
            
        } catch (Exception $e) {
            self::$db->rollBack();
            return null;
        }
    }
    
    /**
     * 标记任务完成
     * 
     * @param int $jobId 任务ID
     * @return bool
     */
    public static function complete($jobId)
    {
        self::init();
        
        return self::$db->update('queue_jobs', [
            'status' => self::STATUS_COMPLETED,
            'finished_at' => time()
        ], 'id = ?', [$jobId]) > 0;
    }
    
    /**
     * 标记任务失败
     * 
     * @param int $jobId 任务ID
     * @param string $error 错误信息
     * @return bool
     */
    public static function fail($jobId, $error = '')
    {
        self::init();
        
        $job = self::$db->fetch(
            "SELECT * FROM __PREFIX__queue_jobs WHERE id = ?",
            [$jobId]
        );
        
        if (!$job) {
            return false;
        }
        
        $retries = (int)$job['retries'] + 1;
        
        if ($retries < self::MAX_RETRIES) {
            return self::$db->update('queue_jobs', [
                'status' => self::STATUS_PENDING,
                'retries' => $retries,
                'last_error' => $error,
                'available_at' => time() + (60 * $retries)
            ], 'id = ?', [$jobId]) > 0;
        }
        
        return self::$db->update('queue_jobs', [
            'status' => self::STATUS_FAILED,
            'retries' => $retries,
            'last_error' => $error,
            'finished_at' => time()
        ], 'id = ?', [$jobId]) > 0;
    }
    
    /**
     * 释放任务（重新入队）
     * 
     * @param int $jobId 任务ID
     * @param int $delay 延迟秒数
     * @return bool
     */
    public static function release($jobId, $delay = 0)
    {
        self::init();
        
        return self::$db->update('queue_jobs', [
            'status' => self::STATUS_PENDING,
            'available_at' => time() + $delay,
            'reserved_at' => null
        ], 'id = ?', [$jobId]) > 0;
    }
    
    /**
     * 处理单个任务
     * 
     * @param array $job 任务数据
     * @return bool
     */
    public static function process($job)
    {
        $jobType = $job['job_type'];
        $payload = $job['payload'];
        
        if (!isset(self::$handlers[$jobType])) {
            self::fail($job['id'], "未注册的任务处理器: {$jobType}");
            return false;
        }
        
        try {
            $handler = self::$handlers[$jobType];
            $result = call_user_func($handler, $payload, $job);
            
            if ($result === true) {
                self::complete($job['id']);
                return true;
            } else {
                $error = is_string($result) ? $result : '任务执行失败';
                self::fail($job['id'], $error);
                return false;
            }
        } catch (Exception $e) {
            self::fail($job['id'], $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取队列统计信息
     * 
     * @param string $queue 队列名称
     * @return array
     */
    public static function stats($queue = null)
    {
        self::init();
        
        $where = $queue ? "WHERE queue = ?" : "WHERE 1=1";
        $params = $queue ? [$queue] : [];
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as failed
                FROM __PREFIX__queue_jobs {$where}";
        
        return self::$db->fetch($sql, $params);
    }
    
    /**
     * 清理已完成和失败的任务
     * 
     * @param int $days 保留天数
     * @return int 删除的任务数
     */
    public static function cleanup($days = 7)
    {
        self::init();
        
        $cutoff = time() - ($days * 86400);
        
        $sql = "DELETE FROM __PREFIX__queue_jobs 
                WHERE status IN (?, ?) 
                AND finished_at < ? 
                AND finished_at > 0";
        
        return self::$db->query($sql, [self::STATUS_COMPLETED, self::STATUS_FAILED, $cutoff])->rowCount();
    }
    
    /**
     * 获取队列大小
     * 
     * @param string $queue 队列名称
     * @return int
     */
    public static function size($queue = self::DEFAULT_QUEUE)
    {
        self::init();
        
        return (int)self::$db->fetchColumn(
            "SELECT COUNT(*) FROM __PREFIX__queue_jobs WHERE queue = ? AND status = ?",
            [$queue, self::STATUS_PENDING]
        );
    }
    
    /**
     * 检查队列表是否存在
     * 
     * @return bool
     */
    public static function tableExists()
    {
        try {
            self::init();
            $prefix = self::$db->getPrefix();
            $sql = "SHOW TABLES LIKE '{$prefix}queue_jobs'";
            $result = self::$db->fetch($sql);
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * 内置任务处理器注册
 */
Queue::register('send_email', function($payload, $job) {
    if (!isset($payload['to']) || !isset($payload['subject'])) {
        return '邮件参数不完整';
    }
    
    $mailer = new Mailer();
    $result = $mailer->send(
        $payload['to'],
        $payload['subject'],
        $payload['body'] ?? '',
        $payload['isHtml'] ?? true
    );
    
    return $result ? true : '邮件发送失败';
});

Queue::register('send_notification', function($payload, $job) {
    if (!isset($payload['user_id']) || !isset($payload['type'])) {
        return '通知参数不完整';
    }
    
    $notificationModel = new NotificationModel();
    $result = $notificationModel->send(
        $payload['user_id'],
        $payload['type'],
        $payload['title'] ?? '',
        $payload['content'] ?? '',
        $payload['data'] ?? []
    );
    
    return $result ? true : '通知发送失败';
});

Queue::register('process_webhook', function($payload, $job) {
    if (!isset($payload['url']) || !isset($payload['data'])) {
        return 'Webhook参数不完整';
    }
    
    $ch = curl_init($payload['url']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload['data']),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($httpCode >= 200 && $httpCode < 300) ? true : "HTTP错误: {$httpCode}";
});

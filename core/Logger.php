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
 * 日志系统
 * 
 * 提供完善的日志记录功能，支持多种日志级别和存储方式
 * 
 * 日志级别：
 *   - emergency: 系统不可用
 *   - alert: 必须立即采取行动
 *   - critical: 严重错误
 *   - error: 运行时错误
 *   - warning: 警告信息
 *   - notice: 通知信息
 *   - info: 一般信息
 *   - debug: 调试信息
 * 
 * 使用示例：
 *   Logger::info('用户登录成功', ['user_id' => 1]);
 *   Logger::error('数据库连接失败', ['error' => $e->getMessage()]);
 *   Logger::debug('请求参数', $_REQUEST);
 */
class Logger
{
    /**
     * 日志级别常量
     */
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    /**
     * 日志级别优先级（数字越大优先级越高）
     * @var array
     */
    private static array $levels = [
        self::DEBUG     => 100,
        self::INFO      => 200,
        self::NOTICE    => 250,
        self::WARNING   => 300,
        self::ERROR     => 400,
        self::CRITICAL  => 500,
        self::ALERT     => 550,
        self::EMERGENCY => 600,
    ];

    /**
     * 日志级别对应的颜色（用于终端输出）
     * @var array
     */
    private static array $colors = [
        self::EMERGENCY => "\033[35m", // 紫色
        self::ALERT     => "\033[35m", // 紫色
        self::CRITICAL  => "\033[31m", // 红色
        self::ERROR     => "\033[31m", // 红色
        self::WARNING   => "\033[33m", // 黄色
        self::NOTICE    => "\033[36m", // 青色
        self::INFO      => "\033[32m", // 绿色
        self::DEBUG     => "\033[37m", // 白色
    ];

    /**
     * 单例实例
     * @var Logger|null
     */
    private static ?Logger $instance = null;

    /**
     * 日志存储路径
     * @var string
     */
    private string $logPath;

    /**
     * 最小记录级别
     * @var string
     */
    private string $minLevel;

    /**
     * 是否启用日志
     * @var bool
     */
    private bool $enabled;

    /**
     * 日志文件最大大小（字节）
     * @var int
     */
    private int $maxFileSize;

    /**
     * 保留的日志文件数量
     * @var int
     */
    private int $maxFiles;

    /**
     * 私有构造函数
     */
    private function __construct()
    {
        $this->logPath = defined('LOG_PATH') ? LOG_PATH : ROOT_PATH . 'logs' . DIRECTORY_SEPARATOR;
        $this->minLevel = self::DEBUG;
        $this->enabled = true;
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->maxFiles = 30;

        $this->ensureLogDirectory();
    }

    /**
     * 获取单例实例
     * 
     * @return Logger
     */
    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 确保日志目录存在
     * 
     * @return void
     */
    private function ensureLogDirectory(): void
    {
        if (!is_dir($this->logPath)) {
            @mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * 设置日志存储路径
     * 
     * @param string $path 日志路径
     * @return Logger
     */
    public function setLogPath(string $path): Logger
    {
        $this->logPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->ensureLogDirectory();
        return $this;
    }

    /**
     * 设置最小记录级别
     * 
     * @param string $level 日志级别
     * @return Logger
     */
    public function setMinLevel(string $level): Logger
    {
        if (isset(self::$levels[$level])) {
            $this->minLevel = $level;
        }
        return $this;
    }

    /**
     * 启用或禁用日志
     * 
     * @param bool $enabled 是否启用
     * @return Logger
     */
    public function setEnabled(bool $enabled): Logger
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * 设置日志文件最大大小
     * 
     * @param int $bytes 字节数
     * @return Logger
     */
    public function setMaxFileSize(int $bytes): Logger
    {
        $this->maxFileSize = $bytes;
        return $this;
    }

    /**
     * 设置保留的日志文件数量
     * 
     * @param int $count 文件数量
     * @return Logger
     */
    public function setMaxFiles(int $count): Logger
    {
        $this->maxFiles = $count;
        return $this;
    }

    /**
     * 记录日志（通用方法）
     * 
     * @param string $level 日志级别
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public static function log(string $level, string $message, array $context = []): bool
    {
        return self::getInstance()->writeLog($level, $message, $context);
    }

    /**
     * 记录 emergency 级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public static function emergency(string $message, array $context = []): bool
    {
        return self::log(self::EMERGENCY, $message, $context);
    }

    /**
     * 记录 alert 级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public static function alert(string $message, array $context = []): bool
    {
        return self::log(self::ALERT, $message, $context);
    }

    /**
     * 记录 critical 级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public static function critical(string $message, array $context = []): bool
    {
        return self::log(self::CRITICAL, $message, $context);
    }

    /**
     * 记录 error 级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public static function error(string $message, array $context = []): bool
    {
        return self::log(self::ERROR, $message, $context);
    }

    /**
     * 记录 warning 级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public static function warning(string $message, array $context = []): bool
    {
        return self::log(self::WARNING, $message, $context);
    }

    /**
     * 记录 notice 级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public static function notice(string $message, array $context = []): bool
    {
        return self::log(self::NOTICE, $message, $context);
    }

    /**
     * 记录 info 级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public static function info(string $message, array $context = []): bool
    {
        return self::log(self::INFO, $message, $context);
    }

    /**
     * 记录 debug 级别日志
     * 
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    public static function debug(string $message, array $context = []): bool
    {
        return self::log(self::DEBUG, $message, $context);
    }

    /**
     * 写入日志
     * 
     * @param string $level 日志级别
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return bool
     */
    private function writeLog(string $level, string $message, array $context = []): bool
    {
        // 检查日志是否启用
        if (!$this->enabled) {
            return false;
        }

        // 检查日志级别是否满足最小级别要求
        if (!$this->shouldLog($level)) {
            return false;
        }

        // 格式化日志内容
        $formatted = $this->formatLog($level, $message, $context);

        // 获取日志文件路径
        $logFile = $this->getLogFile($level);

        // 检查文件大小并轮转
        $this->rotateLogIfNeeded($logFile);

        // 写入日志文件
        $result = file_put_contents($logFile, $formatted . PHP_EOL, FILE_APPEND | LOCK_EX);

        return $result !== false;
    }

    /**
     * 检查是否应该记录该级别的日志
     * 
     * @param string $level 日志级别
     * @return bool
     */
    private function shouldLog(string $level): bool
    {
        if (!isset(self::$levels[$level])) {
            return false;
        }

        return self::$levels[$level] >= self::$levels[$this->minLevel];
    }

    /**
     * 格式化日志内容
     * 
     * @param string $level 日志级别
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return string
     */
    private function formatLog(string $level, string $message, array $context = []): string
    {
        // 替换上下文占位符
        $message = $this->interpolate($message, $context);

        // 构建日志条目
        $timestamp = date('Y-m-d H:i:s');
        $microtime = sprintf('%06d', (microtime(true) - floor(microtime(true))) * 1000000);
        
        // 获取调用者信息
        $caller = $this->getCallerInfo();

        // 格式化上下文数据
        $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';

        // 构建完整日志行
        $logLine = sprintf(
            "[%s.%s] %s.%s: %s%s%s",
            $timestamp,
            $microtime,
            strtoupper($level),
            $caller,
            $message,
            $contextStr ? ' ' . $contextStr : '',
            $this->getAdditionalInfo()
        );

        return $logLine;
    }

    /**
     * 替换消息中的占位符
     * 
     * @param string $message 消息
     * @param array $context 上下文数据
     * @return string
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_string($val) || is_numeric($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        return strtr($message, $replace);
    }

    /**
     * 获取调用者信息
     * 
     * @return string
     */
    private function getCallerInfo(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);

        foreach ($trace as $frame) {
            if (!isset($frame['class'])) {
                continue;
            }

            // 跳过Logger类本身
            if (strpos($frame['class'], 'Logger') !== false) {
                continue;
            }

            $class = $frame['class'] ?? '';
            $function = $frame['function'] ?? '';
            $line = $frame['line'] ?? 0;

            return "{$class}::{$function}({$line})";
        }

        return 'unknown';
    }

    /**
     * 获取附加信息（请求ID、用户ID等）
     * 
     * @return string
     */
    private function getAdditionalInfo(): string
    {
        $info = [];

        // 添加请求ID
        if (!isset($GLOBALS['request_id'])) {
            $GLOBALS['request_id'] = bin2hex(random_bytes(8));
        }
        $info['request_id'] = $GLOBALS['request_id'];

        // 添加用户ID
        if (isset($_SESSION['user_id'])) {
            $info['user_id'] = $_SESSION['user_id'];
        }

        // 添加IP地址
        $info['ip'] = Helper::getIp();

        return ' [' . json_encode($info, JSON_UNESCAPED_UNICODE) . ']';
    }

    /**
     * 获取日志文件路径
     * 
     * @param string $level 日志级别
     * @return string
     */
    private function getLogFile(string $level): string
    {
        // 按日期和级别分文件
        $date = date('Y-m-d');
        $filename = "husns-{$date}.log";
        
        return $this->logPath . $filename;
    }

    /**
     * 检查并轮转日志文件
     * 
     * @param string $logFile 日志文件路径
     * @return void
     */
    private function rotateLogIfNeeded(string $logFile): void
    {
        if (!file_exists($logFile)) {
            return;
        }

        // 检查文件大小
        if (filesize($logFile) >= $this->maxFileSize) {
            $this->rotateFile($logFile);
        }

        // 清理旧日志文件
        $this->cleanupOldLogs();
    }

    /**
     * 轮转日志文件
     * 
     * @param string $logFile 日志文件路径
     * @return void
     */
    private function rotateFile(string $logFile): void
    {
        $info = pathinfo($logFile);
        $timestamp = date('His');
        $newFile = $info['dirname'] . DIRECTORY_SEPARATOR . 
                   $info['filename'] . '-' . $timestamp . '.' . $info['extension'];

        if (file_exists($logFile)) {
            @rename($logFile, $newFile);
        }
    }

    /**
     * 清理旧的日志文件
     * 
     * @return void
     */
    private function cleanupOldLogs(): void
    {
        $pattern = $this->logPath . 'husns-*.log*';
        $files = glob($pattern);

        if ($files === false || count($files) <= $this->maxFiles) {
            return;
        }

        // 按修改时间排序
        usort($files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // 删除最旧的文件
        $deleteCount = count($files) - $this->maxFiles;
        for ($i = 0; $i < $deleteCount; $i++) {
            @unlink($files[$i]);
        }
    }

    /**
     * 获取日志内容
     * 
     * @param string $date 日期（Y-m-d格式）
     * @param int $lines 行数
     * @return array
     */
    public static function getLogs(string $date = '', int $lines = 100): array
    {
        $instance = self::getInstance();
        
        if (empty($date)) {
            $date = date('Y-m-d');
        }

        $logFile = $instance->logPath . "husns-{$date}.log";

        if (!file_exists($logFile)) {
            return [];
        }

        // 读取文件最后N行
        $output = [];
        $fp = fopen($logFile, 'r');
        
        if ($fp === false) {
            return [];
        }

        // 从文件末尾开始读取
        fseek($fp, -1, SEEK_END);
        $pos = ftell($fp);
        $lineCount = 0;
        $content = '';

        while ($pos >= 0 && $lineCount < $lines) {
            fseek($fp, $pos, SEEK_SET);
            $char = fgetc($fp);
            
            if ($char === "\n" && $content !== '') {
                $output[] = strrev($content);
                $content = '';
                $lineCount++;
            } else {
                $content .= $char;
            }
            
            $pos--;
        }

        // 添加最后一行
        if ($content !== '' && $lineCount < $lines) {
            $output[] = strrev($content);
        }

        fclose($fp);

        return array_reverse($output);
    }

    /**
     * 清空日志文件
     * 
     * @param string $date 日期（Y-m-d格式）
     * @return bool
     */
    public static function clear(string $date = ''): bool
    {
        $instance = self::getInstance();
        
        if (empty($date)) {
            $date = date('Y-m-d');
        }

        $logFile = $instance->logPath . "husns-{$date}.log";

        if (file_exists($logFile)) {
            return file_put_contents($logFile, '') !== false;
        }

        return true;
    }
}

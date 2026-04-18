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
 * 应用异常基类
 * 
 * 所有应用异常都应继承此类，便于统一处理
 */
class AppException extends Exception
{
    /**
     * 异常数据
     * @var array
     */
    protected array $data = [];

    /**
     * HTTP状态码
     * @var int
     */
    protected int $statusCode = 500;

    /**
     * 构造函数
     * 
     * @param string $message 异常消息
     * @param int $code 异常代码
     * @param Throwable|null $previous 前一个异常
     * @param array $data 附加数据
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, array $data = [])
    {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }

    /**
     * 获取异常数据
     * 
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 获取HTTP状态码
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * 设置HTTP状态码
     * 
     * @param int $statusCode 状态码
     * @return self
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }
}

/**
 * 数据库异常
 */
class DatabaseException extends AppException
{
    protected int $statusCode = 500;
}

/**
 * 验证异常
 */
class ValidationException extends AppException
{
    protected int $statusCode = 422;
}

/**
 * 认证异常
 */
class AuthenticationException extends AppException
{
    protected int $statusCode = 401;
}

/**
 * 授权异常
 */
class AuthorizationException extends AppException
{
    protected int $statusCode = 403;
}

/**
 * 资源未找到异常
 */
class NotFoundException extends AppException
{
    protected int $statusCode = 404;
}

/**
 * 异常处理器
 * 
 * 统一处理应用中的异常，提供日志记录和友好的错误响应
 * 
 * 使用示例：
 *   // 在入口文件注册
 *   ExceptionHandler::register();
 *   
 *   // 手动报告异常
 *   ExceptionHandler::report($exception);
 */
class ExceptionHandler
{
    /**
     * 单例实例
     * @var ExceptionHandler|null
     */
    private static ?ExceptionHandler $instance = null;

    /**
     * 是否启用调试模式
     * @var bool
     */
    private bool $debug;

    /**
     * 不需要记录日志的异常类型
     * @var array
     */
    private array $dontReport = [];

    /**
     * 异常级别映射
     * @var array
     */
    private array $levelMap = [
        AppException::class => Logger::ERROR,
        DatabaseException::class => Logger::CRITICAL,
        ValidationException::class => Logger::WARNING,
        AuthenticationException::class => Logger::NOTICE,
        AuthorizationException::class => Logger::WARNING,
        NotFoundException::class => Logger::NOTICE,
    ];

    /**
     * 私有构造函数
     */
    private function __construct()
    {
        $this->debug = defined('SITE_DEBUG') && SITE_DEBUG;
    }

    /**
     * 获取单例实例
     * 
     * @return ExceptionHandler
     */
    public static function getInstance(): ExceptionHandler
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 注册异常处理器
     * 
     * @return void
     */
    public static function register(): void
    {
        $handler = self::getInstance();

        // 设置异常处理函数
        set_exception_handler([$handler, 'handle']);

        // 设置错误处理函数
        set_error_handler([$handler, 'handleError']);

        // 设置致命错误处理函数
        register_shutdown_function([$handler, 'handleShutdown']);

        // 启用错误报告
        error_reporting(E_ALL);
    }

    /**
     * 恢复默认异常处理器
     * 
     * @return void
     */
    public static function unregister(): void
    {
        restore_exception_handler();
        restore_error_handler();
    }

    /**
     * 处理异常
     * 
     * @param Throwable $exception 异常对象
     * @return void
     */
    public function handle(Throwable $exception): void
    {
        try {
            // 记录异常日志
            $this->report($exception);

            // 发送响应
            $this->render($exception);
        } catch (Throwable $e) {
            // 如果异常处理过程中出错，使用最简单的方式输出
            if ($this->debug) {
                echo "异常处理失败: " . $e->getMessage();
            } else {
                echo "系统错误，请稍后重试";
            }
        }
    }

    /**
     * 处理错误
     * 
     * @param int $level 错误级别
     * @param string $message 错误消息
     * @param string $file 文件路径
     * @param int $line 行号
     * @return bool
     * @throws ErrorException
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        // 将错误转换为异常
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }

        return false;
    }

    /**
     * 处理致命错误
     * 
     * @return void
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && $this->isFatal($error['type'])) {
            $exception = new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );

            $this->handle($exception);
        }
    }

    /**
     * 判断是否为致命错误
     * 
     * @param int $type 错误类型
     * @return bool
     */
    private function isFatal(int $type): bool
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }

    /**
     * 报告异常（记录日志）
     * 
     * @param Throwable $exception 异常对象
     * @return void
     */
    public static function report(Throwable $exception): void
    {
        $handler = self::getInstance();

        // 检查是否需要记录
        if ($handler->shouldntReport($exception)) {
            return;
        }

        // 获取日志级别
        $level = $handler->getLogLevel($exception);

        // 构建日志上下文
        $context = $handler->getExceptionContext($exception);

        // 记录日志
        Logger::log($level, $exception->getMessage(), $context);
    }

    /**
     * 判断是否不需要报告异常
     * 
     * @param Throwable $exception 异常对象
     * @return bool
     */
    private function shouldntReport(Throwable $exception): bool
    {
        foreach ($this->dontReport as $type) {
            if ($exception instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取异常的日志级别
     * 
     * @param Throwable $exception 异常对象
     * @return string
     */
    private function getLogLevel(Throwable $exception): string
    {
        foreach ($this->levelMap as $class => $level) {
            if ($exception instanceof $class) {
                return $level;
            }
        }

        return Logger::ERROR;
    }

    /**
     * 获取异常上下文信息
     * 
     * @param Throwable $exception 异常对象
     * @return array
     */
    private function getExceptionContext(Throwable $exception): array
    {
        $context = [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->getFormattedTrace($exception),
        ];

        // 如果是AppException，添加附加数据
        if ($exception instanceof AppException) {
            $context['data'] = $exception->getData();
        }

        // 添加请求信息
        $context['request'] = [
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'ip' => Helper::getIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        return $context;
    }

    /**
     * 格式化异常堆栈
     * 
     * @param Throwable $exception 异常对象
     * @return array
     */
    private function getFormattedTrace(Throwable $exception): array
    {
        $trace = [];
        $count = 0;

        foreach ($exception->getTrace() as $frame) {
            // 限制堆栈深度
            if ($count++ >= 20) {
                break;
            }

            $trace[] = [
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? 'unknown',
                'class' => $frame['class'] ?? '',
                'type' => $frame['type'] ?? '',
            ];
        }

        return $trace;
    }

    /**
     * 渲染异常响应
     * 
     * @param Throwable $exception 异常对象
     * @return void
     */
    private function render(Throwable $exception): void
    {
        // 清除之前的输出
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 获取HTTP状态码
        $statusCode = $this->getStatusCode($exception);

        // 设置HTTP响应码
        http_response_code($statusCode);

        // 判断请求类型
        if ($this->isAjax()) {
            $this->renderJsonResponse($exception);
        } else {
            $this->renderHtmlResponse($exception);
        }
    }

    /**
     * 获取HTTP状态码
     * 
     * @param Throwable $exception 异常对象
     * @return int
     */
    private function getStatusCode(Throwable $exception): int
    {
        if ($exception instanceof AppException) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof ErrorException) {
            return 500;
        }

        return 500;
    }

    /**
     * 判断是否为AJAX请求
     * 
     * @return bool
     */
    private function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * 渲染JSON响应
     * 
     * @param Throwable $exception 异常对象
     * @return void
     */
    private function renderJsonResponse(Throwable $exception): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'code' => $exception->getCode() ?: 1,
            'message' => $exception->getMessage(),
            'data' => null,
        ];

        // 调试模式下添加详细信息
        if ($this->debug) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 渲染HTML响应
     * 
     * @param Throwable $exception 异常对象
     * @return void
     */
    private function renderHtmlResponse(Throwable $exception): void
    {
        // 尝试加载错误页面模板
        $errorPage = ROOT_PATH . 'templates' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'error' . DIRECTORY_SEPARATOR . 'exception.php';

        if (file_exists($errorPage) && !$this->debug) {
            // 生产环境使用错误页面模板
            $title = '系统错误';
            $message = '系统发生错误，请稍后重试';
            include $errorPage;
        } elseif ($this->debug) {
            // 调试模式显示详细错误信息
            $this->renderDebugPage($exception);
        } else {
            // 默认错误页面
            $this->renderSimpleErrorPage($exception);
        }
    }

    /**
     * 渲染调试页面
     * 
     * @param Throwable $exception 异常对象
     * @return void
     */
    private function renderDebugPage(Throwable $exception): void
    {
        $title = get_class($exception);
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();
        $code = $this->getCodeSnippet($file, $line);

        echo <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a1a2e; 
            color: #eee; 
            padding: 20px; 
            line-height: 1.6;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { 
            background: linear-gradient(135deg, #e94560, #ff6b6b); 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 20px;
        }
        .header h1 { font-size: 24px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .section { 
            background: #16213e; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            overflow: hidden;
        }
        .section-header { 
            background: #0f3460; 
            padding: 12px 20px; 
            font-weight: bold;
            border-bottom: 1px solid #1a1a2e;
        }
        .section-content { padding: 20px; }
        .file-info { 
            background: #0f3460; 
            padding: 10px 15px; 
            border-radius: 4px; 
            margin-bottom: 15px;
            font-family: monospace;
        }
        .code-block { 
            background: #0a0a15; 
            padding: 15px; 
            border-radius: 4px; 
            overflow-x: auto;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 13px;
            line-height: 1.5;
        }
        .code-line { display: flex; }
        .line-number { 
            color: #666; 
            width: 50px; 
            text-align: right; 
            padding-right: 15px;
            user-select: none;
        }
        .line-content { flex: 1; }
        .highlight { background: rgba(233, 69, 96, 0.3); }
        .trace { 
            white-space: pre-wrap; 
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ {$title}</h1>
            <p>{$message}</p>
        </div>
        
        <div class="section">
            <div class="section-header">📍 位置</div>
            <div class="section-content">
                <div class="file-info">
                    <strong>文件:</strong> {$file}<br>
                    <strong>行号:</strong> {$line}
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">📝 代码片段</div>
            <div class="section-content">
                <div class="code-block">{$code}</div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">📚 调用堆栈</div>
            <div class="section-content">
                <div class="trace">{$trace}</div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * 获取代码片段
     * 
     * @param string $file 文件路径
     * @param int $line 行号
     * @param int $context 上下文行数
     * @return string
     */
    private function getCodeSnippet(string $file, int $line, int $context = 10): string
    {
        if (!file_exists($file)) {
            return '无法读取文件';
        }

        $lines = file($file);
        if ($lines === false) {
            return '无法读取文件';
        }

        $start = max(0, $line - $context - 1);
        $end = min(count($lines), $line + $context);

        $snippet = '';
        for ($i = $start; $i < $end; $i++) {
            $lineNum = $i + 1;
            $content = htmlspecialchars($lines[$i], ENT_QUOTES, 'UTF-8');
            $highlight = ($lineNum === $line) ? ' highlight' : '';
            $snippet .= "<div class=\"code-line{$highlight}\"><span class=\"line-number\">{$lineNum}</span><span class=\"line-content\">{$content}</span></div>";
        }

        return $snippet;
    }

    /**
     * 渲染简单错误页面
     * 
     * @param Throwable $exception 异常对象
     * @return void
     */
    private function renderSimpleErrorPage(Throwable $exception): void
    {
        $statusCode = $this->getStatusCode($exception);
        $message = $this->getFriendlyMessage($exception);

        echo <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>错误 - {$statusCode}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .container { text-align: center; padding: 40px; }
        .error-code { font-size: 120px; font-weight: bold; opacity: 0.3; }
        .error-message { font-size: 24px; margin: 20px 0; }
        .btn { 
            display: inline-block;
            padding: 12px 30px;
            background: rgba(255,255,255,0.2);
            color: #fff;
            text-decoration: none;
            border-radius: 25px;
            transition: background 0.3s;
        }
        .btn:hover { background: rgba(255,255,255,0.3); }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">{$statusCode}</div>
        <div class="error-message">{$message}</div>
        <a href="javascript:history.back()" class="btn">返回上一页</a>
    </div>
</body>
</html>
HTML;
    }

    /**
     * 获取友好的错误消息
     * 
     * @param Throwable $exception 异常对象
     * @return string
     */
    private function getFriendlyMessage(Throwable $exception): string
    {
        if ($exception instanceof NotFoundException) {
            return '您访问的页面不存在';
        }

        if ($exception instanceof AuthenticationException) {
            return '请先登录后再继续操作';
        }

        if ($exception instanceof AuthorizationException) {
            return '您没有权限执行此操作';
        }

        if ($exception instanceof ValidationException) {
            return $exception->getMessage();
        }

        return '系统发生错误，请稍后重试';
    }

    /**
     * 设置不需要记录日志的异常类型
     * 
     * @param array $types 异常类型数组
     * @return self
     */
    public function setDontReport(array $types): self
    {
        $this->dontReport = $types;
        return $this;
    }

    /**
     * 设置调试模式
     * 
     * @param bool $debug 是否启用调试
     * @return self
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }
}

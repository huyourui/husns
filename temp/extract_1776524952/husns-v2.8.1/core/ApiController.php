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
 * API控制器基类
 * 
 * 提供RESTful API的基础功能：
 * - Token认证
 * - 请求验证
 * - 响应格式化
 * - 速率限制
 */
class ApiController extends Controller
{
    /**
     * API版本号
     */
    const API_VERSION = '1.0';
    
    /**
     * 当前认证用户
     * @var array|null
     */
    protected $apiUser = null;
    
    /**
     * API Token
     * @var string|null
     */
    protected $token = null;
    
    /**
     * 请求速率限制（每分钟）
     * @var int
     */
    protected $rateLimit = 60;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->validateRequest();
        $this->authenticate();
        $this->checkRateLimit();
    }
    
    /**
     * 验证请求
     * 
     * 检查请求方法和Content-Type
     */
    protected function validateRequest()
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Token');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * API Token认证
     * 
     * 支持两种方式：
     * 1. Header: Authorization: Bearer {token}
     * 2. Header: X-API-Token: {token}
     */
    protected function authenticate()
    {
        $token = null;
        
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        if (!$token && isset($_SERVER['HTTP_X_API_TOKEN'])) {
            $token = $_SERVER['HTTP_X_API_TOKEN'];
        }
        
        if (!$token) {
            return;
        }
        
        $this->token = $token;
        $this->apiUser = $this->validateToken($token);
    }
    
    /**
     * 验证Token有效性
     * 
     * @param string $token API Token
     * @return array|null 用户信息或null
     */
    protected function validateToken($token)
    {
        $db = Database::getInstance();
        
        $sql = "SELECT t.*, u.id as user_id, u.username, u.email, u.avatar, u.is_admin, u.status
                FROM __PREFIX__api_tokens t
                INNER JOIN __PREFIX__users u ON t.user_id = u.id
                WHERE t.token = ? AND t.status = 1 AND t.expires_at > ?";
        
        $result = $db->fetch($sql, [$token, time()]);
        
        if (!$result) {
            return null;
        }
        
        if ($result['status'] != 1) {
            return null;
        }
        
        $db->update('api_tokens', [
            'last_used_at' => time()
        ], 'token = ?', [$token]);
        
        return [
            'id' => $result['user_id'],
            'username' => $result['username'],
            'email' => $result['email'],
            'avatar' => $result['avatar'],
            'is_admin' => (bool)$result['is_admin']
        ];
    }
    
    /**
     * 检查请求速率限制
     */
    protected function checkRateLimit()
    {
        $identifier = $this->apiUser ? 'user_' . $this->apiUser['id'] : 'ip_' . Helper::getIp();
        $key = 'api_rate_' . $identifier;
        $cacheFile = ROOT_PATH . 'logs' . DIRECTORY_SEPARATOR . 'api_rate' . DIRECTORY_SEPARATOR;
        
        if (!is_dir($cacheFile)) {
            mkdir($cacheFile, 0755, true);
        }
        
        $cacheFile .= md5($key) . '.cache';
        $now = time();
        $window = 60;
        
        $requests = [];
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && isset($data['requests'])) {
                $requests = array_filter($data['requests'], function($time) use ($now, $window) {
                    return ($now - $time) < $window;
                });
            }
        }
        
        if (count($requests) >= $this->rateLimit) {
            $this->jsonError('请求过于频繁，请稍后再试', 429);
        }
        
        $requests[] = $now;
        file_put_contents($cacheFile, json_encode(['requests' => $requests]));
    }
    
    /**
     * 要求用户认证
     * 
     * @return bool
     */
    protected function requireAuth()
    {
        if (!$this->apiUser) {
            $this->jsonError('请先登录', 401);
        }
        return true;
    }
    
    /**
     * 获取当前认证用户
     * 
     * @return array|null
     */
    protected function user()
    {
        return $this->apiUser;
    }
    
    /**
     * 获取JSON请求体
     * 
     * @return array
     */
    protected function getJsonInput()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        return is_array($data) ? $data : [];
    }
    
    /**
     * 获取请求参数（支持GET、POST、JSON Body）
     * 
     * @param string $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function input($key, $default = null)
    {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        
        $json = $this->getJsonInput();
        return isset($json[$key]) ? $json[$key] : $default;
    }
    
    /**
     * 成功响应
     * 
     * @param mixed $data 数据
     * @param string $message 消息
     * @param int $code 状态码
     */
    protected function success($data = null, $message = '操作成功', $code = 200)
    {
        $this->json([
            'code' => 0,
            'message' => $message,
            'data' => $data,
            'api_version' => self::API_VERSION,
            'timestamp' => time()
        ], $code);
    }
    
    /**
     * 错误响应
     * 
     * @param string $message 错误消息
     * @param int $code 错误码
     * @param int $httpCode HTTP状态码
     */
    protected function error($message = '操作失败', $code = 1, $httpCode = 400)
    {
        $this->json([
            'code' => $code,
            'message' => $message,
            'data' => null,
            'api_version' => self::API_VERSION,
            'timestamp' => time()
        ], $httpCode);
    }
    
    /**
     * 分页响应
     * 
     * @param array $items 数据项
     * @param int $total 总数
     * @param int $page 当前页
     * @param int $pageSize 每页数量
     * @param string $message 消息
     */
    protected function paginate($items, $total, $page, $pageSize, $message = '获取成功')
    {
        $this->success([
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'total_pages' => ceil($total / $pageSize)
            ]
        ], $message);
    }
    
    /**
     * 资源未找到响应
     * 
     * @param string $message 消息
     */
    protected function notFound($message = '资源未找到')
    {
        $this->error($message, 404, 404);
    }
    
    /**
     * 无权限响应
     * 
     * @param string $message 消息
     */
    protected function forbidden($message = '无权限访问')
    {
        $this->error($message, 403, 403);
    }
    
    /**
     * 验证错误响应
     * 
     * @param array $errors 错误列表
     * @param string $message 消息
     */
    protected function validationError($errors = [], $message = '参数验证失败')
    {
        $this->json([
            'code' => 422,
            'message' => $message,
            'errors' => $errors,
            'api_version' => self::API_VERSION,
            'timestamp' => time()
        ], 422);
    }
}

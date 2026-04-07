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
class Helper
{
    public static function url($path = '')
    {
        $baseUrl = defined('SITE_URL') && SITE_URL ? SITE_URL : self::getBaseUrl();
        
        if (empty($path)) {
            return $baseUrl . '/';
        }
        
        $queryString = '';
        if (strpos($path, '?') !== false) {
            $parts = explode('?', $path, 2);
            $path = $parts[0];
            $queryString = '&' . $parts[1];
        }
        
        if (defined('URL_REWRITE') && URL_REWRITE) {
            return $baseUrl . '/' . ltrim($path, '/') . ($queryString ? '?' . substr($queryString, 1) : '');
        }
        
        return $baseUrl . '/?r=' . ltrim($path, '/') . $queryString;
    }

    public static function getBaseUrl()
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = dirname($scriptName);
        $basePath = str_replace('\\', '/', $basePath);
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        return rtrim($basePath, '/');
    }

    public static function redirect($url, $delay = 0)
    {
        if ($delay > 0) {
            header('Refresh: ' . $delay . '; url=' . $url);
        } else {
            header('Location: ' . $url);
        }
        exit;
    }

    public static function asset($path)
    {
        $baseUrl = self::getBaseUrl();
        if ($baseUrl === '') {
            return '/static/' . ltrim($path, '/');
        }
        return $baseUrl . '/static/' . ltrim($path, '/');
    }

    public static function uploadUrl($path)
    {
        $baseUrl = self::getBaseUrl();
        if ($baseUrl === '') {
            return '/uploads/' . ltrim($path, '/');
        }
        return $baseUrl . '/uploads/' . ltrim($path, '/');
    }

    public static function formatTime($timestamp)
    {
        $time = time() - $timestamp;
        if ($time < 60) {
            return '刚刚';
        } elseif ($time < 3600) {
            return floor($time / 60) . '分钟前';
        } elseif ($time < 86400) {
            return floor($time / 3600) . '小时前';
        } elseif ($time < 2592000) {
            return floor($time / 86400) . '天前';
        } else {
            return date('Y-m-d H:i', $timestamp);
        }
    }

    public static function truncate($string, $length = 100, $suffix = '...')
    {
        if (mb_strlen($string, 'UTF-8') <= $length) {
            return $string;
        }
        return mb_substr($string, 0, $length, 'UTF-8') . $suffix;
    }

    public static function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    public static function csrfField()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
    }

    public static function verifyCsrf()
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        $headerToken = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
        return hash_equals($_SESSION['csrf_token'], $token) || hash_equals($_SESSION['csrf_token'], $headerToken);
    }

    public static function json($data, $code = 200)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function jsonSuccess($data = null, $message = '操作成功')
    {
        self::json(['code' => 0, 'message' => $message, 'data' => $data]);
    }

    public static function jsonError($message = '操作失败', $code = 1)
    {
        self::json(['code' => $code, 'message' => $message, 'data' => null]);
    }

    public static function getIp()
    {
        $ip = '';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($ip === '::1') {
                return '127.0.0.1';
            }
            return $ip;
        }
        
        return '127.0.0.1';
    }

    public static function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public static function get($key, $default = null)
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    public static function post($key, $default = null)
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    public static function setFlash($type, $message)
    {
        $_SESSION['flash'][$type] = $message;
    }

    public static function getFlash($type)
    {
        $message = isset($_SESSION['flash'][$type]) ? $_SESSION['flash'][$type] : null;
        unset($_SESSION['flash'][$type]);
        return $message;
    }

    public static function hasFlash($type)
    {
        return isset($_SESSION['flash'][$type]);
    }

    /**
     * 生成用户头像HTML
     * 
     * 优化后的逻辑：
     * - 优先检测头像文件是否存在
     * - 文件不存在时自动显示首字符头像
     * - 支持多种尺寸：small(32px)、default(48px)、large(80px) 或自定义像素值
     * 
     * @param string|null $avatar 头像路径
     * @param string $username 用户名（用于生成首字符头像）
     * @param string|int $size 尺寸：small/default/large 或像素值
     * @return string HTML代码
     */
    public static function avatar($avatar, $username, $size = 'default')
    {
        if (is_numeric($size)) {
            $pixelSize = (int)$size;
            $imgStyle = "width:{$pixelSize}px;height:{$pixelSize}px;border-radius:50%;object-fit:cover;";
            $spanStyle = "display:inline-block;width:{$pixelSize}px;height:{$pixelSize}px;border-radius:50%;text-align:center;line-height:{$pixelSize}px;font-size:" . round($pixelSize * 0.4) . "px;font-weight:500;";
        } else {
            $sizes = [
                'small' => ['img' => 'width:32px;height:32px;', 'span' => ['size' => '32px', 'font' => '14px', 'line' => '32px']],
                'default' => ['img' => 'width:48px;height:48px;', 'span' => ['size' => '48px', 'font' => '20px', 'line' => '48px']],
                'large' => ['img' => 'width:80px;height:80px;', 'span' => ['size' => '80px', 'font' => '32px', 'line' => '80px']],
            ];
            $s = $sizes[$size] ?? $sizes['default'];
            $imgStyle = $s['img'] . 'border-radius:50%;object-fit:cover;';
            $spanStyle = "display:inline-block;width:{$s['span']['size']};height:{$s['span']['size']};border-radius:50%;text-align:center;line-height:{$s['span']['line']};font-size:{$s['span']['font']};font-weight:500;";
        }
        
        if ($avatar && self::avatarFileExists($avatar)) {
            $colors = self::getAvatarColor($username);
            $firstChar = self::getFirstChar($username);
            return '<img src="' . self::uploadUrl($avatar) . '" alt="' . htmlspecialchars($username) . '" style="' . $imgStyle . '" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'inline-block\';"><span class="avatar-default" style="' . $spanStyle . 'background:' . $colors['bg'] . ';color:' . $colors['text'] . ';display:none;">' . htmlspecialchars($firstChar) . '</span>';
        }
        
        $firstChar = self::getFirstChar($username);
        $colors = self::getAvatarColor($username);
        
        return '<span class="avatar-default" style="' . $spanStyle . 'background:' . $colors['bg'] . ';color:' . $colors['text'] . ';">' . htmlspecialchars($firstChar) . '</span>';
    }

    /**
     * 检测头像文件是否存在
     * 
     * @param string $avatar 头像路径
     * @return bool 文件是否存在
     */
    private static function avatarFileExists($avatar)
    {
        if (empty($avatar)) {
            return false;
        }
        
        $filePath = UPLOAD_PATH . $avatar;
        return file_exists($filePath);
    }

    /**
     * 获取用户名首字符
     * 
     * @param string $username 用户名
     * @return string 首字符
     */
    private static function getFirstChar($username)
    {
        if (empty($username)) {
            return '?';
        }
        return mb_substr($username, 0, 1, 'UTF-8');
    }

    /**
     * 根据用户名生成头像颜色
     * 
     * @param string $username 用户名
     * @return array 包含背景色和文字色的数组
     */
    private static function getAvatarColor($username)
    {
        $colors = [
            ['bg' => '#667eea', 'text' => '#ffffff'],
            ['bg' => '#764ba2', 'text' => '#ffffff'],
            ['bg' => '#f093fb', 'text' => '#ffffff'],
            ['bg' => '#f5576c', 'text' => '#ffffff'],
            ['bg' => '#4facfe', 'text' => '#ffffff'],
            ['bg' => '#00f2fe', 'text' => '#ffffff'],
            ['bg' => '#43e97b', 'text' => '#ffffff'],
            ['bg' => '#38f9d7', 'text' => '#ffffff'],
            ['bg' => '#fa709a', 'text' => '#ffffff'],
            ['bg' => '#fee140', 'text' => '#333333'],
            ['bg' => '#30cfd0', 'text' => '#ffffff'],
            ['bg' => '#a8edea', 'text' => '#333333'],
            ['bg' => '#ff9a9e', 'text' => '#ffffff'],
            ['bg' => '#ffecd2', 'text' => '#333333'],
            ['bg' => '#a1c4fd', 'text' => '#ffffff'],
            ['bg' => '#c2e9fb', 'text' => '#333333'],
        ];
        
        $hash = 0;
        for ($i = 0; $i < strlen($username); $i++) {
            $hash = ord($username[$i]) + (($hash << 5) - $hash);
        }
        
        $index = abs($hash) % count($colors);
        return $colors[$index];
    }
    
    public static function formatNumber($num)
    {
        if ($num >= 10000) {
            return round($num / 10000, 1) . 'w';
        } elseif ($num >= 1000) {
            return round($num / 1000, 1) . 'k';
        }
        return $num;
    }
}

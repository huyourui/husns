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
class Security
{
    public static function escape($string)
    {
        if (is_array($string)) {
            return array_map([self::class, 'escape'], $string);
        }
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function stripTags($string)
    {
        return strip_tags($string);
    }

    public static function cleanInput($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'cleanInput'], $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = self::stripTags($data);
        return $data;
    }

    public static function hashPassword($password)
    {
        $salt = defined('PASSWORD_SALT') ? PASSWORD_SALT : '';
        return password_hash($password . $salt, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword($password, $hash)
    {
        $salt = defined('PASSWORD_SALT') ? PASSWORD_SALT : '';
        return password_verify($password . $salt, $hash);
    }

    public static function generateRandomString($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $str;
    }

    /**
     * XSS过滤清理
     * 
     * 增强的XSS过滤，移除危险的HTML标签和属性
     * 注意：此方法应用于富文本输入，普通输出应使用escape()方法
     * 
     * @param mixed $data 要清理的数据
     * @return mixed 清理后的数据
     */
    public static function xssClean($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'xssClean'], $data);
        }
        
        // 移除危险的HTML标签
        $dangerousTags = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/<iframe\b[^>]*>(.*?)<\/iframe>/is',
            '/<object\b[^>]*>(.*?)<\/object>/is',
            '/<embed\b[^>]*>(.*?)<\/embed>/is',
            '/<applet\b[^>]*>(.*?)<\/applet>/is',
            '/<meta\b[^>]*>/is',
            '/<link\b[^>]*>/is',
            '/<base\b[^>]*>/is',
            '/<form\b[^>]*>(.*?)<\/form>/is',
            '/<input\b[^>]*>/is',
            '/<button\b[^>]*>(.*?)<\/button>/is',
            '/<textarea\b[^>]*>(.*?)<\/textarea>/is',
            '/<select\b[^>]*>(.*?)<\/select>/is',
        ];
        
        foreach ($dangerousTags as $pattern) {
            $data = preg_replace($pattern, '', $data);
        }
        
        // 移除危险的事件处理属性
        $data = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $data);
        $data = preg_replace('/\s+on\w+\s*=\s*[^\s>]*/i', '', $data);
        
        // 移除危险的协议
        $dangerousProtocols = [
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/expression\s*\(/i',
            '/behavior\s*:/i',
            '/-moz-binding\s*:/i',
            '/data\s*:\s*text\/html/i',
        ];
        
        foreach ($dangerousProtocols as $pattern) {
            $data = preg_replace($pattern, '', $data);
        }
        
        // 移除style属性中的expression
        $data = preg_replace('/style\s*=\s*["\'][^"\']*expression[^"\']*["\']/i', '', $data);
        
        return $data;
    }

    public static function sqlInjectCheck($string)
    {
        $check = preg_match('/select|insert|update|delete|union|into|load_file|outfile|or|and/i', $string);
        return $check ? false : true;
    }

    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateUsername($username, $minLength = null, $maxLength = null)
    {
        if ($minLength === null) {
            $minLength = class_exists('Setting') ? Setting::getUsernameMinLength() : 2;
        }
        if ($maxLength === null) {
            $maxLength = class_exists('Setting') ? Setting::getUsernameMaxLength() : 20;
        }
        
        $len = mb_strlen($username, 'UTF-8');
        if ($len < $minLength || $len > $maxLength) {
            return false;
        }
        return preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]+$/u', $username);
    }

    public static function validatePhone($phone)
    {
        return preg_match('/^1[3-9]\d{9}$/', $phone);
    }

    public static function sanitizeFilename($filename)
    {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        $filename = preg_replace('/\.{2,}/', '.', $filename);
        return $filename;
    }

    public static function checkFileUpload($file, $allowedTypes = null, $maxSize = null)
    {
        $errors = [];
        
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['valid' => false, 'error' => '没有上传文件'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => '上传出错：' . $file['error']];
        }

        $maxSize = $maxSize ?: (defined('UPLOAD_MAX_SIZE') ? UPLOAD_MAX_SIZE : 5 * 1024 * 1024);
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => '文件大小超过限制'];
        }

        $allowedTypes = $allowedTypes ?: (defined('UPLOAD_ALLOW_TYPES') ? UPLOAD_ALLOW_TYPES : 'jpg,jpeg,png,gif,webp');
        $allowedTypes = explode(',', $allowedTypes);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowedTypes)) {
            return ['valid' => false, 'error' => '不允许的文件类型'];
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimeTypes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp'
            ];

            if (!in_array($mimeType, $allowedMimeTypes)) {
                return ['valid' => false, 'error' => '文件类型验证失败'];
            }
        }

        return ['valid' => true, 'error' => ''];
    }
}

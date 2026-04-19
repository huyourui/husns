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
 * 国际化 (i18n) 核心类
 * 
 * 功能特性：
 * - 支持多语言切换（JSON语言包）
 * - 自动检测浏览器语言
 * - 支持手动切换语言
 * - 支持变量替换
 * - 语言回退机制（找不到翻译时回退到英文）
 * 
 * @package HuSNS
 * @version 1.0.0
 */
class I18n
{
    /**
     * 当前语言代码
     * @var string
     */
    private static $currentLang = 'zh-cn';
    
    /**
     * 语言包缓存
     * @var array
     */
    private static $translations = [];
    
    /**
     * 语言包目录
     * @var string
     */
    private static $langDir;
    
    /**
     * 默认语言
     * @var string
     */
    private static $defaultLang = 'en';
    
    /**
     * 可用语言列表
     * @var array
     */
    private static $availableLangs = [];
    
    /**
     * 初始化
     * 
     * @param string $langDir 语言包目录
     */
    public static function init($langDir = null)
    {
        if ($langDir === null) {
            self::$langDir = ROOT_PATH . 'languages/';
        } else {
            self::$langDir = rtrim($langDir, '/') . '/';
        }
        
        // 扫描可用语言
        self::scanAvailableLanguages();
        
        // 确定当前语言
        self::determineLanguage();
    }
    
    /**
     * 扫描可用语言包
     */
    private static function scanAvailableLanguages()
    {
        self::$availableLangs = [];
        
        if (!is_dir(self::$langDir)) {
            return;
        }
        
        $files = glob(self::$langDir . '*.json');
        foreach ($files as $file) {
            $code = basename($file, '.json');
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            
            if ($data && isset($data['meta'])) {
                self::$availableLangs[$code] = [
                    'code' => $code,
                    'name' => $data['meta']['name'] ?? $code,
                    'native_name' => $data['meta']['native_name'] ?? $data['meta']['name'] ?? $code,
                    'author' => $data['meta']['author'] ?? '',
                    'version' => $data['meta']['version'] ?? '1.0.0'
                ];
            }
        }
    }
    
    /**
     * 确定当前语言
     * 优先级：用户设置 > URL参数 > 系统配置 > 浏览器语言 > 默认语言
     */
    private static function determineLanguage()
    {
        // 1. 检查用户会话中的语言设置
        if (isset($_SESSION['user_language']) && self::isValidLanguage($_SESSION['user_language'])) {
            self::$currentLang = $_SESSION['user_language'];
            return;
        }
        
        // 2. 检查URL参数
        if (isset($_GET['lang']) && self::isValidLanguage($_GET['lang'])) {
            self::$currentLang = strtolower($_GET['lang']);
            $_SESSION['user_language'] = self::$currentLang;
            return;
        }
        
        // 3. 检查系统配置（如果是自动模式，则检测浏览器语言）
        $langMode = self::getLanguageMode();
        $defaultLang = self::getDefaultLanguage();
        
        if ($langMode === 'auto') {
            // 自动模式：检测浏览器语言
            $browserLang = self::detectBrowserLanguage();
            if ($browserLang && self::isValidLanguage($browserLang)) {
                self::$currentLang = $browserLang;
            } elseif ($defaultLang && self::isValidLanguage($defaultLang)) {
                self::$currentLang = $defaultLang;
            } else {
                self::$currentLang = self::$defaultLang;
            }
        } else {
            // 手动模式：使用默认语言
            if ($defaultLang && self::isValidLanguage($defaultLang)) {
                self::$currentLang = $defaultLang;
            } else {
                self::$currentLang = self::$defaultLang;
            }
        }
        
        $_SESSION['user_language'] = self::$currentLang;
    }
    
    /**
     * 获取语言切换模式
     * 
     * @return string 'auto' 或 'manual'
     */
    private static function getLanguageMode()
    {
        // 安装模式下返回 manual
        if (defined('INSTALL_MODE') && INSTALL_MODE) {
            return 'manual';
        }
        
        // 检查数据库配置
        if (!defined('DB_PREFIX')) {
            return 'manual';
        }
        
        try {
            $db = Database::getInstance();
            $result = $db->fetch("SELECT value FROM __PREFIX__settings WHERE `key` = 'language_mode'");
            if ($result && isset($result['value'])) {
                return $result['value'];
            }
        } catch (Exception $e) {
        }
        
        return 'manual';
    }
    
    /**
     * 获取默认语言
     * 
     * @return string
     */
    private static function getDefaultLanguage()
    {
        // 安装模式下返回英文
        if (defined('INSTALL_MODE') && INSTALL_MODE) {
            return 'en';
        }
        
        if (!defined('DB_PREFIX')) {
            return 'en';
        }
        
        try {
            $db = Database::getInstance();
            $result = $db->fetch("SELECT value FROM __PREFIX__settings WHERE `key` = 'default_language'");
            if ($result && isset($result['value']) && !empty($result['value'])) {
                return $result['value'];
            }
        } catch (Exception $e) {
        }
        
        return 'en';
    }
    
    /**
     * 检测浏览器语言
     * 
     * @return string|null
     */
    private static function detectBrowserLanguage()
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }
        
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $langs = [];
        
        // 解析 Accept-Language 头
        preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)\s*(?:;\s*q\s*=\s*([0-9.]+))?/i', $acceptLang, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $i => $lang) {
                $q = isset($matches[2][$i]) && $matches[2][$i] !== '' ? (float)$matches[2][$i] : 1.0;
                $langs[strtolower($lang)] = $q;
            }
        }
        
        // 按优先级排序
        arsort($langs);
        
        // 查找匹配的语言
        foreach ($langs as $lang => $q) {
            // 完全匹配
            if (self::isValidLanguage($lang)) {
                return $lang;
            }
            
            // 基础语言匹配（如 zh-cn 匹配 zh）
            $baseLang = substr($lang, 0, 2);
            foreach (self::$availableLangs as $code => $info) {
                if (strpos($code, $baseLang) === 0) {
                    return $code;
                }
            }
        }
        
        return null;
    }
    
    /**
     * 检查语言是否有效
     * 
     * @param string $lang 语言代码
     * @return bool
     */
    public static function isValidLanguage($lang)
    {
        return isset(self::$availableLangs[strtolower($lang)]);
    }
    
    /**
     * 获取当前语言代码
     * 
     * @return string
     */
    public static function getCurrentLang()
    {
        return self::$currentLang;
    }
    
    /**
     * 设置当前语言
     * 
     * @param string $lang 语言代码
     * @return bool
     */
    public static function setLang($lang)
    {
        $lang = strtolower($lang);
        
        if (!self::isValidLanguage($lang)) {
            return false;
        }
        
        self::$currentLang = $lang;
        $_SESSION['user_language'] = $lang;
        
        // 清除缓存，重新加载
        unset(self::$translations[$lang]);
        
        return true;
    }
    
    /**
     * 加载语言包
     * 
     * @param string $lang 语言代码
     * @return array
     */
    private static function loadLanguage($lang)
    {
        if (isset(self::$translations[$lang])) {
            return self::$translations[$lang];
        }
        
        $file = self::$langDir . $lang . '.json';
        
        if (!file_exists($file)) {
            self::$translations[$lang] = [];
            return [];
        }
        
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if (!$data) {
            self::$translations[$lang] = [];
            return [];
        }
        
        // 移除 meta 信息，只保留翻译
        unset($data['meta']);
        
        self::$translations[$lang] = $data;
        return $data;
    }
    
    /**
     * 获取翻译文本
     * 
     * @param string $key 翻译键（支持点号分隔，如 'common.home'）
     * @param array $params 替换参数
     * @param string $lang 指定语言（默认当前语言）
     * @return string
     */
    public static function get($key, $params = [], $lang = null)
    {
        if ($lang === null) {
            $lang = self::$currentLang;
        }
        
        $translations = self::loadLanguage($lang);
        
        // 支持点号分隔的键
        $keys = explode('.', $key);
        $value = $translations;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                $value = null;
                break;
            }
        }
        
        // 如果当前语言找不到，尝试回退到默认语言
        if ($value === null && $lang !== self::$defaultLang) {
            return self::get($key, $params, self::$defaultLang);
        }
        
        // 如果还是找不到，返回键名
        if ($value === null) {
            return $key;
        }
        
        // 变量替换
        if (is_string($value) && !empty($params)) {
            foreach ($params as $search => $replace) {
                $value = str_replace('{' . $search . '}', $replace, $value);
            }
        }
        
        return $value;
    }
    
    /**
     * 简写方法：获取翻译文本
     * 
     * @param string $key 翻译键
     * @param array $params 替换参数
     * @return string
     */
    public static function t($key, $params = [])
    {
        return self::get($key, $params);
    }
    
    /**
     * 获取所有可用语言
     * 
     * @return array
     */
    public static function getAvailableLanguages()
    {
        return self::$availableLangs;
    }
    
    /**
     * 获取当前语言信息
     * 
     * @return array|null
     */
    public static function getCurrentLanguageInfo()
    {
        return self::$availableLangs[self::$currentLang] ?? null;
    }
    
    /**
     * 获取语言选择HTML
     * 
     * @param string $current 当前选中的语言
     * @param string $name 表单字段名
     * @return string
     */
    public static function renderLanguageSelector($current = null, $name = 'language')
    {
        if ($current === null) {
            $current = self::$currentLang;
        }
        
        $html = '<select name="' . htmlspecialchars($name) . '" class="language-selector">';
        
        foreach (self::$availableLangs as $code => $info) {
            $selected = ($code === $current) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($code) . '"' . $selected . '>';
            $html .= htmlspecialchars($info['name']);
            $html .= '</option>';
        }
        
        $html .= '</select>';
        
        return $html;
    }
    
    /**
     * 切换语言接口（用于AJAX请求）
     * 
     * @param string $lang 语言代码
     * @return array
     */
    public static function switchLanguage($lang)
    {
        if (!self::setLang($lang)) {
            return [
                'success' => false,
                'message' => 'Invalid language code'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Language switched successfully',
            'language' => $lang,
            'language_name' => self::$availableLangs[$lang]['name'] ?? $lang
        ];
    }
}

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
 * 视图引擎类
 * 
 * 功能特性：
 * - 支持多主题切换
 * - 布局系统
 * - 模板渲染
 * - 数据传递
 */
class View
{
    private $templateDir;
    private $data = [];
    private $layout = 'main';
    private $theme = 'default';
    
    private static $currentTheme = null;

    public function __construct()
    {
        $this->theme = self::getCurrentTheme();
        $this->templateDir = ROOT_PATH . 'templates/' . $this->theme . '/';
    }
    
    /**
     * 获取当前主题
     * 
     * @return string 主题名称
     */
    public static function getCurrentTheme()
    {
        if (self::$currentTheme !== null) {
            return self::$currentTheme;
        }
        
        try {
            $db = Database::getInstance();
            $result = $db->fetch(
                "SELECT value FROM __PREFIX__settings WHERE `key` = 'current_theme'"
            );
            
            if ($result && !empty($result['value'])) {
                $theme = $result['value'];
                $themeDir = ROOT_PATH . 'templates/' . $theme;
                
                if (is_dir($themeDir) && file_exists($themeDir . '/theme.json')) {
                    self::$currentTheme = $theme;
                    return $theme;
                }
            }
        } catch (Exception $e) {
        }
        
        self::$currentTheme = 'default';
        return 'default';
    }
    
    /**
     * 设置当前主题
     * 
     * @param string $theme 主题名称
     * @return bool
     */
    public static function setCurrentTheme($theme)
    {
        $themeDir = ROOT_PATH . 'templates/' . $theme;
        
        if (!is_dir($themeDir) || !file_exists($themeDir . '/theme.json')) {
            return false;
        }
        
        try {
            $db = Database::getInstance();
            
            $exists = $db->fetch(
                "SELECT id FROM __PREFIX__settings WHERE `key` = 'current_theme'"
            );
            
            if ($exists) {
                $db->update('settings', 
                    ['value' => $theme, 'updated_at' => time()],
                    "`key` = 'current_theme'"
                );
            } else {
                $db->insert('settings', [
                    'key' => 'current_theme',
                    'value' => $theme,
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
            }
            
            self::$currentTheme = $theme;
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取所有可用主题
     * 
     * @return array 主题列表
     */
    public static function getAvailableThemes()
    {
        $themes = [];
        $templateDir = ROOT_PATH . 'templates';
        
        if (!is_dir($templateDir)) {
            return $themes;
        }
        
        $dirs = scandir($templateDir);
        
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $themePath = $templateDir . DIRECTORY_SEPARATOR . $dir;
            $configFile = $themePath . DIRECTORY_SEPARATOR . 'theme.json';
            
            if (is_dir($themePath) && file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                
                if ($config && isset($config['name'])) {
                    $screenshotPath = $themePath . DIRECTORY_SEPARATOR . 'screenshot.png';
                    $screenshot = file_exists($screenshotPath) 
                        ? Helper::getBaseUrl() . '/templates/' . $dir . '/screenshot.png'
                        : null;
                    
                    $themes[] = [
                        'id' => $dir,
                        'name' => $config['name'],
                        'version' => $config['version'] ?? '1.0.0',
                        'author' => $config['author'] ?? '未知',
                        'description' => $config['description'] ?? '',
                        'homepage' => $config['homepage'] ?? '',
                        'screenshot' => $screenshot,
                        'path' => $themePath,
                        'is_current' => $dir === self::getCurrentTheme()
                    ];
                }
            }
        }
        
        usort($themes, function($a, $b) {
            if ($a['is_current']) return -1;
            if ($b['is_current']) return 1;
            return strcmp($a['name'], $b['name']);
        });
        
        return $themes;
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
        return $this;
    }
    
    /**
     * 设置主题
     * 
     * @param string $theme 主题名称
     * @return $this
     */
    public function setTheme($theme)
    {
        $themeDir = ROOT_PATH . 'templates/' . $theme;
        
        if (is_dir($themeDir)) {
            $this->theme = $theme;
            $this->templateDir = $themeDir . '/';
        }
        
        return $this;
    }
    
    /**
     * 获取当前主题名称
     * 
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }
    
    /**
     * 获取主题目录URL
     * 
     * @return string
     */
    public function themeUrl()
    {
        return Helper::getBaseUrl() . '/templates/' . $this->theme;
    }

    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    public function render($template, $data = [])
    {
        $this->data = array_merge($this->data, $data);
        
        $templateFile = $this->templateDir . $template . '.php';
        
        if (!file_exists($templateFile)) {
            $defaultTemplateFile = ROOT_PATH . 'templates/default/' . $template . '.php';
            if (file_exists($defaultTemplateFile)) {
                $templateFile = $defaultTemplateFile;
            } else {
                throw new Exception("模板文件不存在：{$template}");
            }
        }

        $content = $this->renderTemplate($templateFile);

        if ($this->layout) {
            $layoutFile = $this->templateDir . 'layouts/' . $this->layout . '.php';
            
            if (!file_exists($layoutFile)) {
                $layoutFile = ROOT_PATH . 'templates/default/layouts/' . $this->layout . '.php';
            }
            
            if (file_exists($layoutFile)) {
                $this->data['content'] = $content;
                $content = $this->renderTemplate($layoutFile);
            }
        }

        return $content;
    }

    private function renderTemplate($file)
    {
        extract($this->data);
        ob_start();
        include $file;
        return ob_get_clean();
    }

    public function partial($template, $data = [])
    {
        $templateFile = $this->templateDir . $template . '.php';
        
        if (!file_exists($templateFile)) {
            $templateFile = ROOT_PATH . 'templates/default/' . $template . '.php';
        }
        
        if (!file_exists($templateFile)) {
            return '';
        }

        $mergedData = array_merge($this->data, $data);
        extract($mergedData);
        ob_start();
        include $templateFile;
        return ob_get_clean();
    }

    public function escape($string)
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }

    public function url($path = '')
    {
        return Helper::url($path);
    }

    public function asset($path)
    {
        return Helper::asset($path);
    }
    
    /**
     * 获取主题资源URL
     * 
     * @param string $path 资源路径
     * @return string
     */
    public function themeAsset($path)
    {
        return Helper::getBaseUrl() . '/templates/' . $this->theme . '/static/' . ltrim($path, '/');
    }

    public function uploadUrl($path)
    {
        return Helper::uploadUrl($path);
    }

    public function csrf()
    {
        return Helper::csrfField();
    }

    public function flash($type)
    {
        return Helper::getFlash($type);
    }

    public function hasFlash($type)
    {
        return Helper::hasFlash($type);
    }

    public function formatFileSize($bytes)
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }
    }

    public function avatar($avatar, $username, $size = 'default')
    {
        return Helper::avatar($avatar, $username, $size);
    }
}

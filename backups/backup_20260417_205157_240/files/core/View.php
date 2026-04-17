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
class View
{
    private $templateDir;
    private $data = [];
    private $layout = 'main';

    public function __construct()
    {
        $this->templateDir = ROOT_PATH . 'templates/default/';
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
        return $this;
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
            throw new Exception("模板文件不存在：{$template}");
        }

        $content = $this->renderTemplate($templateFile);

        if ($this->layout) {
            $layoutFile = $this->templateDir . 'layouts/' . $this->layout . '.php';
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

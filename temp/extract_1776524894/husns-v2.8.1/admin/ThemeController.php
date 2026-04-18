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
 * 主题管理控制器
 * 
 * 功能：
 * - 查看已安装主题
 * - 切换主题
 * - 预览主题
 * - 主题详情
 */
class ThemeController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('admin');
    }
    
    /**
     * 主题管理首页
     */
    public function index()
    {
        $this->checkAdmin();
        
        $themes = View::getAvailableThemes();
        $currentTheme = View::getCurrentTheme();
        
        $this->render('admin/themes', [
            'themes' => $themes,
            'currentTheme' => $currentTheme,
            'pageTitle' => '主题管理'
        ]);
    }
    
    /**
     * 切换主题
     */
    public function activate()
    {
        $this->checkAdmin();
        
        if (!Helper::isPost()) {
            $this->jsonError('请求方法错误');
        }
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $theme = trim(Helper::post('theme'));
        
        if (empty($theme)) {
            $this->jsonError('请选择要启用的主题');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $theme)) {
            $this->jsonError('主题名称格式不正确');
        }
        
        $themeDir = ROOT_PATH . 'templates' . DIRECTORY_SEPARATOR . $theme;
        $configFile = $themeDir . DIRECTORY_SEPARATOR . 'theme.json';
        
        if (!is_dir($themeDir) || !file_exists($configFile)) {
            $this->jsonError('主题不存在');
        }
        
        $config = json_decode(file_get_contents($configFile), true);
        if (!$config || !isset($config['name'])) {
            $this->jsonError('主题配置文件无效');
        }
        
        $result = View::setCurrentTheme($theme);
        
        if ($result) {
            $this->jsonSuccess([
                'theme' => $theme,
                'name' => $config['name']
            ], '主题切换成功');
        } else {
            $this->jsonError('主题切换失败');
        }
    }
    
    /**
     * 获取主题详情
     */
    public function detail()
    {
        $this->checkAdmin();
        
        $theme = trim(Helper::get('theme'));
        
        if (empty($theme)) {
            $this->jsonError('请指定主题');
        }
        
        $themeDir = ROOT_PATH . 'templates' . DIRECTORY_SEPARATOR . $theme;
        $configFile = $themeDir . DIRECTORY_SEPARATOR . 'theme.json';
        
        if (!is_dir($themeDir) || !file_exists($configFile)) {
            $this->jsonError('主题不存在');
        }
        
        $config = json_decode(file_get_contents($configFile), true);
        
        if (!$config) {
            $this->jsonError('主题配置文件无效');
        }
        
        $screenshotPath = $themeDir . DIRECTORY_SEPARATOR . 'screenshot.png';
        $screenshot = file_exists($screenshotPath) 
            ? Helper::getBaseUrl() . '/templates/' . $theme . '/screenshot.png'
            : null;
        
        $info = [
            'id' => $theme,
            'name' => $config['name'] ?? $theme,
            'version' => $config['version'] ?? '1.0.0',
            'author' => $config['author'] ?? '未知',
            'description' => $config['description'] ?? '',
            'homepage' => $config['homepage'] ?? '',
            'license' => $config['license'] ?? '',
            'requires' => $config['requires'] ?? [],
            'screenshot' => $screenshot,
            'is_current' => $theme === View::getCurrentTheme(),
            'files' => $this->getThemeFiles($themeDir)
        ];
        
        $this->jsonSuccess($info);
    }
    
    /**
     * 获取主题文件列表
     * 
     * @param string $dir 目录路径
     * @return array
     */
    private function getThemeFiles($dir)
    {
        $files = [];
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($path)) {
                $files[$item] = $this->getThemeFiles($path);
            } else {
                $files[] = [
                    'name' => $item,
                    'size' => filesize($path),
                    'modified' => filemtime($path)
                ];
            }
        }
        
        return $files;
    }
    
    /**
     * 重置为默认主题
     */
    public function reset()
    {
        $this->checkAdmin();
        
        if (!Helper::isPost()) {
            $this->jsonError('请求方法错误');
        }
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $result = View::setCurrentTheme('default');
        
        if ($result) {
            $this->jsonSuccess(null, '已重置为默认主题');
        } else {
            $this->jsonError('重置失败');
        }
    }
}

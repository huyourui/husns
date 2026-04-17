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
class PluginController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('admin');
    }

    public function index()
    {
        $this->checkAdmin();
        
        $plugins = Hook::getPlugins();
        
        $dbPlugins = $this->db->fetchAll("SELECT * FROM __PREFIX__plugins");
        $pluginStatus = [];
        
        foreach ($dbPlugins as $p) {
            $pluginStatus[$p['name']] = $p['status'];
        }

        foreach ($plugins as &$plugin) {
            $plugin['installed'] = isset($pluginStatus[$plugin['name']]);
            $plugin['status'] = $pluginStatus[$plugin['name']] ?? 0;
            
            $infoFile = $plugin['path'] . '/info.json';
            if (file_exists($infoFile)) {
                $plugin['info'] = json_decode(file_get_contents($infoFile), true);
            } else {
                $plugin['info'] = [
                    'name' => $plugin['name'],
                    'title' => $plugin['name'],
                    'version' => '1.0.0',
                    'author' => 'Unknown',
                    'description' => ''
                ];
            }
        }

        $this->render('admin/plugins', ['plugins' => $plugins]);
    }

    public function enable()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $name = Helper::post('name');
        $plugins = Hook::getPlugins();
        
        if (!isset($plugins[$name])) {
            $this->jsonError('插件不存在');
        }

        $result = Hook::enablePlugin($name);
        
        if ($result) {
            Hook::trigger('plugin_enabled', ['name' => $name]);
            $this->jsonSuccess(null, '插件已启用');
        }
        
        $this->jsonError('启用失败');
    }

    public function disable()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $name = Helper::post('name');
        
        $result = Hook::disablePlugin($name);
        
        if ($result) {
            Hook::trigger('plugin_disabled', ['name' => $name]);
            $this->jsonSuccess(null, '插件已禁用');
        }
        
        $this->jsonError('禁用失败');
    }

    public function install()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $name = Helper::post('name');
        $plugins = Hook::getPlugins();
        
        if (!isset($plugins[$name])) {
            $this->jsonError('插件不存在');
        }

        $installFile = $plugins[$name]['path'] . '/install.php';
        
        if (file_exists($installFile)) {
            include $installFile;
        }

        $result = Hook::enablePlugin($name);
        
        if ($result) {
            Hook::trigger('plugin_installed', ['name' => $name]);
            $this->jsonSuccess(null, '插件安装成功');
        }
        
        $this->jsonError('安装失败');
    }

    public function uninstall()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $name = Helper::post('name');
        $plugins = Hook::getPlugins();
        
        if (!isset($plugins[$name])) {
            $this->jsonError('插件不存在');
        }

        $uninstallFile = $plugins[$name]['path'] . '/uninstall.php';
        
        if (file_exists($uninstallFile)) {
            include $uninstallFile;
        }

        $this->db->delete('plugins', 'name = ?', [$name]);
        
        Hook::trigger('plugin_uninstalled', ['name' => $name]);
        
        $this->jsonSuccess(null, '插件已卸载');
    }
}

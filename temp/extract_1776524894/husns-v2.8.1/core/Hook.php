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
class Hook
{
    private static $hooks = [];
    private static $plugins = [];

    public static function register($name, $callback, $priority = 10)
    {
        if (!isset(self::$hooks[$name])) {
            self::$hooks[$name] = [];
        }
        self::$hooks[$name][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        usort(self::$hooks[$name], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }

    public static function trigger($name, $params = [])
    {
        if (!isset(self::$hooks[$name])) {
            return $params;
        }

        foreach (self::$hooks[$name] as $hook) {
            if (is_callable($hook['callback'])) {
                $result = call_user_func($hook['callback'], $params);
                if ($result !== null) {
                    $params = $result;
                }
            }
        }

        return $params;
    }

    public static function has($name)
    {
        return isset(self::$hooks[$name]) && !empty(self::$hooks[$name]);
    }

    public static function remove($name, $callback = null)
    {
        if (!isset(self::$hooks[$name])) {
            return false;
        }

        if ($callback === null) {
            unset(self::$hooks[$name]);
            return true;
        }

        foreach (self::$hooks[$name] as $key => $hook) {
            if ($hook['callback'] === $callback) {
                unset(self::$hooks[$name][$key]);
                return true;
            }
        }

        return false;
    }

    public static function loadPlugins()
    {
        $pluginDir = ROOT_PATH . 'plugins';
        if (!is_dir($pluginDir)) {
            return;
        }

        $dirs = scandir($pluginDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $pluginPath = $pluginDir . '/' . $dir;
            $pluginFile = $pluginPath . '/Plugin.php';

            if (is_dir($pluginPath) && file_exists($pluginFile)) {
                self::$plugins[$dir] = [
                    'path' => $pluginPath,
                    'file' => $pluginFile,
                    'name' => $dir,
                    'enabled' => false
                ];
            }
        }

        self::loadEnabledPlugins();
    }

    private static function loadEnabledPlugins()
    {
        if (!file_exists(ROOT_PATH . 'install.lock')) {
            return;
        }

        try {
            $db = Database::getInstance();
            $plugins = $db->fetchAll("SELECT * FROM __PREFIX__plugins WHERE status = 1");
            
            foreach ($plugins as $plugin) {
                $name = $plugin['name'];
                if (isset(self::$plugins[$name])) {
                    self::$plugins[$name]['enabled'] = true;
                    require_once self::$plugins[$name]['file'];
                    $className = 'Plugin\\' . $name . '\\Plugin';
                    if (class_exists($className)) {
                        new $className();
                    }
                }
            }
        } catch (Exception $e) {
        }
    }

    public static function getPlugins()
    {
        return self::$plugins;
    }

    public static function getPlugin($name)
    {
        return isset(self::$plugins[$name]) ? self::$plugins[$name] : null;
    }

    public static function enablePlugin($name)
    {
        if (!isset(self::$plugins[$name])) {
            return false;
        }

        $db = Database::getInstance();
        $exists = $db->fetch("SELECT * FROM __PREFIX__plugins WHERE name = ?", [$name]);

        if ($exists) {
            $db->update('plugins', ['status' => 1, 'updated_at' => time()], 'name = ?', [$name]);
        } else {
            $db->insert('plugins', [
                'name' => $name,
                'status' => 1,
                'created_at' => time(),
                'updated_at' => time()
            ]);
        }

        return true;
    }

    public static function disablePlugin($name)
    {
        if (!isset(self::$plugins[$name])) {
            return false;
        }

        $db = Database::getInstance();
        $db->update('plugins', ['status' => 0, 'updated_at' => time()], 'name = ?', [$name]);

        return true;
    }
}

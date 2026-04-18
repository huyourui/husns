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
class InstallController
{
    private $view;
    private $errors = [];
    private $step = 1;

    public function __construct()
    {
        $this->view = new View();
        $this->view->setLayout(false);
    }

    public function index()
    {
        $this->step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
        
        switch ($this->step) {
            case 1:
                $this->step1();
                break;
            case 2:
                $this->step2();
                break;
            case 3:
                $this->step3();
                break;
            case 4:
                $this->step4();
                break;
            default:
                $this->step1();
        }
    }

    private function step1()
    {
        if (Helper::isPost()) {
            $requirements = $this->checkRequirements();
            if ($this->checkAllPassed($requirements)) {
                Helper::redirect('install.php?step=2');
            }
        }
        
        $requirements = $this->checkRequirements();
        $this->view->assign('step', 1);
        $this->view->assign('requirements', $requirements);
        $this->view->assign('passed', $this->checkAllPassed($requirements));
        echo $this->view->render('install/step1');
    }

    private function step2()
    {
        if (Helper::isPost()) {
            $host = trim(Helper::post('db_host', 'localhost'));
            $port = (int)Helper::post('db_port', 3306);
            $name = trim(Helper::post('db_name', 'husns'));
            $user = trim(Helper::post('db_user', 'root'));
            $pass = Helper::post('db_pass', '');
            $prefix = trim(Helper::post('db_prefix', 'husns_'));

            if (empty($host) || empty($name) || empty($user)) {
                $this->errors[] = '请填写完整的数据库信息';
            }

            if (!Database::testConnection($host, $port, $name, $user, $pass)) {
                if (!Database::createDatabase($host, $port, $user, $pass, $name)) {
                    $this->errors[] = '数据库连接失败，请检查配置或手动创建数据库';
                }
            }

            if (empty($this->errors)) {
                $_SESSION['install_config'] = [
                    'db_host' => $host,
                    'db_port' => $port,
                    'db_name' => $name,
                    'db_user' => $user,
                    'db_pass' => $pass,
                    'db_prefix' => $prefix
                ];
                Helper::redirect('install.php?step=3');
            }
        }

        $this->view->assign('step', 2);
        $this->view->assign('errors', $this->errors);
        echo $this->view->render('install/step2');
    }

    private function step3()
    {
        if (!isset($_SESSION['install_config'])) {
            Helper::redirect('install.php?step=2');
        }

        if (Helper::isPost()) {
            try {
                $username = trim(Helper::post('username', ''));
                $password = Helper::post('password', '');
                $confirmPassword = Helper::post('confirm_password', '');
                $email = trim(Helper::post('email', ''));

                if (!Security::validateUsername($username)) {
                    $this->errors[] = '用户名格式不正确（3-20位字母开头，可包含数字和下划线）';
                }

                if (strlen($password) < 6) {
                    $this->errors[] = '密码长度至少6位';
                }

                if ($password !== $confirmPassword) {
                    $this->errors[] = '两次密码输入不一致';
                }

                if (!Security::validateEmail($email)) {
                    $this->errors[] = '邮箱格式不正确';
                }

                if (empty($this->errors)) {
                    $config = $_SESSION['install_config'];
                    $this->writeConfig($config);
                    
                    require_once ROOT_PATH . 'config.php';
                    
                    $this->createTables($config);
                    $this->createAdmin($username, $password, $email, $config);
                    $this->createLockFile();
                    unset($_SESSION['install_config']);
                    Helper::redirect('install.php?step=4');
                }
            } catch (Exception $e) {
                $this->errors[] = '安装错误：' . $e->getMessage();
            }
        }

        $this->view->assign('step', 3);
        $this->view->assign('errors', $this->errors);
        echo $this->view->render('install/step3');
    }

    private function step4()
    {
        $this->view->assign('step', 4);
        echo $this->view->render('install/step4');
    }

    private function checkRequirements()
    {
        if (!is_dir(ROOT_PATH . 'uploads')) {
            mkdir(ROOT_PATH . 'uploads', 0755, true);
        }
        
        $this->tryFixPermission(ROOT_PATH . 'uploads');
        $this->tryFixPermission(ROOT_PATH);
        
        return [
            'php_version' => [
                'name' => 'PHP版本',
                'required' => '7.4+',
                'current' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, '7.4.0', '>=')
            ],
            'pdo_mysql' => [
                'name' => 'PDO MySQL扩展',
                'required' => '必须',
                'current' => extension_loaded('pdo_mysql') ? '已安装' : '未安装',
                'passed' => extension_loaded('pdo_mysql')
            ],
            'gd' => [
                'name' => 'GD库',
                'required' => '必须',
                'current' => extension_loaded('gd') ? '已安装' : '未安装',
                'passed' => extension_loaded('gd')
            ],
            'mbstring' => [
                'name' => 'MBString扩展',
                'required' => '必须',
                'current' => extension_loaded('mbstring') ? '已安装' : '未安装',
                'passed' => extension_loaded('mbstring')
            ],
            'json' => [
                'name' => 'JSON扩展',
                'required' => '必须',
                'current' => extension_loaded('json') ? '已安装' : '未安装',
                'passed' => extension_loaded('json')
            ],
            'upload_dir' => [
                'name' => '上传目录可写',
                'required' => '必须',
                'current' => is_writable(ROOT_PATH . 'uploads') ? '可写' : '不可写',
                'passed' => is_writable(ROOT_PATH . 'uploads')
            ],
            'config_dir' => [
                'name' => '配置文件可写',
                'required' => '必须',
                'current' => is_writable(ROOT_PATH) ? '可写' : '不可写',
                'passed' => is_writable(ROOT_PATH)
            ]
        ];
    }
    
    private function tryFixPermission($path)
    {
        if (!is_writable($path)) {
            @chmod($path, 0755);
        }
        if (!is_writable($path)) {
            @chmod($path, 0777);
        }
    }

    private function checkAllPassed($requirements)
    {
        foreach ($requirements as $item) {
            if (!$item['passed']) {
                return false;
            }
        }
        return true;
    }

    private function writeConfig($config)
    {
        $configFile = ROOT_PATH . 'config.php';
        
        if (file_exists($configFile)) {
            $this->tryFixPermission($configFile);
            if (!is_writable($configFile)) {
                throw new Exception('配置文件不可写，请检查权限');
            }
        }
        
        if (!file_exists($configFile)) {
            touch($configFile);
            $this->tryFixPermission($configFile);
        }
        
        $configContent = <<<'PHP'
<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)) . '/');
}

define('DB_HOST', '{db_host}');
define('DB_PORT', {db_port});
define('DB_NAME', '{db_name}');
define('DB_USER', '{db_user}');
define('DB_PASS', '{db_pass}');
define('DB_PREFIX', '{db_prefix}');
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', '{site_url}');
define('SITE_NAME', 'HuSNS');
define('SITE_DEBUG', false);
define('URL_REWRITE', false);

define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('UPLOAD_ALLOW_TYPES', 'jpg,jpeg,png,gif');

define('SESSION_NAME', 'HUSNS_SESSION');
define('SESSION_LIFETIME', 7200);

define('PASSWORD_SALT', '{salt}');

date_default_timezone_set('Asia/Shanghai');

if (SITE_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
} else {
    error_reporting(0);
    ini_set('display_errors', 'Off');
}

session_name(SESSION_NAME);
session_start();
PHP;

        $baseUrl = Helper::getBaseUrl();
        $salt = Security::generateRandomString(32);
        
        $configContent = str_replace(
            ['{db_host}', '{db_port}', '{db_name}', '{db_user}', '{db_pass}', '{db_prefix}', '{site_url}', '{salt}'],
            [$config['db_host'], $config['db_port'], $config['db_name'], $config['db_user'], $config['db_pass'], $config['db_prefix'], $baseUrl, $salt],
            $configContent
        );

        $result = file_put_contents($configFile, $configContent);
        if ($result === false) {
            throw new Exception('配置文件写入失败');
        }
    }

    private function createTables($config)
    {
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $prefix = $config['db_prefix'];

        $dropTables = [
            "DROP TABLE IF EXISTS `{$prefix}version`",
            "DROP TABLE IF EXISTS `{$prefix}settings`",
            "DROP TABLE IF EXISTS `{$prefix}plugins`",
            "DROP TABLE IF EXISTS `{$prefix}point_logs`",
            "DROP TABLE IF EXISTS `{$prefix}point_rules`",
            "DROP TABLE IF EXISTS `{$prefix}announcements`",
            "DROP TABLE IF EXISTS `{$prefix}notifications`",
            "DROP TABLE IF EXISTS `{$prefix}follows`",
            "DROP TABLE IF EXISTS `{$prefix}likes`",
            "DROP TABLE IF EXISTS `{$prefix}comments`",
            "DROP TABLE IF EXISTS `{$prefix}posts`",
            "DROP TABLE IF EXISTS `{$prefix}users`",
        ];

        foreach ($dropTables as $sql) {
            $pdo->exec($sql);
        }

        $tables = [
            "CREATE TABLE IF NOT EXISTS `{$prefix}users` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
              `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
              `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
              `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
              `bio` varchar(255) NOT NULL DEFAULT '' COMMENT '个人简介',
              `remember_token` varchar(64) NOT NULL DEFAULT '' COMMENT '保持登录令牌',
              `is_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否管理员',
              `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
              `ban_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '限制类型：0正常 1禁言 2封禁',
              `ban_until` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '限制到期时间，0表示永久',
              `ban_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '限制原因',
              `points` int(11) NOT NULL DEFAULT 0 COMMENT '积分',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '注册时间',
              `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
              PRIMARY KEY (`id`),
              UNIQUE KEY `username` (`username`),
              UNIQUE KEY `email` (`email`),
              KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表'",
            
            "CREATE TABLE IF NOT EXISTS `{$prefix}posts` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
              `content` text NOT NULL COMMENT '内容',
              `images` varchar(2000) NOT NULL DEFAULT '' COMMENT '图片JSON',
              `attachments` text COMMENT '附件JSON',
              `videos` text COMMENT '视频JSON',
              `repost_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '转发的原微博ID，0为原创',
              `repost_user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '被转发微博的用户ID',
              `likes` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
              `comments` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评论数',
              `reposts` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '转发数',
              `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0删除 1正常',
              `is_pinned` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否置顶：0否 1是',
              `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否精华：0否 1是',
              `edit_count` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '编辑次数',
              `edited_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后编辑时间',
              `ip` varchar(50) NOT NULL DEFAULT '' COMMENT '发布IP',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `status` (`status`),
              KEY `created_at` (`created_at`),
              KEY `is_pinned` (`is_pinned`),
              KEY `is_featured` (`is_featured`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='动态表'",
            
            "CREATE TABLE IF NOT EXISTS `{$prefix}comments` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `post_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '动态ID',
              `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
              `content` varchar(500) NOT NULL DEFAULT '' COMMENT '评论内容',
              `parent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父评论ID，0为一级评论',
              `reply_to_user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复的用户ID',
              `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              PRIMARY KEY (`id`),
              KEY `post_id` (`post_id`),
              KEY `user_id` (`user_id`),
              KEY `parent_id` (`parent_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论表'",
            
            "CREATE TABLE IF NOT EXISTS `{$prefix}likes` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `post_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '动态ID',
              `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              PRIMARY KEY (`id`),
              UNIQUE KEY `post_user` (`post_id`, `user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='点赞表'",
            
            "CREATE TABLE IF NOT EXISTS `{$prefix}follows` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
              `follow_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关注用户ID',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              PRIMARY KEY (`id`),
              UNIQUE KEY `user_follow` (`user_id`, `follow_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='关注表'",
            
            "CREATE TABLE IF NOT EXISTS `{$prefix}plugins` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` varchar(50) NOT NULL DEFAULT '' COMMENT '插件名',
              `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0禁用 1启用',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
              PRIMARY KEY (`id`),
              UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件表'",
            
            "CREATE TABLE IF NOT EXISTS `{$prefix}settings` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `key` varchar(50) NOT NULL DEFAULT '' COMMENT '配置键',
              `value` text COMMENT '配置值',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
              PRIMARY KEY (`id`),
              UNIQUE KEY `key` (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表'",
            
            "CREATE TABLE IF NOT EXISTS `{$prefix}notifications` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '接收用户ID',
              `type` varchar(30) NOT NULL DEFAULT '' COMMENT '通知类型',
              `title` varchar(255) NOT NULL DEFAULT '' COMMENT '通知标题',
              `content` text COMMENT '通知内容',
              `data` text COMMENT '扩展数据JSON',
              `sender_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送者ID',
              `target_type` varchar(30) NOT NULL DEFAULT '' COMMENT '目标类型',
              `target_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '目标ID',
              `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已读：0未读 1已读',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `type` (`type`),
              KEY `is_read` (`is_read`),
              KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息通知表'",
            
            "CREATE TABLE IF NOT EXISTS `{$prefix}version` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `version` varchar(20) NOT NULL DEFAULT '' COMMENT '版本号',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='版本表'",
            
            "CREATE TABLE IF NOT EXISTS `{$prefix}links` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` varchar(100) NOT NULL COMMENT '网站名称',
              `url` varchar(255) NOT NULL COMMENT '网站地址',
              `description` varchar(255) DEFAULT '' COMMENT '网站描述',
              `logo` varchar(255) DEFAULT '' COMMENT '网站LOGO',
              `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
              `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
              `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              KEY `status` (`status`),
              KEY `sort_order` (`sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友情链接表'",
            
            "CREATE TABLE IF NOT EXISTS `{$prefix}topics` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` varchar(100) NOT NULL COMMENT '话题名称',
              `is_pinned` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否人工置顶：0否 1是',
              `is_blocked` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否屏蔽：0否 1是',
              `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序（人工置顶时有效）',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
              `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              UNIQUE KEY `name` (`name`),
              KEY `is_pinned` (`is_pinned`),
              KEY `is_blocked` (`is_blocked`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='话题管理表'",
            
            "CREATE TABLE IF NOT EXISTS `{$prefix}announcements` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `title` varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
              `content` text COMMENT '内容',
              `color` varchar(20) NOT NULL DEFAULT 'blue' COMMENT '颜色：blue/green/yellow/red/purple/cyan',
              `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
              `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
              `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              KEY `status` (`status`),
              KEY `sort_order` (`sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告表'",
            
            "CREATE TABLE IF NOT EXISTS `{$prefix}point_logs` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
              `action` varchar(50) NOT NULL DEFAULT '' COMMENT '动作标识',
              `points` int(11) NOT NULL DEFAULT 0 COMMENT '积分变动',
              `balance` int(11) NOT NULL DEFAULT 0 COMMENT '变动后余额',
              `related_type` varchar(30) NOT NULL DEFAULT '' COMMENT '关联类型',
              `related_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联ID',
              `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `action` (`action`),
              KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分日志表'",
            
            "CREATE TABLE IF NOT EXISTS `{$prefix}point_rules` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `action` varchar(50) NOT NULL DEFAULT '' COMMENT '动作标识',
              `name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则名称',
              `points` int(11) NOT NULL DEFAULT 0 COMMENT '积分变动值',
              `daily_limit` int(11) NOT NULL DEFAULT 0 COMMENT '每日限制次数，0为不限',
              `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
              `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              UNIQUE KEY `action` (`action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分规则表'",
            
            /**
             * 系统日志表
             */
            "CREATE TABLE IF NOT EXISTS `{$prefix}system_logs` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `level` varchar(20) NOT NULL DEFAULT 'info' COMMENT '日志级别',
              `message` text NOT NULL COMMENT '日志消息',
              `context` text COMMENT '上下文数据JSON',
              `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
              `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP地址',
              `user_agent` varchar(500) NOT NULL DEFAULT '' COMMENT '用户代理',
              `request_uri` varchar(500) NOT NULL DEFAULT '' COMMENT '请求URI',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              PRIMARY KEY (`id`),
              KEY `level` (`level`),
              KEY `user_id` (`user_id`),
              KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统日志表'",
            
            /**
             * API Token表
             */
            "CREATE TABLE IF NOT EXISTS `{$prefix}api_tokens` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
              `token` varchar(64) NOT NULL DEFAULT '' COMMENT 'API Token',
              `name` varchar(100) NOT NULL DEFAULT '' COMMENT 'Token名称',
              `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
              `expires_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间',
              `last_used_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后使用时间',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              PRIMARY KEY (`id`),
              UNIQUE KEY `token` (`token`),
              KEY `user_id` (`user_id`),
              KEY `status` (`status`),
              KEY `expires_at` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API Token表'",
            
            /**
             * 队列任务表
             */
            "CREATE TABLE IF NOT EXISTS `{$prefix}queue_jobs` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `queue` varchar(50) NOT NULL DEFAULT 'default' COMMENT '队列名称',
              `job_type` varchar(50) NOT NULL DEFAULT '' COMMENT '任务类型',
              `payload` text COMMENT '任务数据JSON',
              `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0待处理 1处理中 2已完成 3失败',
              `priority` int(11) NOT NULL DEFAULT 0 COMMENT '优先级',
              `retries` int(11) NOT NULL DEFAULT 0 COMMENT '重试次数',
              `max_retries` int(11) NOT NULL DEFAULT 3 COMMENT '最大重试次数',
              `last_error` text COMMENT '最后错误信息',
              `available_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '可用时间',
              `started_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '开始时间',
              `reserved_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '保留时间',
              `finished_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '完成时间',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              PRIMARY KEY (`id`),
              KEY `queue` (`queue`),
              KEY `status` (`status`),
              KEY `priority` (`priority`),
              KEY `available_at` (`available_at`),
              KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='队列任务表'",
        ];

        foreach ($tables as $sql) {
            $pdo->exec($sql);
        }

        $version = APP_VERSION;
        $time = time();
        $pdo->exec("INSERT INTO `{$prefix}version` (`version`, `created_at`) VALUES ('{$version}', {$time})");
        
        $pdo->exec("INSERT INTO `{$prefix}links` (`name`, `url`, `description`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES ('HuSNS', 'https://huyourui.com', 'HuSNS官方网站', 0, 1, {$time}, {$time})");
        
        $defaultPointRules = [
            ['publish_post', '发布动态', 1, 5],
            ['publish_comment', '发表评论', 1, 10],
            ['receive_like', '被点赞', 1, 20],
            ['receive_comment', '被评论', 2, 10],
            ['follow_user', '关注用户', 1, 10],
            ['be_followed', '被关注', 2, 10],
        ];
        foreach ($defaultPointRules as $rule) {
            $pdo->exec("INSERT INTO `{$prefix}point_rules` (`action`, `name`, `points`, `daily_limit`, `status`, `created_at`, `updated_at`) VALUES ('{$rule[0]}', '{$rule[1]}', {$rule[2]}, {$rule[3]}, 1, {$time}, {$time})");
        }
        
        $defaultSettings = [
            ['max_avatar_size', '5'],
        ];
        foreach ($defaultSettings as $setting) {
            $pdo->exec("INSERT INTO `{$prefix}settings` (`key`, `value`, `created_at`, `updated_at`) VALUES ('{$setting[0]}', '{$setting[1]}', {$time}, {$time})");
        }
    }

    private function createAdmin($username, $password, $email, $config)
    {
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $prefix = $config['db_prefix'];
        $hashPassword = Security::hashPassword($password);
        $time = time();

        $stmt = $pdo->prepare("SELECT id FROM `{$prefix}users` WHERE username = ?");
        $stmt->execute([$username]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET `password` = ?, `email` = ?, `updated_at` = ? WHERE `username` = ?");
            $stmt->execute([$hashPassword, $email, $time, $username]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO `{$prefix}users` (`username`, `password`, `email`, `is_admin`, `status`, `created_at`, `updated_at`) VALUES (?, ?, ?, 1, 1, ?, ?)");
            $stmt->execute([$username, $hashPassword, $email, $time, $time]);
        }
    }

    private function createLockFile()
    {
        file_put_contents(ROOT_PATH . 'install.lock', date('Y-m-d H:i:s'));
    }
}

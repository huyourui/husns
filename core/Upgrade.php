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
class Upgrade
{
    private static $versionTable = 'version';
    
    public static function run()
    {
        try {
            $db = Database::getInstance();
            
            if (!self::tableExists($db, 'version')) {
                self::createVersionTable($db);
            }
            
            self::ensureTables($db);
            self::ensureColumns($db);
            
            $currentVersion = self::getCurrentVersion($db);
            
            $upgrades = self::getUpgrades();
            
            foreach ($upgrades as $version => $queries) {
                if (version_compare($version, $currentVersion, '>')) {
                    foreach ($queries as $query) {
                        try {
                            $db->query($query);
                        } catch (Exception $e) {
                        }
                    }
                    self::updateVersion($db, $version);
                }
            }
        } catch (Exception $e) {
        }
    }
    
    private static function ensureColumns($db)
    {
        self::addColumnIfNotExists($db, 'users', 'remember_token', "varchar(64) NOT NULL DEFAULT '' COMMENT '保持登录令牌' AFTER `bio`");
        self::addColumnIfNotExists($db, 'users', 'ban_type', "tinyint(1) NOT NULL DEFAULT 0 COMMENT '限制类型：0正常 1禁言 2封禁' AFTER `status`");
        self::addColumnIfNotExists($db, 'users', 'ban_until', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '限制到期时间，0表示永久' AFTER `ban_type`");
        self::addColumnIfNotExists($db, 'users', 'ban_reason', "varchar(255) NOT NULL DEFAULT '' COMMENT '限制原因' AFTER `ban_until`");
        self::addColumnIfNotExists($db, 'users', 'points', "int(11) NOT NULL DEFAULT 0 COMMENT '积分' AFTER `ban_reason`");
        
        self::addColumnIfNotExists($db, 'posts', 'repost_id', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '转发的原微博ID，0为原创' AFTER `images`");
        self::addColumnIfNotExists($db, 'posts', 'repost_user_id', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '被转发微博的用户ID' AFTER `repost_id`");
        self::addColumnIfNotExists($db, 'posts', 'attachments', "text COMMENT '附件JSON' AFTER `images`");
        self::addColumnIfNotExists($db, 'posts', 'videos', "text COMMENT '视频JSON' AFTER `attachments`");
        self::addColumnIfNotExists($db, 'posts', 'is_pinned', "tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否置顶：0否 1是' AFTER `status`");
        self::addColumnIfNotExists($db, 'posts', 'is_featured', "tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否精华：0否 1是' AFTER `is_pinned`");
        self::addColumnIfNotExists($db, 'posts', 'ip', "varchar(50) NOT NULL DEFAULT '' COMMENT '发布IP' AFTER `status`");
        
        self::addIndexIfNotExists($db, 'posts', 'is_pinned', '`is_pinned`');
        self::addIndexIfNotExists($db, 'posts', 'is_featured', '`is_featured`');
        self::addIndexIfNotExists($db, 'posts', 'repost_id', '`repost_id`');
        
        self::addColumnIfNotExists($db, 'comments', 'parent_id', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父评论ID，0为一级评论' AFTER `content`");
        self::addColumnIfNotExists($db, 'comments', 'reply_to_user_id', "int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复的用户ID' AFTER `parent_id`");
        self::addIndexIfNotExists($db, 'comments', 'parent_id', '`parent_id`');
    }
    
    private static function ensureTables($db)
    {
        $prefix = $db->getPrefix();
        
        $tables = [
            'users' => "CREATE TABLE IF NOT EXISTS `{$prefix}users` (
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
            
            'posts' => "CREATE TABLE IF NOT EXISTS `{$prefix}posts` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
              `content` text NOT NULL COMMENT '内容',
              `images` varchar(2000) NOT NULL DEFAULT '' COMMENT '图片JSON',
              `repost_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '转发的原微博ID，0为原创',
              `repost_user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '被转发微博的用户ID',
              `likes` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
              `comments` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '评论数',
              `reposts` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '转发数',
              `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0删除 1正常',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `repost_id` (`repost_id`),
              KEY `status` (`status`),
              KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='动态表'",
            
            'comments' => "CREATE TABLE IF NOT EXISTS `{$prefix}comments` (
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
            
            'likes' => "CREATE TABLE IF NOT EXISTS `{$prefix}likes` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `post_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '动态ID',
              `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              PRIMARY KEY (`id`),
              UNIQUE KEY `post_user` (`post_id`, `user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='点赞表'",
            
            'follows' => "CREATE TABLE IF NOT EXISTS `{$prefix}follows` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
              `follow_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '关注用户ID',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              PRIMARY KEY (`id`),
              UNIQUE KEY `user_follow` (`user_id`, `follow_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='关注表'",
            
            'plugins' => "CREATE TABLE IF NOT EXISTS `{$prefix}plugins` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` varchar(50) NOT NULL DEFAULT '' COMMENT '插件名',
              `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0禁用 1启用',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
              PRIMARY KEY (`id`),
              UNIQUE KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件表'",
            
            'settings' => "CREATE TABLE IF NOT EXISTS `{$prefix}settings` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `key` varchar(50) NOT NULL DEFAULT '' COMMENT '配置键',
              `value` text COMMENT '配置值',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
              PRIMARY KEY (`id`),
              UNIQUE KEY `key` (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表'",
            
            'notifications' => "CREATE TABLE IF NOT EXISTS `{$prefix}notifications` (
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
            
            'verification_codes' => "CREATE TABLE IF NOT EXISTS `{$prefix}verification_codes` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `email` varchar(100) NOT NULL COMMENT '邮箱',
              `code` varchar(10) NOT NULL COMMENT '验证码',
              `purpose` varchar(50) NOT NULL COMMENT '用途',
              `expire_time` datetime NOT NULL COMMENT '过期时间',
              `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
              PRIMARY KEY (`id`),
              KEY `email` (`email`),
              KEY `purpose` (`purpose`),
              KEY `expire_time` (`expire_time`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮箱验证码表'",
            
            'topics' => "CREATE TABLE IF NOT EXISTS `{$prefix}topics` (
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
            
            'announcements' => "CREATE TABLE IF NOT EXISTS `{$prefix}announcements` (
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
            
            'point_logs' => "CREATE TABLE IF NOT EXISTS `{$prefix}point_logs` (
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
            
            'point_rules' => "CREATE TABLE IF NOT EXISTS `{$prefix}point_rules` (
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
        ];
        
        foreach ($tables as $name => $sql) {
            if (!self::tableExists($db, $name)) {
                try {
                    $db->query($sql);
                } catch (Exception $e) {
                }
            }
        }
    }
    
    private static function tableExists($db, $table)
    {
        $prefix = $db->getPrefix();
        $sql = "SHOW TABLES LIKE '{$prefix}{$table}'";
        $result = $db->fetch($sql);
        return $result !== false;
    }
    
    private static function columnExists($db, $table, $column)
    {
        $prefix = $db->getPrefix();
        $sql = "SHOW COLUMNS FROM `{$prefix}{$table}` LIKE '{$column}'";
        $result = $db->fetch($sql);
        return $result !== false;
    }
    
    private static function addColumnIfNotExists($db, $table, $column, $definition)
    {
        if (!self::columnExists($db, $table, $column)) {
            $prefix = $db->getPrefix();
            $sql = "ALTER TABLE `{$prefix}{$table}` ADD COLUMN `{$column}` {$definition}";
            try {
                $db->query($sql);
            } catch (Exception $e) {
            }
        }
    }
    
    private static function addIndexIfNotExists($db, $table, $indexName, $columns)
    {
        $prefix = $db->getPrefix();
        $sql = "SHOW INDEX FROM `{$prefix}{$table}` WHERE Key_name = '{$indexName}'";
        $result = $db->fetch($sql);
        if (!$result) {
            $sql = "ALTER TABLE `{$prefix}{$table}` ADD INDEX `{$indexName}` ({$columns})";
            try {
                $db->query($sql);
            } catch (Exception $e) {
            }
        }
    }
    
    private static function createVersionTable($db)
    {
        $prefix = $db->getPrefix();
        $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}version` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `version` varchar(20) NOT NULL DEFAULT '' COMMENT '版本号',
            `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='版本表'";
        
        $db->query($sql);
        
        $db->insert('version', [
            'version' => '0.0.0',
            'created_at' => time()
        ]);
    }
    
    private static function getCurrentVersion($db)
    {
        $prefix = $db->getPrefix();
        $sql = "SELECT version FROM `{$prefix}version` ORDER BY id DESC LIMIT 1";
        $result = $db->fetch($sql);
        return $result ? $result['version'] : '0.0.0';
    }
    
    private static function updateVersion($db, $version)
    {
        $db->insert('version', [
            'version' => $version,
            'created_at' => time()
        ]);
    }
    
    private static function getUpgrades()
    {
        $prefix = Database::getInstance()->getPrefix();
        
        return [
            '1.1.0' => [
                "ALTER TABLE `{$prefix}comments` ADD COLUMN `parent_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '父评论ID，0为一级评论' AFTER `content`",
                "ALTER TABLE `{$prefix}comments` ADD COLUMN `reply_to_user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '回复的用户ID' AFTER `parent_id`",
                "ALTER TABLE `{$prefix}comments` ADD INDEX `parent_id` (`parent_id`)",
            ],
            '1.2.0' => [
                "ALTER TABLE `{$prefix}posts` ADD COLUMN `repost_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '转发的原微博ID，0为原创' AFTER `images`",
                "ALTER TABLE `{$prefix}posts` ADD COLUMN `repost_user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '被转发微博的用户ID' AFTER `repost_id`",
                "ALTER TABLE `{$prefix}posts` ADD INDEX `repost_id` (`repost_id`)",
            ],
            '1.3.0' => [
                "ALTER TABLE `{$prefix}users` ADD COLUMN `remember_token` varchar(64) NOT NULL DEFAULT '' COMMENT '保持登录令牌' AFTER `bio`",
            ],
            '1.4.0' => [
                "ALTER TABLE `{$prefix}posts` ADD COLUMN `ip` varchar(50) NOT NULL DEFAULT '' COMMENT '发布IP' AFTER `status`",
            ],
            '1.5.0' => [
                "ALTER TABLE `{$prefix}posts` ADD COLUMN `attachments` text COMMENT '附件JSON' AFTER `images`",
            ],
            '1.6.0' => [
                "ALTER TABLE `{$prefix}users` ADD COLUMN `ban_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '限制类型：0正常 1禁言 2封禁' AFTER `status`",
                "ALTER TABLE `{$prefix}users` ADD COLUMN `ban_until` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '限制到期时间，0表示永久' AFTER `ban_type`",
                "ALTER TABLE `{$prefix}users` ADD COLUMN `ban_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '限制原因' AFTER `ban_until`",
            ],
            '1.7.0' => [
                "CREATE TABLE IF NOT EXISTS `{$prefix}announcements` (
                    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `content` text NOT NULL COMMENT '公告内容',
                    `color` varchar(20) NOT NULL DEFAULT 'blue' COMMENT '背景颜色',
                    `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
                    `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
                    `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
                    `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
                    PRIMARY KEY (`id`),
                    KEY `status` (`status`),
                    KEY `sort_order` (`sort_order`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告表'",
            ],
            '1.8.0' => [
                "ALTER TABLE `{$prefix}users` ADD COLUMN `points` int(11) NOT NULL DEFAULT 0 COMMENT '积分' AFTER `ban_reason`",
                "CREATE TABLE IF NOT EXISTS `{$prefix}point_rules` (
                    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `action` varchar(50) NOT NULL COMMENT '动作标识',
                    `name` varchar(100) NOT NULL COMMENT '动作名称',
                    `points` int(11) NOT NULL DEFAULT 0 COMMENT '积分变化，负数为扣减',
                    `daily_limit` int(11) NOT NULL DEFAULT 0 COMMENT '每日上限，0为不限',
                    `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
                    `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
                    `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `action` (`action`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分规则表'",
                "CREATE TABLE IF NOT EXISTS `{$prefix}point_logs` (
                    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) UNSIGNED NOT NULL COMMENT '用户ID',
                    `action` varchar(50) NOT NULL COMMENT '动作标识',
                    `points` int(11) NOT NULL COMMENT '积分变化',
                    `balance` int(11) NOT NULL COMMENT '变化后余额',
                    `related_type` varchar(50) DEFAULT NULL COMMENT '关联类型',
                    `related_id` int(11) UNSIGNED DEFAULT NULL COMMENT '关联ID',
                    `remark` varchar(255) DEFAULT NULL COMMENT '备注',
                    `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`),
                    KEY `user_id` (`user_id`),
                    KEY `action` (`action`),
                    KEY `created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分日志表'",
                "INSERT INTO `{$prefix}point_rules` (`action`, `name`, `points`, `daily_limit`, `status`, `created_at`, `updated_at`) VALUES
                    ('publish_post', '发布微博', 1, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
                    ('publish_comment', '发表评论', 1, 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
                    ('like_post', '点赞', 1, 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
                    ('repost', '转发微博', 2, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
                    ('follow_user', '关注用户', 1, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
                    ('delete_post', '删除微博', -2, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
                    ('delete_comment', '删除评论', -2, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())",
            ],
            '1.9.0' => [
                "ALTER TABLE `{$prefix}posts` ADD COLUMN `edit_count` int(11) NOT NULL DEFAULT 0 COMMENT '编辑次数' AFTER `reposts`",
                "ALTER TABLE `{$prefix}posts` ADD COLUMN `edited_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后编辑时间' AFTER `edit_count`",
            ],
            '1.10.0' => [
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
            ],
            '1.11.0' => [
                "ALTER TABLE `{$prefix}posts` ADD COLUMN `videos` text COMMENT '视频JSON' AFTER `attachments`",
            ],
            '1.12.0' => [
                "ALTER TABLE `{$prefix}posts` ADD COLUMN `is_pinned` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否置顶：0否 1是' AFTER `status`",
                "ALTER TABLE `{$prefix}posts` ADD INDEX `is_pinned` (`is_pinned`)",
            ],
            '1.13.0' => [
                "ALTER TABLE `{$prefix}posts` ADD COLUMN `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否精华：0否 1是' AFTER `is_pinned`",
                "ALTER TABLE `{$prefix}posts` ADD INDEX `is_featured` (`is_featured`)",
            ],
            '1.14.0' => [
                "CREATE TABLE IF NOT EXISTS `{$prefix}verification_codes` (
                    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `email` varchar(100) NOT NULL COMMENT '邮箱',
                    `code` varchar(10) NOT NULL COMMENT '验证码',
                    `purpose` varchar(50) NOT NULL COMMENT '用途',
                    `expire_time` datetime NOT NULL COMMENT '过期时间',
                    `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
                    PRIMARY KEY (`id`),
                    KEY `email` (`email`),
                    KEY `purpose` (`purpose`),
                    KEY `expire_time` (`expire_time`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮箱验证码表'",
            ],
            '1.15.0' => [
                "ALTER TABLE `{$prefix}posts` ADD COLUMN `is_pinned` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否置顶：0否 1是' AFTER `status`",
                "ALTER TABLE `{$prefix}posts` ADD INDEX `is_pinned` (`is_pinned`)",
                "ALTER TABLE `{$prefix}posts` ADD COLUMN `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否精华：0否 1是' AFTER `is_pinned`",
                "ALTER TABLE `{$prefix}posts` ADD INDEX `is_featured` (`is_featured`)",
            ],
            '1.16.0' => [
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
            ],
            '1.17.0' => [
                "INSERT IGNORE INTO `{$prefix}settings` (`key`, `value`, `created_at`, `updated_at`) VALUES ('max_avatar_size', '5', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())",
            ],
            '1.18.0' => [
                "CREATE TABLE IF NOT EXISTS `{$prefix}invite_codes` (
                    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `code` varchar(32) NOT NULL COMMENT '邀请码',
                    `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0未使用 1已使用',
                    `used_by` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用者用户ID',
                    `used_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '使用时间',
                    `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `code` (`code`),
                    KEY `status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邀请码表'",
                "INSERT IGNORE INTO `{$prefix}settings` (`key`, `value`, `created_at`, `updated_at`) VALUES ('require_invite_code', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())",
            ],
            '1.19.0' => [
                "ALTER TABLE `{$prefix}comments` ADD COLUMN `ip` varchar(50) NOT NULL DEFAULT '' COMMENT '评论IP' AFTER `status`",
            ],
            '1.20.0' => [
                "CREATE TABLE IF NOT EXISTS `{$prefix}favorites` (
                    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `post_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '动态ID',
                    `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
                    `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `post_user` (`post_id`, `user_id`),
                    KEY `user_id` (`user_id`),
                    KEY `created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收藏表'",
            ],
            /**
             * 版本 2.1.2 - 添加系统日志表
             */
            '2.1.2' => [
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
            ],
        ];
    }
}

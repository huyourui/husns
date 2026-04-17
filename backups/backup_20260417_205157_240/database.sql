-- HuSNS Database Backup
-- Version: 2.4.0
-- Date: 2026-04-17 20:51:57

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Table: husns_announcements
DROP TABLE IF EXISTS `husns_announcements`;
CREATE TABLE `husns_announcements` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
  `content` text DEFAULT NULL COMMENT '内容',
  `color` varchar(20) NOT NULL DEFAULT 'blue' COMMENT '颜色：blue/green/yellow/red/purple/cyan',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告表';

-- Table: husns_comments
DROP TABLE IF EXISTS `husns_comments`;
CREATE TABLE `husns_comments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '动态ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '评论内容',
  `parent_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '父评论ID，0为一级评论',
  `reply_to_user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '回复的用户ID',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT '评论IP',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论表';

INSERT INTO `husns_comments` VALUES ('1', '4', '1', '那我应该回复哪一条呢？', '0', '0', '1', '127.0.0.1', '1775571562');

-- Table: husns_favorites
DROP TABLE IF EXISTS `husns_favorites`;
CREATE TABLE `husns_favorites` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '动态ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_user` (`post_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收藏表';

INSERT INTO `husns_favorites` VALUES ('7', '109', '1', '1774146172');
INSERT INTO `husns_favorites` VALUES ('8', '112', '1', '1774146469');
INSERT INTO `husns_favorites` VALUES ('10', '111', '2', '1774146600');

-- Table: husns_follows
DROP TABLE IF EXISTS `husns_follows`;
CREATE TABLE `husns_follows` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `follow_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关注用户ID',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_follow` (`user_id`,`follow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='关注表';

-- Table: husns_invite_codes
DROP TABLE IF EXISTS `husns_invite_codes`;
CREATE TABLE `husns_invite_codes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL COMMENT '邀请码',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0未使用 1已使用',
  `used_by` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '使用者用户ID',
  `used_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '使用时间',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邀请码表';

INSERT INTO `husns_invite_codes` VALUES ('11', 'SAC3STY3PF856R8V', '1', '3', '1773584244', '1773584204');

-- Table: husns_likes
DROP TABLE IF EXISTS `husns_likes`;
CREATE TABLE `husns_likes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '动态ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_user` (`post_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='点赞表';

-- Table: husns_links
DROP TABLE IF EXISTS `husns_links`;
CREATE TABLE `husns_links` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '网站名称',
  `url` varchar(255) NOT NULL COMMENT '网站地址',
  `description` varchar(255) DEFAULT '' COMMENT '网站描述',
  `logo` varchar(255) DEFAULT '' COMMENT '网站LOGO',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='友情链接表';

INSERT INTO `husns_links` VALUES ('1', 'HuSNS', 'https://huyourui.com', 'HuSNS官方网站', '', '0', '1', '1773320126', '1773320126');
INSERT INTO `husns_links` VALUES ('4', 'HuSNS', 'https://huyourui.com', 'HuSNS官方网站', '', '0', '1', '1775569825', '1775569825');

-- Table: husns_notifications
DROP TABLE IF EXISTS `husns_notifications`;
CREATE TABLE `husns_notifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '接收用户ID',
  `type` varchar(30) NOT NULL DEFAULT '' COMMENT '通知类型',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '通知标题',
  `content` text DEFAULT NULL COMMENT '通知内容',
  `data` text DEFAULT NULL COMMENT '扩展数据JSON',
  `sender_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '发送者ID',
  `target_type` varchar(30) NOT NULL DEFAULT '' COMMENT '目标类型',
  `target_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '目标ID',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已读：0未读 1已读',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='消息通知表';

-- Table: husns_plugins
DROP TABLE IF EXISTS `husns_plugins`;
CREATE TABLE `husns_plugins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '插件名',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0禁用 1启用',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='插件表';

-- Table: husns_point_logs
DROP TABLE IF EXISTS `husns_point_logs`;
CREATE TABLE `husns_point_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `action` varchar(50) NOT NULL DEFAULT '' COMMENT '动作标识',
  `points` int(11) NOT NULL DEFAULT 0 COMMENT '积分变动',
  `balance` int(11) NOT NULL DEFAULT 0 COMMENT '变动后余额',
  `related_type` varchar(30) NOT NULL DEFAULT '' COMMENT '关联类型',
  `related_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联ID',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分日志表';

INSERT INTO `husns_point_logs` VALUES ('1', '1', 'publish_post', '1', '1', 'post', '1', '发布动态', '1775569842');
INSERT INTO `husns_point_logs` VALUES ('2', '1', 'publish_post', '1', '2', 'post', '2', '发布动态', '1775571532');
INSERT INTO `husns_point_logs` VALUES ('3', '1', 'publish_post', '1', '3', 'post', '3', '发布动态', '1775571538');
INSERT INTO `husns_point_logs` VALUES ('4', '1', 'publish_comment', '1', '4', 'comment', '1', '发表评论', '1775571562');
INSERT INTO `husns_point_logs` VALUES ('5', '1', 'publish_post', '1', '5', 'post', '5', '发布动态', '1775571572');
INSERT INTO `husns_point_logs` VALUES ('6', '1', 'publish_post', '1', '6', 'post', '6', '发布动态', '1775571691');
INSERT INTO `husns_point_logs` VALUES ('7', '1', 'publish_post', '1', '7', 'post', '11', '发布动态', '1775710489');
INSERT INTO `husns_point_logs` VALUES ('8', '1', 'publish_post', '1', '8', 'post', '12', '发布动态', '1775711282');
INSERT INTO `husns_point_logs` VALUES ('9', '1', 'publish_post', '1', '9', 'post', '13', '发布动态', '1775713391');
INSERT INTO `husns_point_logs` VALUES ('10', '1', 'publish_post', '1', '10', 'post', '14', '发布动态', '1775713426');
INSERT INTO `husns_point_logs` VALUES ('11', '1', 'publish_post', '1', '11', 'post', '15', '发布动态', '1775714087');
INSERT INTO `husns_point_logs` VALUES ('12', '1', 'publish_post', '1', '12', 'post', '17', '发布动态', '1775792213');
INSERT INTO `husns_point_logs` VALUES ('13', '1', 'publish_post', '1', '13', 'post', '18', '发布动态', '1775792231');
INSERT INTO `husns_point_logs` VALUES ('14', '1', 'publish_post', '1', '14', 'post', '19', '发布动态', '1775792295');
INSERT INTO `husns_point_logs` VALUES ('15', '1', 'publish_post', '1', '15', 'post', '20', '发布动态', '1775792719');
INSERT INTO `husns_point_logs` VALUES ('16', '1', 'publish_post', '1', '16', 'post', '21', '发布动态', '1775792755');
INSERT INTO `husns_point_logs` VALUES ('17', '1', 'publish_post', '1', '17', 'post', '27', '发布动态', '1776056294');
INSERT INTO `husns_point_logs` VALUES ('18', '1', 'publish_post', '1', '18', 'post', '28', '发布动态', '1776343632');
INSERT INTO `husns_point_logs` VALUES ('19', '1', 'publish_post', '1', '19', 'post', '29', '发布动态', '1776427802');
INSERT INTO `husns_point_logs` VALUES ('20', '1', 'publish_post', '1', '20', 'post', '30', '发布动态', '1776427805');
INSERT INTO `husns_point_logs` VALUES ('21', '1', 'publish_post', '1', '21', 'post', '31', '发布动态', '1776427807');
INSERT INTO `husns_point_logs` VALUES ('22', '1', 'publish_post', '1', '22', 'post', '32', '发布动态', '1776427810');
INSERT INTO `husns_point_logs` VALUES ('23', '1', 'publish_post', '1', '23', 'post', '33', '发布动态', '1776427812');

-- Table: husns_point_rules
DROP TABLE IF EXISTS `husns_point_rules`;
CREATE TABLE `husns_point_rules` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(50) NOT NULL DEFAULT '' COMMENT '动作标识',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则名称',
  `points` int(11) NOT NULL DEFAULT 0 COMMENT '积分变动值',
  `daily_limit` int(11) NOT NULL DEFAULT 0 COMMENT '每日限制次数，0为不限',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1启用',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `action` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='积分规则表';

INSERT INTO `husns_point_rules` VALUES ('1', 'publish_post', '发布动态', '1', '5', '1', '1775569825', '1775569825');
INSERT INTO `husns_point_rules` VALUES ('2', 'publish_comment', '发表评论', '1', '10', '1', '1775569825', '1775569825');
INSERT INTO `husns_point_rules` VALUES ('3', 'receive_like', '被点赞', '1', '20', '1', '1775569825', '1775569825');
INSERT INTO `husns_point_rules` VALUES ('4', 'receive_comment', '被评论', '2', '10', '1', '1775569825', '1775569825');
INSERT INTO `husns_point_rules` VALUES ('5', 'follow_user', '关注用户', '1', '10', '1', '1775569825', '1775569825');
INSERT INTO `husns_point_rules` VALUES ('6', 'be_followed', '被关注', '2', '10', '1', '1775569825', '1775569825');

-- Table: husns_posts
DROP TABLE IF EXISTS `husns_posts`;
CREATE TABLE `husns_posts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `content` text NOT NULL COMMENT '内容',
  `images` varchar(2000) NOT NULL DEFAULT '' COMMENT '图片JSON',
  `attachments` text DEFAULT NULL COMMENT '附件JSON',
  `videos` text DEFAULT NULL COMMENT '视频JSON',
  `repost_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '转发的原微博ID，0为原创',
  `repost_user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '被转发微博的用户ID',
  `likes` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '点赞数',
  `comments` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '评论数',
  `reposts` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '转发数',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0删除 1正常',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否置顶：0否 1是',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否精华：0否 1是',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT '发布IP',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  KEY `is_pinned` (`is_pinned`),
  KEY `is_featured` (`is_featured`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='动态表';

INSERT INTO `husns_posts` VALUES ('1', '1', '哈哈哈', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775569842', '1775569842');
INSERT INTO `husns_posts` VALUES ('2', '1', '这个隐藏功能优点意思呀', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775571532', '1775571532');
INSERT INTO `husns_posts` VALUES ('3', '1', '[hide]测试隐藏内容呀，哈哈哈哈[/hide]', '[]', '[]', '[]', '0', '0', '0', '0', '1', '1', '0', '0', '127.0.0.1', '1775571538', '1775571538');
INSERT INTO `husns_posts` VALUES ('4', '1', '不会出现吧', '', NULL, NULL, '3', '1', '0', '1', '0', '1', '0', '0', '127.0.0.1', '1775571548', '1775571548');
INSERT INTO `husns_posts` VALUES ('5', '1', '纯粹就是为了什么呀', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775571572', '1775571572');
INSERT INTO `husns_posts` VALUES ('6', '1', '你知道什么呀', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775571691', '1775571691');
INSERT INTO `husns_posts` VALUES ('7', '1', '哈哈但是看韩剧对方撒娇很快乐但是发空间', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775571694', '1775571694');
INSERT INTO `husns_posts` VALUES ('8', '1', '还很大发空间和地方撒看韩剧对方撒 很可以呀', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775571702', '1775571702');
INSERT INTO `husns_posts` VALUES ('9', '1', '安全是最重要的', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775571844', '1775571844');
INSERT INTO `husns_posts` VALUES ('10', '1', '啊立法艰苦奋斗撒娇哭啦发 就是最重要的呀', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775571849', '1775571849');
INSERT INTO `husns_posts` VALUES ('11', '1', '图片发布', '[\"images\\/20260409\\/69d73119d28fb.jpeg\",\"images\\/20260409\\/69d73119d2a2a.png\"]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775710489', '1775710489');
INSERT INTO `husns_posts` VALUES ('12', '1', '优化图片 哈哈哈哈啊哈', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775711282', '1775711282');
INSERT INTO `husns_posts` VALUES ('13', '1', '多图上传。', '[\"images\\/20260409\\/69d73c6f69cc2.jpeg\",\"images\\/20260409\\/69d73c6f69db9.jpeg\",\"images\\/20260409\\/69d73c6f69e0b.jpeg\",\"images\\/20260409\\/69d73c6f69e4f.jpeg\"]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775713391', '1775713391');
INSERT INTO `husns_posts` VALUES ('14', '1', '哈哈哈哈哈', '[\"images\\/20260409\\/69d73c923bb66.png\",\"images\\/20260409\\/69d73c923bc60.jpeg\"]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775713426', '1775713426');
INSERT INTO `husns_posts` VALUES ('15', '1', '修复了多图预览功能。', '[\"images\\/20260409\\/69d73f2770003.jpeg\",\"images\\/20260409\\/69d73f2770105.jpeg\"]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775714087', '1775714087');
INSERT INTO `husns_posts` VALUES ('16', '1', '继续测试', '[\"images\\/20260409\\/69d73f33484f1.jpeg\",\"images\\/20260409\\/69d73f33485ee.jpeg\",\"images\\/20260409\\/69d73f334863f.jpeg\"]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775714099', '1775714099');
INSERT INTO `husns_posts` VALUES ('17', '1', '测试一下 哈哈哈哈哈', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775792213', '1775792213');
INSERT INTO `husns_posts` VALUES ('18', '1', '4张的图片排版', '[\"images\\/20260410\\/69d870670bbf4.jpeg\",\"images\\/20260410\\/69d870670bf04.jpeg\",\"images\\/20260410\\/69d870670bf71.jpeg\",\"images\\/20260410\\/69d870670bfdd.jpeg\"]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775792231', '1775792231');
INSERT INTO `husns_posts` VALUES ('19', '1', '5张图片的排版', '[\"images\\/20260410\\/69d870a7c048c.jpeg\",\"images\\/20260410\\/69d870a7c05ce.jpeg\",\"images\\/20260410\\/69d870a7c0624.jpeg\",\"images\\/20260410\\/69d870a7c067e.jpeg\",\"images\\/20260410\\/69d870a7c06c2.jpeg\"]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775792295', '1775792295');
INSERT INTO `husns_posts` VALUES ('20', '1', 'ceshi', '[\"images\\/20260410\\/69d8724f2fb64.jpeg\",\"images\\/20260410\\/69d8724f2fc40.jpeg\",\"images\\/20260410\\/69d8724f2fc83.jpeg\",\"images\\/20260410\\/69d8724f2fcc0.jpeg\",\"images\\/20260410\\/69d8724f2fcfe.jpeg\",\"images\\/20260410\\/69d8724f2fd34.jpeg\",\"images\\/20260410\\/69d8724f2fd6b.jpeg\",\"images\\/20260410\\/69d8724f2fda4.jpeg\"]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775792719', '1775792719');
INSERT INTO `husns_posts` VALUES ('21', '1', 'jixu', '[\"images\\/20260410\\/69d87273775bd.jpeg\",\"images\\/20260410\\/69d87273776b7.jpeg\",\"images\\/20260410\\/69d8727377705.jpeg\",\"images\\/20260410\\/69d8727377747.jpeg\",\"images\\/20260410\\/69d8727377788.jpeg\",\"images\\/20260410\\/69d87273777c7.jpeg\",\"images\\/20260410\\/69d8727377808.jpeg\"]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775792755', '1775792755');
INSERT INTO `husns_posts` VALUES ('22', '1', '很有趣', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775792849', '1775792849');
INSERT INTO `husns_posts` VALUES ('23', '1', '哈哈哈哈哈哈', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775792852', '1775792852');
INSERT INTO `husns_posts` VALUES ('24', '1', '爱死你了', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775792854', '1775792854');
INSERT INTO `husns_posts` VALUES ('25', '1', '哈哈哈哈领导开发深刻酸辣粉', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775792856', '1775792856');
INSERT INTO `husns_posts` VALUES ('26', '1', '[hide]在此输入隐藏内容[/hide]', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1775792867', '1775792867');
INSERT INTO `husns_posts` VALUES ('27', '1', '我爱你，哈哈', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1776056294', '1776056294');
INSERT INTO `husns_posts` VALUES ('28', '1', '大升级 哈哈哈哈', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1776343632', '1776343632');
INSERT INTO `husns_posts` VALUES ('29', '1', '#话题#测试', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1776427802', '1776427802');
INSERT INTO `husns_posts` VALUES ('30', '1', '#话题# 继续测试', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1776427805', '1776427805');
INSERT INTO `husns_posts` VALUES ('31', '1', '#话题# 哈哈哈哈', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1776427807', '1776427807');
INSERT INTO `husns_posts` VALUES ('32', '1', '#话题1# 激发了卡拉斯地方', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1776427810', '1776427810');
INSERT INTO `husns_posts` VALUES ('33', '1', '#话题1# 立法发开链接', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1776427812', '1776427812');
INSERT INTO `husns_posts` VALUES ('34', '1', '#话题1# 链接进来就开始掉发了几开发', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1776427813', '1776427813');
INSERT INTO `husns_posts` VALUES ('35', '1', '#话题2#艰苦奋斗撒健康', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1776427818', '1776427818');
INSERT INTO `husns_posts` VALUES ('36', '1', '#话题2# 经济法经济法', '[]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1776427820', '1776427820');
INSERT INTO `husns_posts` VALUES ('37', '1', '测试图片轮播。', '[\"images\\/20260417\\/69e225ce75be6.jpeg\",\"images\\/20260417\\/69e225ce75eb0.png\"]', '[]', '[]', '0', '0', '0', '0', '0', '1', '0', '0', '127.0.0.1', '1776428494', '1776428494');

-- Table: husns_settings
DROP TABLE IF EXISTS `husns_settings`;
CREATE TABLE `husns_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(50) NOT NULL DEFAULT '' COMMENT '配置键',
  `value` text DEFAULT NULL COMMENT '配置值',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

INSERT INTO `husns_settings` VALUES ('1', 'max_avatar_size', '5', '1775569825', '1776427956');
INSERT INTO `husns_settings` VALUES ('3', 'require_invite_code', '0', '1775569826', '1776427956');
INSERT INTO `husns_settings` VALUES ('4', 'site_name', 'HuSNS', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('5', 'site_subtitle', '一款免费开源的社交平台', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('6', 'site_keywords', '', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('7', 'site_description', '', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('8', 'posts_per_page', '20', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('9', 'max_post_length', '500', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('10', 'publish_placeholder', '', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('11', 'max_comment_length', '500', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('12', 'hide_tag_admin_only', '0', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('13', 'hot_topics_enabled', '1', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('14', 'hot_threshold', '20', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('15', 'registration_open', '1', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('16', 'allowed_email_suffixes', '', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('17', 'max_attachment_size', '10', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('18', 'allowed_attachment_extensions', 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('19', 'max_attachment_count', '5', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('20', 'max_image_size', '5', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('21', 'max_image_count', '9', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('22', 'guest_download_allowed', '0', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('23', 'mention_suggest_scope', 'all', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('24', 'max_video_size', '100', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('25', 'max_video_count', '1', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('26', 'point_name', '积分', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('27', 'banned_usernames', '', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('28', 'action_interval', '0', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('29', 'default_all_posts_threshold', '100', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('30', 'icp_number', '', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('31', 'icp_url', 'https://beian.miit.gov.cn/', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('32', 'guest_access_allowed', '0', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('33', 'registration_email_verify', '0', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('34', 'mail_enabled', '0', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('35', 'mail_host', '', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('36', 'mail_port', '465', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('37', 'mail_encryption', 'ssl', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('38', 'mail_username', '', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('39', 'mail_password', '', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('40', 'mail_from_name', '', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('41', 'mail_from_address', '', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('42', 'username_min_length', '2', '1776427956', '1776427956');
INSERT INTO `husns_settings` VALUES ('43', 'username_max_length', '20', '1776427956', '1776427956');

-- Table: husns_system_logs
DROP TABLE IF EXISTS `husns_system_logs`;
CREATE TABLE `husns_system_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `level` varchar(20) NOT NULL DEFAULT 'info' COMMENT '日志级别',
  `message` text NOT NULL COMMENT '日志消息',
  `context` text DEFAULT NULL COMMENT '上下文数据JSON',
  `user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP地址',
  `user_agent` varchar(500) NOT NULL DEFAULT '' COMMENT '用户代理',
  `request_uri` varchar(500) NOT NULL DEFAULT '' COMMENT '请求URI',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `level` (`level`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统日志表';

-- Table: husns_topics
DROP TABLE IF EXISTS `husns_topics`;
CREATE TABLE `husns_topics` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '话题名称',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否人工置顶：0否 1是',
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否屏蔽：0否 1是',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '排序（人工置顶时有效）',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0,
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `is_pinned` (`is_pinned`),
  KEY `is_blocked` (`is_blocked`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='话题管理表';

INSERT INTO `husns_topics` VALUES ('1', '话题2', '0', '0', '0', '1773582375', '1773583059');
INSERT INTO `husns_topics` VALUES ('2', '话题', '0', '0', '0', '1773582394', '1773583061');
INSERT INTO `husns_topics` VALUES ('3', '测试话题', '0', '0', '0', '1773582490', '1773582684');
INSERT INTO `husns_topics` VALUES ('4', '话题3', '0', '0', '0', '1773582784', '1773582786');

-- Table: husns_users
DROP TABLE IF EXISTS `husns_users`;
CREATE TABLE `husns_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `bio` varchar(255) NOT NULL DEFAULT '' COMMENT '个人简介',
  `remember_token` varchar(64) NOT NULL DEFAULT '' COMMENT '保持登录令牌',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否管理员',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `ban_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '限制类型：0正常 1禁言 2封禁',
  `ban_until` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '限制到期时间，0表示永久',
  `ban_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '限制原因',
  `points` int(11) NOT NULL DEFAULT 0 COMMENT '积分',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '注册时间',
  `updated_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

INSERT INTO `husns_users` VALUES ('1', 'admin', '$2y$12$sRssu1iKoNtQtjyygjWJ0.d.8q3hsA28YvlFA.Xd68zJ/4FKhU/cS', 'admin@qq.com', 'avatars/user_1.jpeg', '', '', '1', '1', '0', '0', '', '23', '1775569825', '1776427812');

-- Table: husns_verification_codes
DROP TABLE IF EXISTS `husns_verification_codes`;
CREATE TABLE `husns_verification_codes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `code` varchar(10) NOT NULL COMMENT '验证码',
  `purpose` varchar(50) NOT NULL COMMENT '用途',
  `expire_time` datetime NOT NULL COMMENT '过期时间',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `purpose` (`purpose`),
  KEY `expire_time` (`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邮箱验证码表';

-- Table: husns_version
DROP TABLE IF EXISTS `husns_version`;
CREATE TABLE `husns_version` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(20) NOT NULL DEFAULT '' COMMENT '版本号',
  `created_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='版本表';

INSERT INTO `husns_version` VALUES ('1', '1.16.0', '1775569825');
INSERT INTO `husns_version` VALUES ('2', '1.17.0', '1775569826');
INSERT INTO `husns_version` VALUES ('3', '1.18.0', '1775569826');
INSERT INTO `husns_version` VALUES ('4', '1.19.0', '1775569826');
INSERT INTO `husns_version` VALUES ('5', '1.20.0', '1775569826');
INSERT INTO `husns_version` VALUES ('6', '2.1.2', '1775569826');

SET FOREIGN_KEY_CHECKS = 1;

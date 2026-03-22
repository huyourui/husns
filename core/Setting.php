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
class Setting
{
    private static $settings = null;

    public static function get($key, $default = '')
    {
        if (self::$settings === null) {
            self::load();
        }
        
        return isset(self::$settings[$key]) ? self::$settings[$key] : $default;
    }

    public static function all()
    {
        if (self::$settings === null) {
            self::load();
        }
        
        return self::$settings;
    }

    private static function load()
    {
        self::$settings = [];
        
        if (!file_exists(ROOT_PATH . 'install.lock')) {
            return;
        }
        
        try {
            $db = Database::getInstance();
            $rows = $db->fetchAll("SELECT `key`, `value` FROM __PREFIX__settings");
            
            foreach ($rows as $row) {
                self::$settings[$row['key']] = $row['value'];
            }
        } catch (Exception $e) {
        }
    }

    public static function getSiteName()
    {
        return self::get('site_name', 'HuSNS');
    }

    public static function getSubtitle()
    {
        return self::get('site_subtitle', '一款免费开源的社交平台');
    }

    public static function getKeywords()
    {
        return self::get('site_keywords', '');
    }

    public static function getDescription()
    {
        return self::get('site_description', '');
    }

    public static function getPostsPerPage()
    {
        $value = (int)self::get('posts_per_page', 20);
        return max(5, min(50, $value));
    }

    public static function getMaxPostLength()
    {
        $value = (int)self::get('max_post_length', 500);
        return max(50, min(5000, $value));
    }

    public static function getPublishPlaceholder()
    {
        return self::get('publish_placeholder', '');
    }

    public static function getMaxCommentLength()
    {
        $value = (int)self::get('max_comment_length', 500);
        return max(50, min(2000, $value));
    }

    public static function getMaxImageSize()
    {
        $value = (int)self::get('max_image_size', 5);
        return max(1, min(20, $value));
    }

    public static function getMaxAvatarSize()
    {
        $value = (int)self::get('max_avatar_size', 5);
        return max(1, min(20, $value));
    }

    public static function getPointName()
    {
        return self::get('point_name', '积分');
    }

    public static function getBannedUsernames()
    {
        $banned = self::get('banned_usernames', '');
        if (empty($banned)) {
            return [];
        }
        return array_map('trim', array_filter(explode(',', $banned)));
    }

    public static function getActionInterval()
    {
        $interval = (int)self::get('action_interval', 0);
        return max(0, min(60, $interval));
    }

    public static function isRegistrationOpen()
    {
        return (int)self::get('registration_open', 1) === 1;
    }

    public static function getAllowedEmailSuffixes()
    {
        $suffixes = self::get('allowed_email_suffixes', '');
        if (empty($suffixes)) {
            return [];
        }
        return array_map('trim', array_filter(explode(',', $suffixes)));
    }

    public static function getTitle($page = '')
    {
        $siteName = self::getSiteName();
        $subtitle = self::getSubtitle();
        
        if ($page) {
            return $page . ' - ' . $siteName;
        }
        
        if ($subtitle) {
            return $siteName . ' - ' . $subtitle;
        }
        
        return $siteName;
    }

    public static function getMaxAttachmentSize()
    {
        $value = (int)self::get('max_attachment_size', 10);
        return max(1, min(100, $value));
    }

    public static function getAllowedAttachmentExtensions()
    {
        $extensions = self::get('allowed_attachment_extensions', 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar');
        if (empty($extensions)) {
            return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
        }
        return array_map('trim', array_filter(explode(',', $extensions)));
    }

    public static function getMaxAttachmentCount()
    {
        $value = (int)self::get('max_attachment_count', 5);
        return max(1, min(10, $value));
    }

    public static function getMaxImageCount()
    {
        $value = (int)self::get('max_image_count', 9);
        return max(1, min(18, $value));
    }

    public static function isGuestDownloadAllowed()
    {
        return (int)self::get('guest_download_allowed', 1) === 1;
    }

    public static function getMentionSuggestScope()
    {
        $scope = self::get('mention_suggest_scope', 'all');
        return in_array($scope, ['all', 'following']) ? $scope : 'all';
    }

    public static function getMaxVideoSize()
    {
        $value = (int)self::get('max_video_size', 100);
        return max(1, min(500, $value));
    }

    public static function getMaxVideoCount()
    {
        $value = (int)self::get('max_video_count', 1);
        return max(1, min(5, $value));
    }

    public static function getIcpNumber()
    {
        return trim(self::get('icp_number', ''));
    }

    public static function getIcpUrl()
    {
        $url = trim(self::get('icp_url', 'https://beian.miit.gov.cn/'));
        return $url ?: 'https://beian.miit.gov.cn/';
    }

    public static function getDefaultAllPostsThreshold()
    {
        $value = (int)self::get('default_all_posts_threshold', 100);
        return max(0, min(10000, $value));
    }

    public static function isGuestAccessAllowed()
    {
        return (int)self::get('guest_access_allowed', 0) === 1;
    }

    public static function isRegistrationEmailVerifyEnabled()
    {
        return (int)self::get('registration_email_verify', 0) === 1;
    }

    public static function getUsernameMinLength()
    {
        $value = (int)self::get('username_min_length', 2);
        return max(1, min(20, $value));
    }

    public static function getUsernameMaxLength()
    {
        $value = (int)self::get('username_max_length', 20);
        return max(1, min(50, $value));
    }

    public static function isHideTagAdminOnly()
    {
        return (int)self::get('hide_tag_admin_only', 0) === 1;
    }

    public static function isHotTopicsEnabled()
    {
        return (int)self::get('hot_topics_enabled', 1) === 1;
    }
}

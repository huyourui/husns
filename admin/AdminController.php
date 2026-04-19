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
class AdminController extends Controller
{
    private $userModel;
    private $postModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->postModel = new PostModel();
        $this->view->setLayout('admin');
    }

    public function index()
    {
        $this->checkAdmin();
        
        $topicCount = $this->getTopicCount();
        
        $stats = [
            'users' => $this->db->count('users'),
            'posts' => $this->db->count('posts', 'status = 1'),
            'comments' => $this->db->count('comments', 'status = 1'),
            'topics' => $topicCount,
            'today_users' => $this->db->count('users', 'created_at > ?', [strtotime('today')]),
            'today_posts' => $this->db->count('posts', 'status = 1 AND created_at > ?', [strtotime('today')])
        ];

        $serverInfo = [
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->getMysqlVersion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'os' => PHP_OS,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . '秒',
            'disk_free_space' => $this->formatBytes(disk_free_space(ROOT_PATH)),
            'php_extensions' => implode(', ', ['gd' => 'GD', 'pdo' => 'PDO', 'pdo_mysql' => 'PDO MySQL', 'mbstring' => 'Mbstring', 'curl' => 'cURL', 'json' => 'JSON', 'zip' => 'Zip']),
        ];
        
        $versionInfo = $this->checkLatestVersion();

        $this->render('admin/index', [
            'stats' => $stats,
            'serverInfo' => $serverInfo,
            'versionInfo' => $versionInfo
        ]);
    }
    
    /**
     * 检查最新版本
     * 
     * @return array 版本信息
     */
    private function checkLatestVersion()
    {
        $cacheFile = ROOT_PATH . 'temp' . DIRECTORY_SEPARATOR . 'version_check.json';
        $cacheTime = 3600;
        
        // 如果缓存文件存在且未过期，直接返回缓存
        if (file_exists($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true);
            if ($cache && isset($cache['timestamp']) && (time() - $cache['timestamp']) < $cacheTime) {
                return $cache;
            }
        }
        
        $versionInfo = [
            'current' => APP_VERSION,
            'latest' => null,
            'has_update' => false,
            'release_url' => null,
            'timestamp' => time()
        ];
        
        // 检查 curl 扩展是否可用
        if (!function_exists('curl_init')) {
            return $versionInfo;
        }
        
        try {
            $ch = curl_init();
            if ($ch === false) {
                return $versionInfo;
            }
            
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://gitee.com/api/v5/repos/youruihu/husns/releases/latest',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'HuSNS/' . APP_VERSION
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $release = json_decode($response, true);
                
                if ($release && isset($release['tag_name'])) {
                    $latestVersion = ltrim($release['tag_name'], 'v');
                    $versionInfo['latest'] = $latestVersion;
                    $versionInfo['has_update'] = version_compare($latestVersion, APP_VERSION, '>');
                    $versionInfo['release_url'] = $release['html_url'] ?? null;
                    $versionInfo['release_notes'] = $release['body'] ?? '';
                }
            }
        } catch (Exception $e) {
            // 忽略异常，返回默认版本信息
        }
        
        // 尝试写入缓存文件，失败不影响功能
        try {
            $cacheDir = dirname($cacheFile);
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            if (is_dir($cacheDir) && is_writable($cacheDir)) {
                @file_put_contents($cacheFile, json_encode($versionInfo));
            }
        } catch (Exception $e) {
            // 缓存写入失败不影响功能
        }
        
        return $versionInfo;
    }

    private function getMysqlVersion()
    {
        try {
            $result = $this->db->fetch("SELECT VERSION() as version");
            return $result['version'] ?? 'Unknown';
        } catch (Exception $e) {
            return 'Unknown';
        }
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function getTopicCount()
    {
        $sql = "SELECT content FROM __PREFIX__posts WHERE status = 1";
        $posts = $this->db->fetchAll($sql);
        
        $topics = [];
        foreach ($posts as $post) {
            preg_match_all('/#([^\s#]+)/u', $post['content'], $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $topic) {
                    $topic = trim($topic);
                    if (!empty($topic)) {
                        $topics[$topic] = ($topics[$topic] ?? 0) + 1;
                    }
                }
            }
        }
        
        return count($topics);
    }

    public function verify()
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            $this->redirect(Helper::url());
        }
        
        $authKey = 'admin_auth_time_' . $_SESSION['user_id'];
        $authExpire = 1800;
        
        if (isset($_SESSION[$authKey]) && (time() - $_SESSION[$authKey]) < $authExpire) {
            $_SESSION[$authKey] = time();
            $this->redirect(Helper::url('admin'));
        }
        
        if ($this->isPost()) {
            $password = Helper::post('password');
            
            $user = $this->userModel->find($_SESSION['user_id']);
            
            if (!$user || !Security::verifyPassword($password, $user['password'])) {
                $this->setFlash('error', '密码错误');
                $this->redirect(Helper::url('admin/verify'));
            }
            
            $_SESSION[$authKey] = time();
            $this->redirect(Helper::url('admin'));
        }
        
        $this->view->setLayout(null);
        $this->render('admin/verify');
    }

    public function users()
    {
        $this->checkAdmin();
        
        $page = (int)Helper::get('page', 1);
        $keyword = Helper::get('keyword', '');
        
        $where = '1';
        $params = [];
        
        if ($keyword) {
            $where = '(username LIKE ? OR email LIKE ?)';
            $params = ["%{$keyword}%", "%{$keyword}%"];
        }

        $users = $this->userModel->paginate($page, 20, $where, $params, 'id DESC');

        $this->render('admin/users', [
            'users' => $users,
            'keyword' => $keyword
        ]);
    }

    public function userStatus()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $id = (int)Helper::post('id');
        $status = (int)Helper::post('status');
        
        if (!in_array($status, [0, 1])) {
            $this->jsonError('状态值错误');
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->jsonError('用户不存在');
        }

        if ($user['is_admin'] && $status == 0) {
            $this->jsonError('不能封禁管理员');
        }

        $this->userModel->update($id, ['status' => $status, 'updated_at' => time()]);
        $this->jsonSuccess(null, $status ? '用户已解封' : '用户已封禁');
    }

    public function userEdit()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $id = (int)Helper::post('id');
        $username = trim(Helper::post('username'));
        $email = trim(Helper::post('email'));
        $bio = trim(Helper::post('bio'));
        $password = trim(Helper::post('password'));
        
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->jsonError('用户不存在');
        }
        
        if (empty($username)) {
            $this->jsonError('用户名不能为空');
        }
        
        if (empty($email)) {
            $this->jsonError('邮箱不能为空');
        }
        
        $existing = $this->db->fetch(
            "SELECT id FROM __PREFIX__users WHERE (username = ? OR email = ?) AND id != ?",
            [$username, $email, $id]
        );
        if ($existing) {
            $this->jsonError('用户名或邮箱已被使用');
        }
        
        $data = [
            'username' => $username,
            'email' => $email,
            'bio' => $bio,
            'updated_at' => time()
        ];
        
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $this->jsonError('密码长度不能少于6位');
            }
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $this->userModel->update($id, $data);
        $this->jsonSuccess(null, '用户信息已更新');
    }

    public function userBan()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $id = (int)Helper::post('id');
        $banDays = (int)Helper::post('ban_days');
        $banReason = trim(Helper::post('ban_reason'));
        
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->jsonError('用户不存在');
        }
        
        if ($user['is_admin']) {
            $this->jsonError('不能对管理员进行禁言操作');
        }
        
        $notificationModel = new NotificationModel();
        
        if ($banDays === -1) {
            $data = [
                'ban_type' => 0,
                'ban_until' => 0,
                'ban_reason' => '',
                'updated_at' => time()
            ];
            $this->userModel->update($id, $data);
            
            $notificationModel->send(
                $id,
                NotificationModel::TYPE_SYSTEM,
                '您已被解除禁言',
                '管理员已解除了您的禁言状态，您现在可以正常发布内容、评论和点赞了。',
                [
                    'sender_id' => 0,
                    'target_type' => NotificationModel::TARGET_USER,
                    'target_id' => $id,
                    'data' => ['action' => 'unban']
                ]
            );
            
            $this->jsonSuccess(null, '已解除禁言');
        }
        
        $banUntil = $banDays === 0 ? 0 : time() + ($banDays * 86400);
        
        $data = [
            'ban_type' => 1,
            'ban_until' => $banUntil,
            'ban_reason' => $banReason,
            'updated_at' => time()
        ];
        
        $this->userModel->update($id, $data);
        
        $untilText = $banDays === 0 ? '永久' : date('Y-m-d H:i', $banUntil);
        $reasonText = $banReason ? "\n原因：" . $banReason : '';
        
        $notificationModel->send(
            $id,
            NotificationModel::TYPE_SYSTEM,
            '您已被禁言',
            '您已被管理员禁言，禁言期间无法发布内容、评论和点赞。' . "\n禁言至：" . $untilText . $reasonText,
            [
                'sender_id' => 0,
                'target_type' => NotificationModel::TARGET_USER,
                'target_id' => $id,
                'data' => ['action' => 'ban', 'ban_until' => $banUntil, 'ban_reason' => $banReason]
            ]
        );
        
        $untilTextShort = $banDays === 0 ? '永久' : $banDays . '天';
        $this->jsonSuccess(null, '已禁言用户' . $untilTextShort);
    }

    public function posts()
    {
        $this->checkAdmin();
        
        $page = (int)Helper::get('page', 1);
        $keyword = Helper::get('keyword', '');
        
        $where = 'p.status = 1';
        $params = [];
        
        if ($keyword) {
            $where .= ' AND (p.content LIKE ? OR u.username LIKE ?)';
            $params = ["%{$keyword}%", "%{$keyword}%"];
        }

        $offset = ($page - 1) * 20;
        $sql = "SELECT p.*, u.username, p.ip 
                FROM __PREFIX__posts p 
                INNER JOIN __PREFIX__users u ON p.user_id = u.id 
                WHERE {$where} 
                ORDER BY p.is_pinned DESC, p.is_featured DESC, p.id DESC 
                LIMIT {$offset}, 20";
        
        $posts = $this->db->fetchAll($sql, $params);
        
        foreach ($posts as &$post) {
            $post['time_ago'] = Helper::formatTime($post['created_at']);
            $post['location'] = IpLocation::getLocation($post['ip'] ?? '');
        }

        $total = $this->db->count('posts', 'status = 1');
        $totalPages = ceil($total / 20);

        $this->render('admin/posts', [
            'posts' => $posts,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword
        ]);
    }

    public function postDelete()
    {
        $this->checkAdmin();
        
        $id = (int)Helper::post('id');
        
        $result = $this->db->update('posts', ['status' => 0, 'updated_at' => time()], 'id = ?', [$id]);
        
        if ($result) {
            $this->jsonSuccess(null, '删除成功');
        }
        $this->jsonError('删除失败');
    }

    public function comments()
    {
        $this->checkAdmin();
        
        $page = (int)Helper::get('page', 1);
        $keyword = Helper::get('keyword', '');
        
        $where = 'c.status = 1';
        $params = [];
        
        if ($keyword) {
            $where .= ' AND (c.content LIKE ? OR u.username LIKE ?)';
            $params = ["%{$keyword}%", "%{$keyword}%"];
        }

        $offset = ($page - 1) * 20;
        $sql = "SELECT c.*, u.username 
                FROM __PREFIX__comments c 
                INNER JOIN __PREFIX__users u ON c.user_id = u.id 
                WHERE {$where} 
                ORDER BY c.id DESC 
                LIMIT {$offset}, 20";
        
        $comments = $this->db->fetchAll($sql, $params);
        
        foreach ($comments as &$comment) {
            $comment['time_ago'] = Helper::formatTime($comment['created_at']);
            $comment['location'] = IpLocation::getLocation($comment['ip'] ?? '');
        }

        $total = $this->db->count('comments', 'status = 1');
        $totalPages = ceil($total / 20);

        $this->render('admin/comments', [
            'comments' => $comments,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword
        ]);
    }

    public function commentDelete()
    {
        $this->checkAdmin();
        
        $id = (int)Helper::post('id');
        
        $comment = $this->db->fetch("SELECT * FROM __PREFIX__comments WHERE id = ?", [$id]);
        if (!$comment) {
            $this->setFlash('error', '评论不存在');
            $this->redirect(Helper::url('admin/comments'));
            return;
        }
        
        $result = $this->db->update('comments', ['status' => 0], 'id = ?', [$id]);
        
        if ($result) {
            $this->db->query("UPDATE __PREFIX__posts SET comments = comments - 1 WHERE id = ?", [$comment['post_id']]);
            $this->setFlash('success', '删除成功');
        } else {
            $this->setFlash('error', '删除失败');
        }
        
        $this->redirect(Helper::url('admin/comments'));
    }

    public function togglePin()
    {
        $this->checkAdmin();
        
        $id = (int)Helper::post('id');
        
        $post = $this->postModel->find($id);
        if (!$post) {
            $this->jsonError('微博不存在');
        }
        
        if (!isset($post['is_pinned'])) {
            $this->jsonError('数据库尚未升级，请刷新页面后重试');
        }
        
        $currentStatus = (int)$post['is_pinned'];
        $newStatus = $currentStatus ? 0 : 1;
        
        $result = $this->postModel->update($id, ['is_pinned' => $newStatus]);
        
        $message = $newStatus ? '置顶成功' : '取消置顶成功';
        $this->jsonSuccess(['is_pinned' => $newStatus], $message);
    }

    public function toggleFeature()
    {
        $this->checkAdmin();
        
        $id = (int)Helper::post('id');
        
        $post = $this->postModel->find($id);
        if (!$post) {
            $this->jsonError('微博不存在');
        }
        
        if (!isset($post['is_featured'])) {
            $this->jsonError('数据库尚未升级，请刷新页面后重试');
        }
        
        $currentStatus = (int)$post['is_featured'];
        $newStatus = $currentStatus ? 0 : 1;
        
        $result = $this->postModel->update($id, ['is_featured' => $newStatus]);
        
        $message = $newStatus ? '加精成功' : '取消加精成功';
        $this->jsonSuccess(['is_featured' => $newStatus], $message);
    }

    public function settings()
    {
        $this->checkAdmin();
        
        if ($this->isPost()) {
            $siteName = trim(Helper::post('site_name'));
            $siteSubtitle = trim(Helper::post('site_subtitle'));
            $siteKeywords = trim(Helper::post('site_keywords'));
            $siteDescription = trim(Helper::post('site_description'));
            $postsPerPage = (int)Helper::post('posts_per_page', 10);
            $maxPostLength = (int)Helper::post('max_post_length', 500);
            $maxCommentLength = (int)Helper::post('max_comment_length', 500);
            $registrationOpen = (int)Helper::post('registration_open', 1);
            $allowedEmailSuffixes = trim(Helper::post('allowed_email_suffixes', ''));
            $maxAttachmentSize = (int)Helper::post('max_attachment_size', 10);
            $allowedAttachmentExtensions = trim(Helper::post('allowed_attachment_extensions', 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar'));
            $maxAttachmentCount = (int)Helper::post('max_attachment_count', 5);
            
            if ($postsPerPage < 5 || $postsPerPage > 50) {
                $postsPerPage = 10;
            }
            
            if ($maxPostLength < 50 || $maxPostLength > 5000) {
                $maxPostLength = 500;
            }
            
            if ($maxCommentLength < 50 || $maxCommentLength > 2000) {
                $maxCommentLength = 500;
            }
            
            if ($maxAttachmentSize < 1 || $maxAttachmentSize > 100) {
                $maxAttachmentSize = 10;
            }
            
            if ($maxAttachmentCount < 1 || $maxAttachmentCount > 10) {
                $maxAttachmentCount = 5;
            }
            
            $this->saveSettingValue('site_name', $siteName);
            $this->saveSettingValue('site_subtitle', $siteSubtitle);
            $this->saveSettingValue('site_keywords', $siteKeywords);
            $this->saveSettingValue('site_description', $siteDescription);
            $this->saveSettingValue('posts_per_page', $postsPerPage);
            $this->saveSettingValue('max_post_length', $maxPostLength);
            $this->saveSettingValue('publish_placeholder', trim(Helper::post('publish_placeholder', '')));
            $this->saveSettingValue('max_comment_length', $maxCommentLength);
            $this->saveSettingValue('hide_tag_admin_only', (int)Helper::post('hide_tag_admin_only', 0));
            $this->saveSettingValue('hot_topics_enabled', (int)Helper::post('hot_topics_enabled', 1));
            $this->saveSettingValue('hot_threshold', min(10000, max(1, (int)Helper::post('hot_threshold', 20))));
            $this->saveSettingValue('registration_open', $registrationOpen);
            $this->saveSettingValue('allowed_email_suffixes', $allowedEmailSuffixes);
            $this->saveSettingValue('max_attachment_size', min(100, max(1, $maxAttachmentSize)));
            $this->saveSettingValue('allowed_attachment_extensions', $allowedAttachmentExtensions);
            $this->saveSettingValue('max_attachment_count', $maxAttachmentCount);
            $this->saveSettingValue('max_image_size', min(20, max(1, (int)Helper::post('max_image_size', 5))));
            $this->saveSettingValue('max_avatar_size', min(20, max(1, (int)Helper::post('max_avatar_size', 5))));
            $this->saveSettingValue('max_image_count', min(18, max(1, (int)Helper::post('max_image_count', 9))));
            $this->saveSettingValue('guest_download_allowed', (int)Helper::post('guest_download_allowed', 1));
            $this->saveSettingValue('mention_suggest_scope', Helper::post('mention_suggest_scope', 'all'));
            $this->saveSettingValue('max_video_size', min(500, max(1, (int)Helper::post('max_video_size', 100))));
            $this->saveSettingValue('max_video_count', min(5, max(1, (int)Helper::post('max_video_count', 1))));
            $this->saveSettingValue('point_name', trim(Helper::post('point_name', '积分')));
            $this->saveSettingValue('banned_usernames', trim(Helper::post('banned_usernames', '')));
            $this->saveSettingValue('action_interval', min(60, max(0, (int)Helper::post('action_interval', 0))));
            $this->saveSettingValue('default_all_posts_threshold', min(10000, max(0, (int)Helper::post('default_all_posts_threshold', 100))));
            $this->saveSettingValue('icp_number', trim(Helper::post('icp_number', '')));
            $this->saveSettingValue('icp_url', trim(Helper::post('icp_url', 'https://beian.miit.gov.cn/')));
            $this->saveSettingValue('guest_access_allowed', (int)Helper::post('guest_access_allowed', 1));
            $this->saveSettingValue('registration_email_verify', (int)Helper::post('registration_email_verify', 0));
            $this->saveSettingValue('require_invite_code', (int)Helper::post('require_invite_code', 0));
            $this->saveSettingValue('mail_enabled', (int)Helper::post('mail_enabled', 0));
            $this->saveSettingValue('mail_host', trim(Helper::post('mail_host', '')));
            $this->saveSettingValue('mail_port', (int)Helper::post('mail_port', 465));
            $this->saveSettingValue('mail_encryption', Helper::post('mail_encryption', 'ssl'));
            $this->saveSettingValue('mail_username', trim(Helper::post('mail_username', '')));
            $this->saveSettingValue('mail_password', trim(Helper::post('mail_password', '')));
            $this->saveSettingValue('mail_from_name', trim(Helper::post('mail_from_name', '')));
            $this->saveSettingValue('mail_from_address', trim(Helper::post('mail_from_address', '')));
            
            $usernameMinLength = min(20, max(1, (int)Helper::post('username_min_length', 2)));
            $usernameMaxLength = min(50, max(1, (int)Helper::post('username_max_length', 20)));
            if ($usernameMaxLength < $usernameMinLength) {
                $usernameMaxLength = $usernameMinLength;
            }
            $this->saveSettingValue('username_min_length', $usernameMinLength);
            $this->saveSettingValue('username_max_length', $usernameMaxLength);
            
            $this->saveSettingValue('language_mode', Helper::post('language_mode', 'manual'));
            $this->saveSettingValue('default_language', Helper::post('default_language', 'zh-cn'));
            
            // 处理可用语言数组
            $availableLanguages = Helper::post('available_languages', ['zh-cn']);
            if (is_array($availableLanguages)) {
                $this->saveSettingValue('available_languages', implode(',', $availableLanguages));
            } else {
                $this->saveSettingValue('available_languages', 'zh-cn,zh-tw,en');
            }
            
            $this->setFlash('success', '设置保存成功');
            
            $currentTab = Helper::post('current_tab', 'basic');
            $this->redirect(Helper::url('admin/settings?tab=' . $currentTab));
        }

        $settings = $this->getSettings();
        $this->render('admin/settings', ['settings' => $settings]);
    }

    private function saveSettingValue($key, $value)
    {
        $exists = $this->db->fetch("SELECT * FROM __PREFIX__settings WHERE `key` = ?", [$key]);
        
        if ($exists) {
            $this->db->update('settings', ['value' => $value, 'updated_at' => time()], '`key` = ?', [$key]);
        } else {
            $this->db->insert('settings', [
                'key' => $key,
                'value' => $value,
                'created_at' => time(),
                'updated_at' => time()
            ]);
        }
    }

    private function getSettings()
    {
        $rows = $this->db->fetchAll("SELECT * FROM __PREFIX__settings");
        $settings = [];
        
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        
        return $settings;
    }
    
    public function saveSetting()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        $key = Helper::post('key');
        $value = Helper::post('value');
        
        if (empty($key)) {
            Helper::jsonError('参数错误');
        }
        
        $this->saveSettingValue($key, $value);
        Helper::jsonSuccess(null, '保存成功');
    }
    
    public function testMail()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        $email = Helper::post('email');
        if (empty($email)) {
            Helper::jsonError('请输入测试邮箱地址');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Helper::jsonError('邮箱格式不正确');
        }
        
        $result = Mailer::send($email, '【' . Setting::getSiteName() . '】测试邮件', '
            <div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; padding: 20px; background: #f5f5f5;">
                <div style="max-width: 500px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 30px; text-align: center;">
                    <h2 style="color: #1e293b; margin-bottom: 20px;">邮件测试成功</h2>
                    <p style="color: #64748b;">恭喜！您的邮件服务配置正确，可以正常发送邮件。</p>
                    <p style="color: #94a3b8; font-size: 12px; margin-top: 30px;">发送时间：' . date('Y-m-d H:i:s') . '</p>
                </div>
            </div>
        ');
        
        if ($result['success']) {
            Helper::jsonSuccess(null, '测试邮件已发送至 ' . $email);
        } else {
            Helper::jsonError($result['message']);
        }
    }

    public function userDelete()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        $id = (int)Helper::post('id');
        
        if ($id <= 0) {
            Helper::jsonError('用户ID无效');
        }
        
        $user = $this->db->fetch("SELECT * FROM __PREFIX__users WHERE id = ?", [$id]);
        
        if (!$user) {
            Helper::jsonError('用户不存在');
        }
        
        if ($user['is_admin']) {
            Helper::jsonError('不能删除管理员用户');
        }
        
        if ($id == $_SESSION['user_id']) {
            Helper::jsonError('不能删除自己');
        }
        
        try {
            $posts = $this->db->fetchAll("SELECT images, attachments, videos FROM __PREFIX__posts WHERE user_id = ?", [$id]);
            
            foreach ($posts as $post) {
                $this->deleteMediaFiles($post['images']);
                $this->deleteMediaFiles($post['attachments']);
                $this->deleteMediaFiles($post['videos']);
            }
            
            if (!empty($user['avatar'])) {
                $this->deleteFile($user['avatar']);
            }
            
            $this->db->delete('posts', 'user_id = ?', [$id]);
            $this->db->delete('comments', 'user_id = ?', [$id]);
            $this->db->delete('likes', 'user_id = ?', [$id]);
            $this->db->delete('follows', 'user_id = ? OR follow_id = ?', [$id, $id]);
            $this->db->delete('notifications', 'user_id = ? OR sender_id = ?', [$id, $id]);
            $this->db->delete('point_logs', 'user_id = ?', [$id]);
            $this->db->delete('users', 'id = ?', [$id]);
            
            Helper::jsonSuccess(null, '用户已删除');
        } catch (Exception $e) {
            Helper::jsonError('删除失败：' . $e->getMessage());
        }
    }
    
    private function deleteMediaFiles($jsonStr)
    {
        if (empty($jsonStr)) {
            return;
        }
        
        $items = json_decode($jsonStr, true);
        
        if (!is_array($items)) {
            return;
        }
        
        foreach ($items as $item) {
            if (is_string($item)) {
                $this->deleteFile($item);
            } elseif (is_array($item) && isset($item['path'])) {
                $this->deleteFile($item['path']);
            } elseif (is_array($item) && isset($item['url'])) {
                $this->deleteFile($item['url']);
            }
        }
    }
    
    private function deleteFile($path)
    {
        if (empty($path)) {
            return;
        }
        
        if (preg_match('/^https?:\/\//i', $path)) {
            return;
        }
        
        $fullPath = ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        
        if (strpos($fullPath, ROOT_PATH . 'uploads') !== 0) {
            return;
        }
        
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}

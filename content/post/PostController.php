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
class IndexController extends Controller
{
    private $postModel;
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->postModel = new PostModel();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->view->setLayout(null);
            $this->render('post/guest', [
                'siteName' => Setting::getSiteName(),
                'subtitle' => Setting::getSubtitle(),
                'registrationOpen' => Setting::isRegistrationOpen(),
                'emailVerifyEnabled' => Setting::isRegistrationEmailVerifyEnabled(),
                'icpNumber' => Setting::getIcpNumber(),
                'icpUrl' => Setting::getIcpUrl()
            ]);
            return;
        }
        
        $page = (int)Helper::get('page', 1);
        $tab = Helper::get('tab', '');
        $pageSize = Setting::getPostsPerPage();
        
        $userModel = new UserModel();
        $totalUsers = $userModel->count();
        $threshold = Setting::getDefaultAllPostsThreshold();
        
        if (empty($tab)) {
            if ($userId) {
                $tab = ($totalUsers < $threshold) ? 'all' : 'following';
            } else {
                $tab = 'all';
            }
        }
        
        $pinnedPost = $this->postModel->getPinnedPostForDisplay($userId ? $userId : 0);
        if ($pinnedPost) {
            $pinnedPost['content'] = Helper::parseContent($pinnedPost['content']);
        }
        
        $posts = $this->postModel->getTimeline($page, $pageSize, $userId, $tab);
        foreach ($posts as &$post) {
            $post['content'] = Helper::parseContent($post['content']);
        }
        unset($post);
        
        $totalPosts = $this->postModel->getTimelineCount($userId, $tab);
        $totalPages = ceil($totalPosts / $pageSize);
        
        $announcementModel = new AnnouncementModel();
        $announcements = $announcementModel->getActive();
        
        $userStats = null;
        if ($userId) {
            $userStats = $this->getUserStats($userId);
        }
        
        $hotTopics = $this->postModel->getHotTopics(10);
        
        $this->render('post/index', [
            'posts' => $posts,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages,
            'announcements' => $announcements,
            'tab' => $tab,
            'totalUsers' => $totalUsers,
            'threshold' => $threshold,
            'pinnedPost' => $pinnedPost,
            'userStats' => $userStats,
            'hotTopics' => $hotTopics
        ]);
    }
    
    private function getUserStats($userId)
    {
        $user = $this->userModel->find($userId);
        if (!$user) {
            return null;
        }
        
        $postCount = $this->postModel->count('user_id = ? AND status = 1', [$userId]);
        
        $sql = "SELECT 
                    COALESCE(SUM(likes), 0) + COALESCE(SUM(comments), 0) + COALESCE(SUM(reposts), 0) as total_engagement
                FROM __PREFIX__posts 
                WHERE user_id = ? AND status = 1";
        $result = $this->db->fetch($sql, [$userId]);
        $totalEngagement = (int)($result['total_engagement'] ?? 0);
        
        $points = (int)($user['points'] ?? 0);
        
        return [
            'post_count' => $postCount,
            'total_engagement' => $totalEngagement,
            'points' => $points,
            'user' => $user
        ];
    }
}

class PostController extends Controller
{
    private $postModel;
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->postModel = new PostModel();
        $this->userModel = new UserModel();
    }

    public function publish()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        if (!Helper::isPost()) {
            Helper::jsonError('请求方法错误');
        }

        if (empty($_POST) && !empty($_FILES)) {
            $maxVideoSize = Setting::getMaxVideoSize();
            Helper::jsonError('上传文件过大，请检查视频大小是否超过限制（最大' . $maxVideoSize . 'MB），或联系管理员调整服务器配置');
        }

        $userModel = new UserModel();
        $banInfo = $userModel->getBanInfo($_SESSION['user_id']);
        if ($banInfo) {
            Helper::jsonError('您已被' . $banInfo['type_text'] . '，无法发布内容。' . ($banInfo['until_text'] !== '永久' ? '解禁时间：' . $banInfo['until_text'] : '') . ($banInfo['reason'] ? ' 原因：' . $banInfo['reason'] : ''));
        }

        if (!$this->checkActionInterval()) {
            $remaining = $this->getRemainingInterval();
            Helper::jsonError('操作过于频繁，请' . $remaining . '秒后再试');
        }

        $content = trim(Helper::post('content'));
        
        if (empty($content)) {
            Helper::jsonError('内容不能为空');
        }

        if (mb_strlen($content, 'UTF-8') > Setting::getMaxPostLength()) {
            Helper::jsonError('内容不能超过' . Setting::getMaxPostLength() . '字');
        }

        $content = Security::xssClean($content);
        
        $images = [];
        if (!empty($_FILES['images']) && is_array($_FILES['images']['name']) && count($_FILES['images']['name']) > 0) {
            $images = $this->uploadImages();
        }

        $attachments = [];
        if (!empty($_FILES['attachments']) && is_array($_FILES['attachments']['name']) && count($_FILES['attachments']['name']) > 0) {
            $attachments = $this->uploadAttachments();
        }

        $videos = [];
        if (!empty($_FILES['videos']) && is_array($_FILES['videos']['name']) && count($_FILES['videos']['name']) > 0) {
            $videos = $this->uploadVideos();
        }

        $postId = $this->postModel->publish([
            'user_id' => $_SESSION['user_id'],
            'content' => $content,
            'images' => $images,
            'attachments' => $attachments,
            'videos' => $videos
        ]);

        if ($postId) {
            Point::change($_SESSION['user_id'], 'publish_post', 'post', $postId);
            
            $post = $this->postModel->getPost($postId, $_SESSION['user_id']);
            $user = $this->userModel->find($_SESSION['user_id']);
            
            $avatar = Helper::avatar($user['avatar'] ?? null, $user['username']);
            $username = htmlspecialchars($user['username']);
            $profileUrl = Helper::url('user/profile?id=' . $user['id']);
            
            $formattedContent = Security::escape($post['content']);
            $formattedContent = preg_replace('/#(.+?)#/', '<a href="' . Helper::url('post/topic?keyword=$1') . '">#$1#</a>', $formattedContent);
            $formattedContent = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|$)/u', '<a href="' . Helper::url('user/profile?username=$1') . '">@$1</a>', $formattedContent);
            $formattedContent = preg_replace('/(https?:\/\/[^\s<]+)/i', '<a href="$1" target="_blank" rel="noopener">$1</a>', $formattedContent);
            $formattedContent = Helper::parseEmojis($formattedContent);
            
            $hideTagAdminOnly = Setting::isHideTagAdminOnly();
            $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
            if ($hideTagAdminOnly && !$isAdmin) {
                $formattedContent = str_replace(['[hide]', '[/hide]'], ['&#91;hide&#93;', '&#91;/hide&#93;'], $formattedContent);
            } else {
                $formattedContent = $this->postModel->parseHideContent($formattedContent, $postId, $_SESSION['user_id'], $_SESSION['user_id']);
            }
            
            $html = '<div class="post-item" data-id="' . $postId . '">';
            $html .= '<div class="post-avatar"><a href="' . $profileUrl . '">' . $avatar . '</a></div>';
            $html .= '<div class="post-content">';
            $html .= '<div class="post-header">';
            $html .= '<a href="' . $profileUrl . '" class="username">' . $username . '</a>';
            $html .= '<span class="time">刚刚</span></div>';
            $html .= '<div class="post-text">' . $formattedContent . '</div>';
            
            if (!empty($images)) {
                $html .= '<div class="post-images">';
                foreach ($images as $image) {
                    $imgUrl = Helper::uploadUrl($image);
                    $html .= '<img src="' . $imgUrl . '" alt="" onclick="previewImage(this)">';
                }
                $html .= '</div>';
            }
            
            if (!empty($videos)) {
                $html .= '<div class="post-videos">';
                foreach ($videos as $video) {
                    $videoUrl = Helper::uploadUrl($video['path']);
                    $html .= '<div class="post-video-item" onclick="playVideo(this)">';
                    $html .= '<video preload="metadata" playsinline>';
                    $html .= '<source src="' . $videoUrl . '#t=3" type="video/' . $video['ext'] . '">';
                    $html .= '您的浏览器不支持视频播放';
                    $html .= '</video>';
                    $html .= '<div class="video-overlay">';
                    $html .= '<div class="video-play-btn">▶</div>';
                    $html .= '</div>';
                    $html .= '<div class="video-name-tag">' . htmlspecialchars($video['name']) . '</div>';
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
            
            if (!empty($attachments)) {
                $html .= '<div class="post-attachments">';
                $html .= '<div class="post-attachments-title">📎 附件</div>';
                foreach ($attachments as $index => $attachment) {
                    $attUrl = Helper::url('download/attachment?id=' . $postId . '&index=' . $index);
                    $html .= '<a href="' . $attUrl . '" class="post-attachment-item">';
                    $html .= '<span class="post-attachment-icon">📄</span>';
                    $html .= '<span class="post-attachment-name">' . htmlspecialchars($attachment['name']) . '</span>';
                    $html .= '<span class="post-attachment-size">' . $this->formatFileSize($attachment['size']) . '</span>';
                    $html .= '</a>';
                }
                $html .= '</div>';
            }
            
            $post = [
                'id' => $postId,
                'user_id' => $_SESSION['user_id'],
                'reposts' => 0,
                'comments' => 0,
                'likes' => 0
            ];
            $html .= PostActionHelper::render($post);
            $html .= CommentHelper::renderCommentBox($postId);
            $html .= '</div></div>';
            
            try {
                $this->sendMentionNotifications($content, $postId, $_SESSION['user_id'], null);
            } catch (Exception $e) {
            }
            
            Helper::jsonSuccess(['id' => $postId, 'html' => $html], '发布成功');
        }

        Helper::jsonError('发布失败');
    }

    public function detail()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url());
        }
        
        $id = (int)Helper::get('id');
        
        if (!$id) {
            $this->redirect(Helper::url());
        }

        $post = $this->postModel->getPost($id, $userId ? $userId : 0);
        
        if (!$post || $post['status'] != 1) {
            Helper::setFlash('error', '动态不存在');
            $this->redirect(Helper::url());
        }

        $post['formatted_content'] = Security::escape($post['content']);
        $post['formatted_content'] = preg_replace('/#(.+?)#/', '<a href="' . Helper::url('post/topic?keyword=$1') . '">#$1#</a>', $post['formatted_content']);
        $post['formatted_content'] = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|$)/u', '<a href="' . Helper::url('user/profile?username=$1') . '">@$1</a>', $post['formatted_content']);
        $post['formatted_content'] = preg_replace('/(https?:\/\/[^\s<]+)/i', '<a href="$1" target="_blank" rel="noopener">$1</a>', $post['formatted_content']);
        $post['formatted_content'] = Helper::parseEmojis($post['formatted_content']);
        
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $post['formatted_content'] = $this->postModel->parseHideContent($post['formatted_content'], $id, $userId, $post['user_id']);

        $comments = $this->postModel->getComments($id);
        $isLiked = isset($_SESSION['user_id']) ? $this->postModel->isLiked($id, $_SESSION['user_id']) : false;
        $isReposted = isset($_SESSION['user_id']) ? $this->postModel->isReposted($id, $_SESSION['user_id']) : false;

        $plainContent = strip_tags($post['content']);
        $plainContent = preg_replace('/\[hide\].*?\[\/hide\]/is', '[隐藏内容]', $plainContent);
        $plainContent = preg_replace('/#([^#]+)#/', '$1', $plainContent);
        $plainContent = trim($plainContent);
        
        $title = mb_substr($plainContent, 0, 40, 'UTF-8');
        if (mb_strlen($plainContent, 'UTF-8') > 40) {
            $title .= '...';
        }
        
        $description = mb_substr($plainContent, 0, 200, 'UTF-8');
        if (mb_strlen($plainContent, 'UTF-8') > 200) {
            $description .= '...';
        }

        $this->render('post/detail', [
            'post' => $post,
            'comments' => $comments,
            'isLiked' => $isLiked,
            'isReposted' => $isReposted,
            'title' => $title,
            'description' => $description
        ]);
    }

    public function delete()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        $id = (int)Helper::post('id');
        
        $result = $this->postModel->deletePost($id, $_SESSION['user_id']);
        
        if ($result) {
            Point::change($_SESSION['user_id'], 'delete_post', 'post', $id);
            
            Helper::jsonSuccess(null, '删除成功');
        } else {
            Helper::jsonError('删除失败');
        }
    }

    public function edit()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        if (!Helper::isPost()) {
            Helper::jsonError('请求方法错误');
        }
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败，请刷新页面重试');
        }

        $id = (int)Helper::post('id');
        $content = trim(Helper::post('content'));
        
        if (empty($content)) {
            Helper::jsonError('内容不能为空');
        }

        $maxPostLength = Setting::getMaxPostLength();
        if (mb_strlen($content, 'UTF-8') > $maxPostLength) {
            Helper::jsonError('内容长度不能超过' . $maxPostLength . '个字符');
        }

        $post = $this->postModel->find($id);
        if (!$post) {
            Helper::jsonError('微博不存在');
        }

        $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
        if ($post['user_id'] != $_SESSION['user_id'] && !$isAdmin) {
            Helper::jsonError('无权编辑此微博');
        }

        $editCount = (int)($post['edit_count'] ?? 0) + 1;
        
        $updateData = [
            'content' => $content,
            'updated_at' => time()
        ];
        
        try {
            $this->db->query("SHOW COLUMNS FROM " . $this->db->table('posts') . " LIKE 'edit_count'");
            if ($this->db->fetch()) {
                $updateData['edit_count'] = $editCount;
                $updateData['edited_at'] = time();
            }
        } catch (Exception $e) {
        }
        
        $result = $this->postModel->update($id, $updateData);
        
        if (!$result) {
            Helper::jsonError('更新失败，请重试');
        }

        $updatedPost = $this->postModel->getPost($id, $_SESSION['user_id']);
        
        $formattedContent = Security::escape($updatedPost['content']);
        $formattedContent = preg_replace('/#(.+?)#/', '<a href="' . Helper::url('post/topic?keyword=$1') . '">#$1#</a>', $formattedContent);
        $formattedContent = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|$)/u', '<a href="' . Helper::url('user/profile?username=$1') . '">@$1</a>', $formattedContent);
        $formattedContent = preg_replace('/(https?:\/\/[^\s<]+)/i', '<a href="$1" target="_blank" rel="noopener">$1</a>', $formattedContent);
        $formattedContent = Helper::parseEmojis($formattedContent);
        
        Helper::jsonSuccess([
            'content' => $formattedContent,
            'edit_count' => $editCount,
            'edited_at' => time()
        ], '编辑成功');
    }

    public function getEditData()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }

        $id = (int)Helper::get('id');
        
        $post = $this->postModel->find($id);
        if (!$post) {
            Helper::jsonError('微博不存在');
        }

        $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
        if ($post['user_id'] != $_SESSION['user_id'] && !$isAdmin) {
            Helper::jsonError('无权编辑此微博');
        }

        Helper::jsonSuccess([
            'id' => $post['id'],
            'content' => $post['content'],
            'images' => json_decode($post['images'] ?? '[]', true),
            'attachments' => json_decode($post['attachments'] ?? '[]', true)
        ]);
    }

    public function like()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        $banInfo = $this->userModel->getBanInfo($_SESSION['user_id']);
        if ($banInfo) {
            Helper::jsonError('您已被' . $banInfo['type_text'] . '，无法点赞');
        }

        if (!$this->checkActionInterval()) {
            $remaining = $this->getRemainingInterval();
            Helper::jsonError('操作过于频繁，请' . $remaining . '秒后再试');
        }
        
        $id = (int)Helper::post('id');
        $result = $this->postModel->like($id, $_SESSION['user_id']);
        
        if ($result) {
            Point::change($_SESSION['user_id'], 'like_post', 'post', $id);
            
            $post = $this->postModel->getPost($id, $_SESSION['user_id']);
            
            if ($post && isset($post['user_id']) && $post['user_id'] != $_SESSION['user_id']) {
                $this->sendLikeNotification($post['user_id'], $id);
            }
            
            Helper::jsonSuccess(['likes' => $post['likes'] ?? 0]);
        } else {
            Helper::jsonError('已点赞过');
        }
    }
    
    private function sendLikeNotification($postUserId, $postId)
    {
        try {
            $notificationModel = new NotificationModel();
            $sender = $this->userModel->find($_SESSION['user_id']);
            
            if (!$sender) {
                Logger::error('点赞通知发送失败：发送者不存在', [
                    'post_id' => $postId,
                    'sender_id' => $_SESSION['user_id']
                ]);
                return;
            }
            
            $senderName = $sender['username'];
            $notificationId = $notificationModel->sendLikeNotification(
                $postUserId, 
                $_SESSION['user_id'], 
                $postId, 
                $senderName
            );
            
            if (!$notificationId) {
                Logger::warning('点赞通知未发送', [
                    'post_id' => $postId,
                    'post_user_id' => $postUserId,
                    'sender_id' => $_SESSION['user_id'],
                    'reason' => '可能是自己点赞自己的帖子'
                ]);
            }
        } catch (\Exception $e) {
            Logger::error('点赞通知发送异常', [
                'post_id' => $postId,
                'sender_id' => $_SESSION['user_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function unlike()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        $id = (int)Helper::post('id');
        $result = $this->postModel->unlike($id, $_SESSION['user_id']);
        
        if ($result) {
            $post = $this->postModel->getPost($id, $_SESSION['user_id']);
            Helper::jsonSuccess(['likes' => $post['likes']]);
        } else {
            Helper::jsonError('取消点赞失败');
        }
    }

    public function favorite()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        $id = (int)Helper::post('id');
        $post = $this->postModel->find($id);
        
        if (!$post || $post['status'] != 1) {
            Helper::jsonError('微博不存在');
        }
        
        if ($post['user_id'] == $_SESSION['user_id']) {
            Helper::jsonError('不能收藏自己的微博');
        }
        
        $favoriteModel = new FavoriteModel();
        $result = $favoriteModel->add($id, $_SESSION['user_id']);
        
        if ($result) {
            $this->sendFavoriteNotification($post['user_id'], $id);
            Helper::jsonSuccess(null, '收藏成功');
        } else {
            Helper::jsonError('已收藏过');
        }
    }
    
    private function sendFavoriteNotification($postUserId, $postId)
    {
        try {
            $notificationModel = new NotificationModel();
            $sender = $this->userModel->find($_SESSION['user_id']);
            
            if (!$sender) {
                Logger::error('收藏通知发送失败：发送者不存在', [
                    'post_id' => $postId,
                    'sender_id' => $_SESSION['user_id']
                ]);
                return;
            }
            
            $senderName = $sender['username'];
            $notificationId = $notificationModel->sendFavoriteNotification(
                $postUserId, 
                $_SESSION['user_id'], 
                $postId, 
                $senderName
            );
            
            if (!$notificationId) {
                Logger::warning('收藏通知未发送', [
                    'post_id' => $postId,
                    'post_user_id' => $postUserId,
                    'sender_id' => $_SESSION['user_id'],
                    'reason' => '可能是自己收藏自己的帖子'
                ]);
            }
        } catch (\Exception $e) {
            Logger::error('收藏通知发送异常', [
                'post_id' => $postId,
                'sender_id' => $_SESSION['user_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function unfavorite()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        $id = (int)Helper::post('id');
        $favoriteModel = new FavoriteModel();
        $result = $favoriteModel->remove($id, $_SESSION['user_id']);
        
        if ($result) {
            Helper::jsonSuccess(null, '取消收藏成功');
        } else {
            Helper::jsonError('取消收藏失败');
        }
    }

    public function comment()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        if (!Helper::isPost()) {
            Helper::jsonError('请求方法错误');
        }

        $banInfo = $this->userModel->getBanInfo($_SESSION['user_id']);
        if ($banInfo) {
            Helper::jsonError('您已被' . $banInfo['type_text'] . '，无法发表评论');
        }

        if (!$this->checkActionInterval()) {
            $remaining = $this->getRemainingInterval();
            Helper::jsonError('操作过于频繁，请' . $remaining . '秒后再试');
        }

        $postId = (int)Helper::post('post_id');
        $content = trim(Helper::post('content'));
        $parentId = (int)Helper::post('parent_id', 0);
        $replyToUserId = (int)Helper::post('reply_to_user_id', 0);
        $alsoRepost = (int)Helper::post('also_repost', 0);
        
        if (empty($content)) {
            Helper::jsonError('评论内容不能为空');
        }

        if (mb_strlen($content, 'UTF-8') > Setting::getMaxCommentLength()) {
            Helper::jsonError('评论内容不能超过' . Setting::getMaxCommentLength() . '字');
        }

        $content = Security::xssClean($content);
        
        $db = Database::getInstance();
        $db->insert('comments', [
            'post_id' => $postId,
            'user_id' => $_SESSION['user_id'],
            'content' => $content,
            'parent_id' => $parentId,
            'reply_to_user_id' => $replyToUserId,
            'status' => 1,
            'ip' => Helper::getIp(),
            'created_at' => time()
        ]);
        
        $commentId = $db->lastInsertId();
        
        if ($commentId) {
            Point::change($_SESSION['user_id'], 'publish_comment', 'comment', $commentId);
            
            $db->query(
                "UPDATE __PREFIX__posts SET comments = comments + 1 WHERE id = ?",
                [$postId]
            );
            
            try {
                $post = $this->postModel->getPost($postId, $_SESSION['user_id']);
                if ($post && $post['user_id'] != $_SESSION['user_id']) {
                    $notificationModel = new NotificationModel();
                    $sender = $this->userModel->find($_SESSION['user_id']);
                    if ($sender) {
                        $notificationModel->sendCommentNotification($post['user_id'], $_SESSION['user_id'], $postId, $content, $sender['username']);
                    }
                }
                
                if ($replyToUserId && $replyToUserId != $_SESSION['user_id']) {
                    $notificationModel = new NotificationModel();
                    $sender = $this->userModel->find($_SESSION['user_id']);
                    if ($sender) {
                        $notificationModel->send(
                            $replyToUserId,
                            NotificationModel::TYPE_COMMENT,
                            "{$sender['username']} 回复了你的评论",
                            mb_substr($content, 0, 50, 'UTF-8'),
                            [
                                'sender_id' => $_SESSION['user_id'],
                                'target_type' => NotificationModel::TARGET_POST,
                                'target_id' => $postId,
                                'data' => ['post_id' => $postId, 'comment_id' => $commentId]
                            ]
                        );
                    }
                }
                
                $this->sendMentionNotifications($content, $postId, $_SESSION['user_id'], $commentId);

                if ($alsoRepost && $post) {
                    $repostId = $this->postModel->repost($_SESSION['user_id'], $postId, $content, false);
                    if ($repostId && $post['user_id'] != $_SESSION['user_id']) {
                        $notificationModel = new NotificationModel();
                        $sender = $this->userModel->find($_SESSION['user_id']);
                        if ($sender) {
                            $notificationModel->send(
                                $post['user_id'],
                                NotificationModel::TYPE_FOLLOW,
                                "{$sender['username']} 转发了你的微博",
                                mb_substr($content, 0, 50, 'UTF-8'),
                                [
                                    'sender_id' => $_SESSION['user_id'],
                                    'target_type' => NotificationModel::TARGET_POST,
                                    'target_id' => $postId,
                                    'data' => ['post_id' => $repostId, 'original_post_id' => $postId]
                                ]
                            );
                        }
                    }
                }
            } catch (Exception $e) {
            }
            
            $comment = $db->fetch(
                "SELECT c.*, u.username, u.avatar 
                 FROM __PREFIX__comments c 
                 INNER JOIN __PREFIX__users u ON c.user_id = u.id 
                 WHERE c.id = ?",
                [$commentId]
            );
            
            $responseData = ['id' => $commentId];
            
            if ($comment) {
                $comment['time_ago'] = Helper::formatTime($comment['created_at']);
                $responseData['html'] = CommentHelper::renderCommentItem($comment);
            }
            
            $post = $this->postModel->getPost($postId, $_SESSION['user_id']);
            if ($post && preg_match('/\[hide\].*?\[\/hide\]/is', $post['content'])) {
                $formattedContent = Security::escape($post['content']);
                $formattedContent = preg_replace('/#(.+?)#/', '<a href="' . Helper::url('post/topic?keyword=$1') . '">#$1#</a>', $formattedContent);
                $formattedContent = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|$)/u', '<a href="' . Helper::url('user/profile?username=$1') . '">@$1</a>', $formattedContent);
                $formattedContent = preg_replace('/(https?:\/\/[^\s<]+)/i', '<a href="$1" target="_blank" rel="noopener">$1</a>', $formattedContent);
                $formattedContent = Helper::parseEmojis($formattedContent);
                $formattedContent = $this->postModel->parseHideContent($formattedContent, $postId, $_SESSION['user_id'], $post['user_id']);
                $responseData['post_content'] = $formattedContent;
            }
            
            Helper::jsonSuccess($responseData, '评论成功');
        }

        Helper::jsonError('评论失败');
    }

    public function repost()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            Helper::jsonError('请先登录', 401);
        }
        
        if (!Helper::isPost()) {
            Helper::jsonError('请求方法错误');
        }

        $banInfo = $this->userModel->getBanInfo($_SESSION['user_id']);
        if ($banInfo) {
            Helper::jsonError('您已被' . $banInfo['type_text'] . '，无法转发微博');
        }

        if (!$this->checkActionInterval()) {
            $remaining = $this->getRemainingInterval();
            Helper::jsonError('操作过于频繁，请' . $remaining . '秒后再试');
        }

        $originalPostId = (int)Helper::post('post_id');
        $content = trim(Helper::post('content'));
        $alsoComment = (int)Helper::post('also_comment', 0);

        if (!$originalPostId) {
            Helper::jsonError('参数错误');
        }

        $originalPost = $this->postModel->getPost($originalPostId, $_SESSION['user_id']);
        if (!$originalPost || $originalPost['status'] != 1) {
            Helper::jsonError('原微博不存在');
        }

        if (mb_strlen($content, 'UTF-8') > Setting::getMaxPostLength()) {
            Helper::jsonError('转发内容不能超过' . Setting::getMaxPostLength() . '字');
        }

        $content = Security::xssClean($content);

        $postId = $this->postModel->repost($_SESSION['user_id'], $originalPostId, $content, $alsoComment);

        if ($postId) {
            Point::change($_SESSION['user_id'], 'repost', 'post', $postId);
            
            try {
                $notificationModel = new NotificationModel();
                $sender = $this->userModel->find($_SESSION['user_id']);
                if ($sender && $originalPost['user_id'] != $_SESSION['user_id']) {
                    $notificationModel->send(
                        $originalPost['user_id'],
                        NotificationModel::TYPE_FOLLOW,
                        "{$sender['username']} 转发了你的微博",
                        mb_substr($content ?: $originalPost['content'], 0, 50, 'UTF-8'),
                        [
                            'sender_id' => $_SESSION['user_id'],
                            'target_type' => NotificationModel::TARGET_POST,
                            'target_id' => $originalPostId,
                            'data' => ['post_id' => $postId, 'original_post_id' => $originalPostId]
                        ]
                    );
                }
                
                if ($content) {
                    $this->sendMentionNotifications($content, $postId, $_SESSION['user_id'], null);
                }
            } catch (Exception $e) {
            }

            Helper::jsonSuccess(['id' => $postId], '转发成功');
        }

        Helper::jsonError('转发失败');
    }

    public function getComments()
    {
        $postId = (int)Helper::get('post_id');
        $page = (int)Helper::get('page', 1);
        
        if (!$postId) {
            Helper::jsonError('参数错误');
        }
        
        $comments = $this->postModel->getComments($postId, $page, 5);
        
        $html = CommentHelper::renderCommentList($comments);
        
        Helper::jsonSuccess(['html' => $html, 'count' => count($comments)]);
    }

    public function getReplies()
    {
        $parentId = (int)Helper::get('parent_id');
        $limit = (int)Helper::get('limit', 10);
        
        if (!$parentId) {
            Helper::jsonError('参数错误');
        }
        
        $replies = $this->postModel->getReplies($parentId, $limit);
        
        $html = CommentHelper::renderReplyList($replies);
        
        Helper::jsonSuccess(['html' => $html, 'count' => count($replies)]);
    }

    public function hot()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url());
        }
        
        $page = max(1, (int)Helper::get('page', 1));
        $pageSize = max(1, min(100, (int)Setting::getPostsPerPage()));
        $threshold = max(0, (int)Setting::getHotThreshold());
        
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT p.*, u.username, u.avatar,
                    (
                        (SELECT COUNT(*) FROM __PREFIX__likes l WHERE l.post_id = p.id AND l.user_id != p.user_id) +
                        (SELECT COUNT(*) FROM __PREFIX__comments c WHERE c.post_id = p.id AND c.user_id != p.user_id AND c.status = 1) +
                        (SELECT COUNT(*) FROM __PREFIX__posts rp WHERE rp.repost_id = p.id AND rp.user_id != p.user_id AND rp.status = 1) +
                        (SELECT COUNT(*) FROM __PREFIX__favorites f WHERE f.post_id = p.id AND f.user_id != p.user_id)
                    ) as hot_score
                FROM __PREFIX__posts p 
                INNER JOIN __PREFIX__users u ON p.user_id = u.id 
                WHERE p.status = 1 
                HAVING hot_score >= ?
                ORDER BY p.created_at DESC 
                LIMIT ?, ?";
        
        $posts = $this->db->fetchAll($sql, [$threshold, $offset, $pageSize]);
        
        foreach ($posts as &$post) {
            $post['images'] = is_array($post['images']) ? $post['images'] : ($post['images'] ? json_decode($post['images'], true) : []);
            $post['attachments'] = is_array($post['attachments']) ? $post['attachments'] : ($post['attachments'] ? json_decode($post['attachments'], true) : []);
            $post['videos'] = is_array($post['videos']) ? $post['videos'] : ($post['videos'] ? json_decode($post['videos'], true) : []);
            $post['time_ago'] = Helper::formatTime($post['created_at']);
            $post['content'] = Security::escape($post['content']);
            $post['content'] = preg_replace('/#(.+?)#/', '<a href="' . Helper::url('post/topic?keyword=$1') . '">#$1#</a>', $post['content']);
            $post['content'] = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|:|$|\/\/)/u', '<a href="' . Helper::url('user/profile?username=$1') . '">@$1</a>', $post['content']);
            $post['content'] = Helper::parseEmojis($post['content']);
            
            if ($userId) {
                $post['is_liked'] = $this->postModel->isLiked($post['id'], $userId);
                $favoriteModel = new FavoriteModel();
                $post['is_favorited'] = $favoriteModel->isFavorited($post['id'], $userId);
            } else {
                $post['is_liked'] = false;
                $post['is_favorited'] = false;
            }
        }
        
        $countSql = "SELECT COUNT(*) as total FROM (
                        SELECT p.id,
                            (
                                (SELECT COUNT(*) FROM __PREFIX__likes l WHERE l.post_id = p.id AND l.user_id != p.user_id) +
                                (SELECT COUNT(*) FROM __PREFIX__comments c WHERE c.post_id = p.id AND c.user_id != p.user_id AND c.status = 1) +
                                (SELECT COUNT(*) FROM __PREFIX__posts rp WHERE rp.repost_id = p.id AND rp.user_id != p.user_id AND rp.status = 1) +
                                (SELECT COUNT(*) FROM __PREFIX__favorites f WHERE f.post_id = p.id AND f.user_id != p.user_id)
                            ) as hot_score
                        FROM __PREFIX__posts p 
                        WHERE p.status = 1 
                        HAVING hot_score >= ?
                    ) as hot_posts";
        $countResult = $this->db->fetch($countSql, [$threshold]);
        $total = (int)($countResult['total'] ?? 0);
        $totalPages = ceil($total / $pageSize);
        
        $this->render('post/hot', [
            'posts' => $posts,
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'totalPages' => $totalPages,
            'threshold' => $threshold,
            'title' => '热门'
        ]);
    }

    public function topic()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url());
        }
        
        $keyword = trim(Helper::get('keyword'));
        $page = (int)Helper::get('page', 1);
        
        if (empty($keyword)) {
            $this->redirect(Helper::url());
        }
        
        $keyword = Security::xssClean($keyword);
        $pageSize = Setting::getPostsPerPage();
        
        $posts = $this->postModel->getPostsByTopic($keyword, $page, $pageSize);
        foreach ($posts as &$post) {
            $post['content'] = Helper::parseContent($post['content']);
        }
        unset($post);
        
        $total = $this->postModel->countPostsByTopic($keyword);
        
        $title = '#' . $keyword . '# - 话题';
        
        $this->render('post/topic', [
            'posts' => $posts,
            'keyword' => $keyword,
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'title' => $title
        ]);
    }

    public function featured()
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        if (!$userId && !Setting::isGuestAccessAllowed()) {
            $this->redirect(Helper::url());
        }
        
        $page = (int)Helper::get('page', 1);
        $pageSize = Setting::getPostsPerPage();
        
        $posts = $this->postModel->getFeaturedPosts($page, $pageSize);
        foreach ($posts as &$post) {
            $post['content'] = Helper::parseContent($post['content']);
        }
        unset($post);
        
        $total = $this->postModel->countFeaturedPosts();
        $totalPages = ceil($total / $pageSize);
        
        $this->render('post/featured', [
            'posts' => $posts,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages,
            'total' => $total
        ]);
    }

    public function pin()
    {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            Helper::jsonError('无权限操作');
        }
        
        $id = (int)Helper::post('id');
        if (!$id) {
            Helper::jsonError('参数错误');
        }
        
        $result = $this->postModel->togglePin($id);
        if ($result) {
            $post = $this->postModel->find($id);
            Helper::jsonSuccess(['pinned' => $post['is_pinned'] ? 1 : 0]);
        }
        
        Helper::jsonError('操作失败');
    }

    public function feature()
    {
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            Helper::jsonError('无权限操作');
        }
        
        $id = (int)Helper::post('id');
        if (!$id) {
            Helper::jsonError('参数错误');
        }
        
        $result = $this->postModel->toggleFeature($id);
        if ($result) {
            $post = $this->postModel->find($id);
            Helper::jsonSuccess(['featured' => $post['is_featured'] ? 1 : 0]);
        }
        
        Helper::jsonError('操作失败');
    }

    private function sendMentionNotifications($content, $postId, $senderId, $commentId = null)
    {
        $usernames = Helper::extractMentions($content);
        
        if (empty($usernames)) {
            return;
        }
        
        $notificationModel = new NotificationModel();
        $sender = $this->userModel->find($senderId);
        
        if (!$sender) {
            return;
        }
        
        $senderName = $sender['username'];
        
        foreach ($usernames as $username) {
            try {
                $mentionedUser = $this->userModel->findByUsername($username);
                
                if ($mentionedUser && $mentionedUser['id'] != $senderId) {
                    $notificationModel->sendMentionNotification(
                        $mentionedUser['id'],
                        $senderId,
                        $postId,
                        $senderName,
                        $commentId
                    );
                }
            } catch (Exception $e) {
            }
        }
    }

    private function uploadImages()
    {
        $images = [];
        $files = $_FILES['images'];
        $count = count($files['name']);
        $maxSize = Setting::getMaxImageSize() * 1024 * 1024;
        
        for ($i = 0; $i < $count && $i < 9; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            if ($files['size'][$i] > $maxSize) {
                continue;
            }

            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (empty($ext) || !in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                $mimeToExt = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp'
                ];
                
                if (isset($mimeToExt[$mimeType])) {
                    $ext = $mimeToExt[$mimeType];
                    $file['name'] = 'paste_' . time() . '.' . $ext;
                } else {
                    continue;
                }
            }

            $filename = 'images/' . date('Ymd') . '/' . uniqid() . '.' . $ext;
            $savePath = UPLOAD_PATH . $filename;
            
            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            if (!is_writable($dir)) {
                chmod($dir, 0755);
            }

            if (move_uploaded_file($file['tmp_name'], $savePath)) {
                chmod($savePath, 0644);
                $images[] = $filename;
            }
        }
        
        return $images;
    }

    private function uploadAttachments()
    {
        $attachments = [];
        $files = $_FILES['attachments'];
        $count = count($files['name']);
        $maxSize = Setting::getMaxAttachmentSize() * 1024 * 1024;
        $allowedExtensions = Setting::getAllowedAttachmentExtensions();
        $maxCount = Setting::getMaxAttachmentCount();
        
        // 允许的MIME类型映射
        $allowedMimeTypes = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'txt' => ['text/plain'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'rar' => ['application/x-rar-compressed', 'application/vnd.rar'],
            '7z' => ['application/x-7z-compressed'],
        ];
        
        for ($i = 0; $i < $count && $i < $maxCount; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            if ($files['size'][$i] > $maxSize) {
                continue;
            }

            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowedExtensions)) {
                continue;
            }
            
            // 验证MIME类型
            if (function_exists('finfo_open') && isset($allowedMimeTypes[$ext])) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $files['tmp_name'][$i]);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedMimeTypes[$ext])) {
                    // MIME类型不匹配，跳过此文件
                    continue;
                }
            }

            $filename = 'attachments/' . date('Ymd') . '/' . uniqid() . '.' . $ext;
            $savePath = UPLOAD_PATH . $filename;
            
            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            if (!is_writable($dir)) {
                chmod($dir, 0755);
            }

            if (move_uploaded_file($files['tmp_name'][$i], $savePath)) {
                chmod($savePath, 0644);
                $attachments[] = [
                    'name' => $files['name'][$i],
                    'path' => $filename,
                    'size' => $files['size'][$i],
                    'ext' => $ext
                ];
            }
        }

        return $attachments;
    }

    private function formatFileSize($bytes)
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }
    }

    private function uploadVideos()
    {
        $videos = [];
        $files = $_FILES['videos'];
        $count = count($files['name']);
        $maxSize = Setting::getMaxVideoSize() * 1024 * 1024;
        $maxCount = Setting::getMaxVideoCount();
        $allowedExtensions = ['mp4', 'webm', 'mov', 'avi', 'mkv'];
        
        for ($i = 0; $i < $count && $i < $maxCount; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            if ($files['size'][$i] > $maxSize) {
                continue;
            }

            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowedExtensions)) {
                continue;
            }

            $dateDir = date('Y/m/d');
            $filename = 'videos/' . $dateDir . '/' . uniqid() . '.' . $ext;
            $savePath = UPLOAD_PATH . $filename;
            
            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            if (!is_writable($dir)) {
                chmod($dir, 0755);
            }

            if (move_uploaded_file($files['tmp_name'][$i], $savePath)) {
                chmod($savePath, 0644);
                $videos[] = [
                    'name' => $files['name'][$i],
                    'path' => $filename,
                    'size' => $files['size'][$i],
                    'ext' => $ext
                ];
            }
        }

        return $videos;
    }
}

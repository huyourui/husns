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

namespace Controller\Traits;

use Helper;
use Security;
use Setting;
use Point;
use Hook;
use NotificationModel;
use Logger;

/**
 * 帖子发布相关 Trait
 * 
 * 包含帖子发布、编辑、删除等功能
 * 
 * @package Controller\Traits
 */
trait PostPublishTrait
{
    /**
     * 发布帖子
     *
     * @return void
     */
    public function publish(): void
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

        $userModel = new \UserModel();
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
            
            $html = $this->renderPostHtml($post, $user, $images, $videos, $attachments, $postId);
            
            try {
                $this->sendMentionNotifications($content, $postId, $_SESSION['user_id'], null);
            } catch (Exception $e) {
            }
            
            Helper::jsonSuccess(['id' => $postId, 'html' => $html], '发布成功');
        }

        Helper::jsonError('发布失败');
    }

    /**
     * 编辑帖子
     *
     * @return void
     */
    public function edit(): void
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

    /**
     * 删除帖子
     *
     * @return void
     */
    public function delete(): void
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

    /**
     * 获取编辑数据
     *
     * @return void
     */
    public function getEditData(): void
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

    /**
     * 渲染帖子HTML
     *
     * @param array $post 帖子数据
     * @param array $user 用户数据
     * @param array $images 图片数组
     * @param array $videos 视频数组
     * @param array $attachments 附件数组
     * @param int $postId 帖子ID
     * @return string
     */
    protected function renderPostHtml(array $post, array $user, array $images, array $videos, array $attachments, int $postId): string
    {
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
        $html .= \PostActionHelper::render($post);
        $html .= \CommentHelper::renderCommentBox($postId);
        $html .= '</div></div>';
        
        return $html;
    }

    /**
     * 上传图片
     *
     * @return array
     */
    protected function uploadImages(): array
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

    /**
     * 上传附件
     *
     * @return array
     */
    protected function uploadAttachments(): array
    {
        $attachments = [];
        $files = $_FILES['attachments'];
        $count = count($files['name']);
        $maxSize = Setting::getMaxAttachmentSize() * 1024 * 1024;
        $allowedExtensions = Setting::getAllowedAttachmentExtensions();
        $maxCount = Setting::getMaxAttachmentCount();
        
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
            
            if (function_exists('finfo_open') && isset($allowedMimeTypes[$ext])) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $files['tmp_name'][$i]);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedMimeTypes[$ext])) {
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

    /**
     * 上传视频
     *
     * @return array
     */
    protected function uploadVideos(): array
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

    /**
     * 格式化文件大小
     *
     * @param int $bytes 字节数
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }
    }

    /**
     * 发送@通知
     *
     * @param string $content 内容
     * @param int $postId 帖子ID
     * @param int $senderId 发送者ID
     * @param int|null $commentId 评论ID
     * @return void
     */
    protected function sendMentionNotifications(string $content, int $postId, int $senderId, ?int $commentId = null): void
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
        
        $senderName = $sender['nickname'] ?: $sender['username'];
        
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
}

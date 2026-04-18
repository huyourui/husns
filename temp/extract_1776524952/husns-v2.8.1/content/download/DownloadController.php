<?php
class DownloadController extends Controller
{
    private $postModel;
    private $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->postModel = new PostModel();
        $this->userModel = new UserModel();
    }
    
    public function attachment()
    {
        $id = (int)Helper::get('id');
        $index = (int)Helper::get('index', 0);
        $debug = isset($_GET['debug']);
        
        if (!$id) {
            $this->showError('参数错误');
            return;
        }
        
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $post = $this->postModel->getPost($id, $userId);
        
        if (!$post || $post['status'] != 1) {
            $this->showError('内容不存在');
            return;
        }
        
        $isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
        $guestDownloadAllowed = Setting::isGuestDownloadAllowed();
        
        if (!$isLoggedIn && !$guestDownloadAllowed) {
            $this->showError('请登录后下载附件', '需要登录', Helper::url('user/login'));
            return;
        }
        
        $canDownload = false;
        
        if ($post['user_id'] == $userId) {
            $canDownload = true;
        }
        
        if (!$canDownload && $isLoggedIn) {
            $canDownload = $this->postModel->hasUserCommented($id, $_SESSION['user_id']);
        }
        
        if (!$canDownload && $isLoggedIn) {
            $user = $this->userModel->find($_SESSION['user_id']);
            if ($user && $user['is_admin'] == 1) {
                $canDownload = true;
            }
        }
        
        $hasHideContent = preg_match('/\[hide\]/i', $post['content']);
        if ($hasHideContent && !$canDownload) {
            $this->showError('请先评论后再下载附件', '需要评论', Helper::url('post/detail?id=' . $id));
            return;
        }
        
        if (!$hasHideContent) {
            $canDownload = true;
        }
        
        if (!$canDownload) {
            $this->showError('您没有权限下载此附件');
            return;
        }
        
        $attachments = is_array($post['attachments']) ? $post['attachments'] : (json_decode($post['attachments'], true) ?: []);
        
        if (empty($attachments)) {
            $this->showError('附件列表为空');
            return;
        }
        
        if (!isset($attachments[$index])) {
            $this->showError('附件索引不存在');
            return;
        }
        
        $attachment = $attachments[$index];
        
        if (!isset($attachment['path'])) {
            $this->showError('附件路径不存在');
            return;
        }
        
        $attachmentPath = str_replace('\\', '/', $attachment['path']);
        $filePath = UPLOAD_PATH . $attachmentPath;
        
        if ($debug) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: text/plain; charset=utf-8');
            echo "=== 下载调试信息 ===\n\n";
            echo "POST ID: $id\n";
            echo "INDEX: $index\n";
            echo "USER ID: $userId\n";
            echo "ROOT_PATH: " . ROOT_PATH . "\n";
            echo "UPLOAD_PATH: " . UPLOAD_PATH . "\n";
            echo "附件路径: " . $attachmentPath . "\n";
            echo "完整文件路径: " . $filePath . "\n";
            echo "文件是否存在: " . (file_exists($filePath) ? '是' : '否') . "\n";
            if (file_exists($filePath)) {
                echo "文件大小: " . filesize($filePath) . " bytes\n";
                echo "文件可读: " . (is_readable($filePath) ? '是' : '否') . "\n";
            }
            exit;
        }
        
        if (!file_exists($filePath)) {
            $this->showError('文件不存在');
            return;
        }
        
        if (!is_readable($filePath)) {
            $this->showError('文件不可读');
            return;
        }
        
        $this->outputFile($filePath, $attachment['name']);
    }
    
    private function outputFile($filePath, $fileName)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        set_time_limit(300);
        
        $fileSize = filesize($filePath);
        $encodedName = rawurlencode($fileName);
        
        $mimeType = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeType = finfo_file($finfo, $filePath);
                finfo_close($finfo);
            }
        }
        
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];
        if (isset($mimeTypes[$ext])) {
            $mimeType = $mimeTypes[$ext];
        }
        
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $encodedName . '"; filename*=UTF-8\'\'' . $encodedName);
        header('Content-Length: ' . $fileSize);
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: 0');
        header('X-Content-Type-Options: nosniff');
        header('Accept-Ranges: bytes');
        header('Connection: close');
        
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            $this->showError('无法打开文件');
            return;
        }
        
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        
        fclose($handle);
        exit;
    }
    
    private function showError($message, $title = '提示', $redirectUrl = '')
    {
        $this->view->setLayout('main');
        $this->render('download/error', [
            'title' => $title,
            'message' => $message,
            'redirectUrl' => $redirectUrl
        ]);
    }
}

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
class UpgradeController extends Controller
{
    private $giteeApiUrl = 'https://gitee.com/api/v5/repos/youruihu/husns/releases/latest';
    private $giteeReleasesUrl = 'https://gitee.com/api/v5/repos/youruihu/husns/releases';
    private $downloadPageUrl = 'https://gitee.com/youruihu/husns/releases';
    private $backupDir;
    private $tempDir;

    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('admin');
        $this->backupDir = ROOT_PATH . 'backups' . DIRECTORY_SEPARATOR;
        $this->tempDir = ROOT_PATH . 'temp' . DIRECTORY_SEPARATOR;
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    public function index()
    {
        $this->checkAdmin();
        
        $currentVersion = APP_VERSION;
        $latestVersion = null;
        $hasUpdate = false;
        $releaseInfo = null;
        $error = null;
        
        try {
            $releaseInfo = $this->fetchLatestRelease();
            if ($releaseInfo && isset($releaseInfo['tag_name'])) {
                $latestVersion = ltrim($releaseInfo['tag_name'], 'v');
                $hasUpdate = version_compare($latestVersion, $currentVersion, '>');
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        
        $backups = $this->getBackupList();
        
        $this->render('admin/upgrade', [
            'currentVersion' => $currentVersion,
            'latestVersion' => $latestVersion,
            'hasUpdate' => $hasUpdate,
            'releaseInfo' => $releaseInfo,
            'error' => $error,
            'backups' => $backups,
            'downloadPageUrl' => $this->downloadPageUrl
        ]);
    }

    public function check()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        try {
            $releaseInfo = $this->fetchLatestRelease();
            if (!$releaseInfo || !isset($releaseInfo['tag_name'])) {
                $this->jsonError('无法获取版本信息');
            }
            
            $latestVersion = ltrim($releaseInfo['tag_name'], 'v');
            $currentVersion = APP_VERSION;
            $hasUpdate = version_compare($latestVersion, $currentVersion, '>');
            
            $this->jsonSuccess([
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'has_update' => $hasUpdate,
                'release_name' => $releaseInfo['name'] ?? '',
                'release_body' => $releaseInfo['body'] ?? '',
                'published_at' => $releaseInfo['published_at'] ?? '',
                'download_url' => $this->downloadPageUrl
            ]);
        } catch (Exception $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function backup()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        try {
            $backupName = 'backup_' . date('Ymd_His') . '_' . str_replace('.', '', APP_VERSION);
            $backupPath = $this->backupDir . $backupName;
            
            mkdir($backupPath, 0755, true);
            
            $this->backupDatabase($backupPath);
            
            $this->backupFiles($backupPath);
            
            $this->backupManifest($backupPath, $backupName);
            
            $this->jsonSuccess([
                'backup_name' => $backupName,
                'backup_path' => $backupPath,
                'backup_size' => $this->formatBytes($this->getDirectorySize($backupPath))
            ], '备份创建成功');
        } catch (Exception $e) {
            $this->jsonError('备份失败：' . $e->getMessage());
        }
    }

    public function restore()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $backupName = Helper::post('backup_name');
        if (empty($backupName)) {
            $this->jsonError('请选择要恢复的备份');
        }
        
        $backupPath = $this->backupDir . $backupName;
        if (!is_dir($backupPath)) {
            $this->jsonError('备份不存在');
        }
        
        $manifestFile = $backupPath . DIRECTORY_SEPARATOR . 'manifest.json';
        if (!file_exists($manifestFile)) {
            $this->jsonError('备份文件损坏，缺少manifest.json');
        }
        
        try {
            $manifest = json_decode(file_get_contents($manifestFile), true);
            if (!$manifest) {
                $this->jsonError('备份manifest文件解析失败');
            }
            
            $this->restoreDatabase($backupPath);
            
            $this->restoreFiles($backupPath);
            
            $this->jsonSuccess(null, '恢复成功，请刷新页面');
        } catch (Exception $e) {
            $this->jsonError('恢复失败：' . $e->getMessage());
        }
    }

    public function deleteBackup()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $backupName = Helper::post('backup_name');
        if (empty($backupName)) {
            $this->jsonError('请选择要删除的备份');
        }
        
        $backupPath = $this->backupDir . $backupName;
        if (!is_dir($backupPath)) {
            $this->jsonError('备份不存在');
        }
        
        if (strpos(realpath($backupPath), realpath($this->backupDir)) !== 0) {
            $this->jsonError('非法路径');
        }
        
        try {
            $this->deleteDirectory($backupPath);
            $this->jsonSuccess(null, '备份已删除');
        } catch (Exception $e) {
            $this->jsonError('删除失败：' . $e->getMessage());
        }
    }

    public function upload()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        if (!isset($_FILES['package']) || $_FILES['package']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
                UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
                UPLOAD_ERR_PARTIAL => '文件上传不完整',
                UPLOAD_ERR_NO_FILE => '没有文件被上传',
                UPLOAD_ERR_NO_TMP_DIR => '缺少临时文件夹',
                UPLOAD_ERR_CANT_WRITE => '写入文件失败',
            ];
            $errorCode = $_FILES['package']['error'] ?? UPLOAD_ERR_NO_FILE;
            $this->jsonError($errorMessages[$errorCode] ?? '上传失败');
        }
        
        $file = $_FILES['package'];
        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        
        if (pathinfo($fileName, PATHINFO_EXTENSION) !== 'zip') {
            $this->jsonError('只支持ZIP压缩包格式');
        }
        
        if ($fileSize > 100 * 1024 * 1024) {
            $this->jsonError('文件大小不能超过100MB');
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);
        
        $allowedMimes = ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'];
        if (!in_array($mimeType, $allowedMimes)) {
            $this->jsonError('文件类型不正确');
        }
        
        try {
            $tempFile = $this->tempDir . 'upgrade_' . time() . '.zip';
            if (!move_uploaded_file($fileTmp, $tempFile)) {
                $this->jsonError('文件保存失败');
            }
            
            $this->jsonSuccess([
                'temp_file' => basename($tempFile)
            ], '上传成功');
        } catch (Exception $e) {
            $this->jsonError('上传失败：' . $e->getMessage());
        }
    }

    public function doUpgrade()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $tempFileName = Helper::post('temp_file');
        $autoBackup = Helper::post('auto_backup', 1);
        
        if (empty($tempFileName)) {
            $this->jsonError('请先上传更新包');
        }
        
        $tempFile = $this->tempDir . $tempFileName;
        if (!file_exists($tempFile)) {
            $this->jsonError('更新包不存在，请重新上传');
        }
        
        if (strpos(realpath($tempFile), realpath($this->tempDir)) !== 0) {
            $this->jsonError('非法路径');
        }
        
        try {
            if ($autoBackup) {
                $backupName = 'auto_backup_' . date('Ymd_His') . '_' . str_replace('.', '', APP_VERSION);
                $backupPath = $this->backupDir . $backupName;
                mkdir($backupPath, 0755, true);
                $this->backupDatabase($backupPath);
                $this->backupFiles($backupPath);
                $this->backupManifest($backupPath, $backupName);
            }
            
            $extractDir = $this->tempDir . 'extract_' . time();
            mkdir($extractDir, 0755, true);
            
            $zip = new ZipArchive();
            if ($zip->open($tempFile) !== true) {
                $this->deleteDirectory($extractDir);
                $this->jsonError('无法打开ZIP文件');
            }
            
            $zip->extractTo($extractDir);
            $zip->close();
            
            $sourceDir = $this->findSourceDir($extractDir);
            if (!$sourceDir) {
                $this->deleteDirectory($extractDir);
                $this->jsonError('无效的更新包结构');
            }
            
            $this->validatePackage($sourceDir);
            
            $this->applyUpdate($sourceDir);
            
            $this->deleteDirectory($extractDir);
            unlink($tempFile);
            
            $this->jsonSuccess(null, '更新成功！请刷新页面查看新版本');
        } catch (Exception $e) {
            $this->jsonError('更新失败：' . $e->getMessage());
        }
    }

    private function fetchLatestRelease()
    {
        if (!function_exists('curl_init')) {
            throw new Exception('服务器需要cURL扩展');
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->giteeApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'HuSNS/' . APP_VERSION);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('网络请求失败：' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('获取版本信息失败，HTTP状态码：' . $httpCode);
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('解析版本信息失败');
        }
        
        return $data;
    }

    private function backupDatabase($backupPath)
    {
        $dbFile = $backupPath . DIRECTORY_SEPARATOR . 'database.sql';
        $dbName = DB_NAME;
        $dbPrefix = DB_PREFIX;
        
        $tables = $this->db->fetchAll("SHOW TABLES");
        $tableKey = 'Tables_in_' . $dbName;
        
        $sql = "-- HuSNS Database Backup\n";
        $sql .= "-- Version: " . APP_VERSION . "\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET NAMES utf8mb4;\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
            $tableName = $table[$tableKey];
            if (strpos($tableName, $dbPrefix) !== 0) {
                continue;
            }
            
            $createTable = $this->db->fetch("SHOW CREATE TABLE `{$tableName}`");
            $sql .= "-- Table: {$tableName}\n";
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sql .= $createTable['Create Table'] . ";\n\n";
            
            $rows = $this->db->fetchAll("SELECT * FROM `{$tableName}`");
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $sql .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        if (file_put_contents($dbFile, $sql) === false) {
            throw new Exception('数据库备份文件写入失败');
        }
    }

    private function backupFiles($backupPath)
    {
        $filesDir = $backupPath . DIRECTORY_SEPARATOR . 'files';
        mkdir($filesDir, 0755, true);
        
        $excludeDirs = ['backups', 'temp', 'logs', 'uploads', 'node_modules', '.git', 'vendor'];
        $excludeFiles = ['.env', '.gitignore', '.gitkeep'];
        
        $this->copyDirectory(ROOT_PATH, $filesDir, $excludeDirs, $excludeFiles);
    }

    private function backupManifest($backupPath, $backupName)
    {
        $manifest = [
            'name' => $backupName,
            'version' => APP_VERSION,
            'created_at' => time(),
            'created_at_text' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        ];
        
        file_put_contents(
            $backupPath . DIRECTORY_SEPARATOR . 'manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function copyDirectory($src, $dst, $excludeDirs = [], $excludeFiles = [])
    {
        $dir = opendir($src);
        if (!$dir) {
            throw new Exception("无法打开目录：{$src}");
        }
        
        if (!is_dir($dst)) {
            if (!mkdir($dst, 0755, true)) {
                throw new Exception("无法创建目录：{$dst}");
            }
        }
        
        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $srcPath = $src . DIRECTORY_SEPARATOR . $file;
            $dstPath = $dst . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($srcPath)) {
                if (in_array($file, $excludeDirs)) {
                    continue;
                }
                $this->copyDirectory($srcPath, $dstPath, $excludeDirs, $excludeFiles);
            } else {
                if (in_array($file, $excludeFiles)) {
                    continue;
                }
                
                if (file_exists($dstPath)) {
                    if (!is_writable($dstPath)) {
                        @chmod($dstPath, 0644);
                    }
                    if (!is_writable($dstPath)) {
                        @unlink($dstPath);
                    }
                }
                
                if (!copy($srcPath, $dstPath)) {
                    throw new Exception("无法复制文件：{$file}");
                }
                
                @chmod($dstPath, 0644);
            }
        }
        
        closedir($dir);
    }

    private function restoreDatabase($backupPath)
    {
        $dbFile = $backupPath . DIRECTORY_SEPARATOR . 'database.sql';
        if (!file_exists($dbFile)) {
            throw new Exception('数据库备份文件不存在');
        }
        
        $sql = file_get_contents($dbFile);
        
        $statements = array_filter(array_map('trim', explode(";\n", $sql)));
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            $this->db->query($statement);
        }
    }

    private function restoreFiles($backupPath)
    {
        $filesDir = $backupPath . DIRECTORY_SEPARATOR . 'files';
        if (!is_dir($filesDir)) {
            throw new Exception('文件备份目录不存在');
        }
        
        $excludeDirs = ['backups', 'temp'];
        $excludeFiles = ['.env'];
        
        $this->copyDirectory($filesDir, ROOT_PATH, $excludeDirs, $excludeFiles);
    }

    private function getBackupList()
    {
        $backups = [];
        
        if (!is_dir($this->backupDir)) {
            return $backups;
        }
        
        $dirs = scandir($this->backupDir, SCANDIR_SORT_DESCENDING);
        
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $backupPath = $this->backupDir . $dir;
            if (!is_dir($backupPath)) {
                continue;
            }
            
            $manifestFile = $backupPath . DIRECTORY_SEPARATOR . 'manifest.json';
            if (file_exists($manifestFile)) {
                $manifest = json_decode(file_get_contents($manifestFile), true);
                $backups[] = [
                    'name' => $dir,
                    'version' => $manifest['version'] ?? 'Unknown',
                    'created_at' => $manifest['created_at'] ?? 0,
                    'created_at_text' => $manifest['created_at_text'] ?? 'Unknown',
                    'size' => $this->formatBytes($this->getDirectorySize($backupPath))
                ];
            }
        }
        
        return $backups;
    }

    private function findSourceDir($extractDir)
    {
        $dirs = scandir($extractDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $path = $extractDir . DIRECTORY_SEPARATOR . $dir;
            if (is_dir($path)) {
                if (file_exists($path . DIRECTORY_SEPARATOR . 'index.php')) {
                    return $path;
                }
                
                $subDir = $this->findSourceDir($path);
                if ($subDir) {
                    return $subDir;
                }
            }
        }
        
        if (file_exists($extractDir . DIRECTORY_SEPARATOR . 'index.php')) {
            return $extractDir;
        }
        
        return null;
    }

    private function validatePackage($sourceDir)
    {
        $requiredFiles = ['index.php'];
        foreach ($requiredFiles as $file) {
            if (!file_exists($sourceDir . DIRECTORY_SEPARATOR . $file)) {
                throw new Exception('更新包缺少必要文件：' . $file);
            }
        }
        
        $indexContent = file_get_contents($sourceDir . DIRECTORY_SEPARATOR . 'index.php');
        if (strpos($indexContent, 'HuSNS') === false) {
            throw new Exception('更新包不是有效的HuSNS程序包');
        }
    }

    private function applyUpdate($sourceDir)
    {
        $excludeDirs = ['backups', 'temp', 'uploads'];
        $excludeFiles = ['.env', 'config.php'];
        
        $this->copyDirectory($sourceDir, ROOT_PATH, $excludeDirs, $excludeFiles);
        
        $upgradeFile = ROOT_PATH . 'core' . DIRECTORY_SEPARATOR . 'Upgrade.php';
        if (file_exists($upgradeFile)) {
            require_once $upgradeFile;
            if (class_exists('Upgrade')) {
                $upgrade = new Upgrade($this->db);
                $upgrade->run();
            }
        }
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object === '.' || $object === '..') {
                continue;
            }
            
            $path = $dir . DIRECTORY_SEPARATOR . $object;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    private function getDirectorySize($dir)
    {
        $size = 0;
        
        if (!is_dir($dir)) {
            return $size;
        }
        
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object === '.' || $object === '..') {
                continue;
            }
            
            $path = $dir . DIRECTORY_SEPARATOR . $object;
            if (is_dir($path)) {
                $size += $this->getDirectorySize($path);
            } else {
                $size += filesize($path);
            }
        }
        
        return $size;
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
}

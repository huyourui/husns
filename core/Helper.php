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
class Helper
{
    public static function url($path = '')
    {
        $baseUrl = defined('SITE_URL') && SITE_URL ? SITE_URL : self::getBaseUrl();
        
        if (empty($path)) {
            return $baseUrl . '/';
        }
        
        $queryString = '';
        if (strpos($path, '?') !== false) {
            $parts = explode('?', $path, 2);
            $path = $parts[0];
            $queryString = '&' . $parts[1];
        }
        
        if (defined('URL_REWRITE') && URL_REWRITE) {
            return $baseUrl . '/' . ltrim($path, '/') . ($queryString ? '?' . substr($queryString, 1) : '');
        }
        
        return $baseUrl . '/?r=' . ltrim($path, '/') . $queryString;
    }

    public static function getBaseUrl()
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = dirname($scriptName);
        $basePath = str_replace('\\', '/', $basePath);
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        return rtrim($basePath, '/');
    }

    public static function redirect($url, $delay = 0)
    {
        if ($delay > 0) {
            header('Refresh: ' . $delay . '; url=' . $url);
        } else {
            header('Location: ' . $url);
        }
        exit;
    }

    public static function asset($path)
    {
        $baseUrl = self::getBaseUrl();
        if ($baseUrl === '') {
            return '/static/' . ltrim($path, '/');
        }
        return $baseUrl . '/static/' . ltrim($path, '/');
    }

    public static function uploadUrl($path)
    {
        $baseUrl = self::getBaseUrl();
        if ($baseUrl === '') {
            return '/uploads/' . ltrim($path, '/');
        }
        return $baseUrl . '/uploads/' . ltrim($path, '/');
    }

    public static function formatTime($timestamp)
    {
        $time = time() - $timestamp;
        if ($time < 60) {
            return '刚刚';
        } elseif ($time < 3600) {
            return floor($time / 60) . '分钟前';
        } elseif ($time < 86400) {
            return floor($time / 3600) . '小时前';
        } elseif ($time < 2592000) {
            return floor($time / 86400) . '天前';
        } else {
            return date('Y-m-d H:i', $timestamp);
        }
    }

    public static function truncate($string, $length = 100, $suffix = '...')
    {
        if (mb_strlen($string, 'UTF-8') <= $length) {
            return $string;
        }
        return mb_substr($string, 0, $length, 'UTF-8') . $suffix;
    }

    public static function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    public static function csrfField()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
    }

    public static function verifyCsrf()
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        $headerToken = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
        return hash_equals($_SESSION['csrf_token'], $token) || hash_equals($_SESSION['csrf_token'], $headerToken);
    }

    public static function json($data, $code = 200)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function jsonSuccess($data = null, $message = '操作成功')
    {
        self::json(['code' => 0, 'message' => $message, 'data' => $data]);
    }

    public static function jsonError($message = '操作失败', $code = 1)
    {
        self::json(['code' => $code, 'message' => $message, 'data' => null]);
    }

    /**
     * 获取客户端真实IP地址
     * 
     * 安全优化：
     * - 仅在可信代理环境下读取 X-Forwarded-For
     * - 不信任 HTTP_CLIENT_IP（可被伪造）
     * - 验证IP格式有效性
     * 
     * @return string 客户端IP地址
     */
    public static function getIp()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        $trustedProxies = ['127.0.0.1', '::1'];
        
        if (defined('TRUSTED_PROXIES') && TRUSTED_PROXIES) {
            $trustedProxies = array_merge($trustedProxies, explode(',', TRUSTED_PROXIES));
        }
        
        if (in_array($ip, $trustedProxies)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $clientIp = trim($ips[0]);
                
                if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                    $ip = $clientIp;
                }
            }
        }
        
        if ($ip === '::1') {
            return '127.0.0.1';
        }
        
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        return '127.0.0.1';
    }

    public static function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public static function get($key, $default = '')
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    public static function post($key, $default = '')
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    public static function setFlash($type, $message)
    {
        $_SESSION['flash'][$type] = $message;
    }

    public static function getFlash($type)
    {
        $message = isset($_SESSION['flash'][$type]) ? $_SESSION['flash'][$type] : null;
        unset($_SESSION['flash'][$type]);
        return $message;
    }

    public static function hasFlash($type)
    {
        return isset($_SESSION['flash'][$type]);
    }

    /**
     * 生成用户头像HTML
     * 
     * 优化后的逻辑：
     * - 优先检测头像文件是否存在
     * - 文件不存在时自动显示首字符头像
     * - 支持多种尺寸：small(32px)、default(48px)、large(80px) 或自定义像素值
     * 
     * @param string|null $avatar 头像路径
     * @param string $username 用户名（用于生成首字符头像）
     * @param string|int $size 尺寸：small/default/large 或像素值
     * @return string HTML代码
     */
    public static function avatar($avatar, $username, $size = 'default')
    {
        if (is_numeric($size)) {
            $pixelSize = (int)$size;
            $imgStyle = "width:{$pixelSize}px;height:{$pixelSize}px;border-radius:50%;object-fit:cover;";
            $spanStyle = "display:inline-block;width:{$pixelSize}px;height:{$pixelSize}px;border-radius:50%;text-align:center;line-height:{$pixelSize}px;font-size:" . round($pixelSize * 0.4) . "px;font-weight:500;";
        } else {
            $sizes = [
                'small' => ['img' => 'width:32px;height:32px;', 'span' => ['size' => '32px', 'font' => '14px', 'line' => '32px']],
                'default' => ['img' => 'width:48px;height:48px;', 'span' => ['size' => '48px', 'font' => '20px', 'line' => '48px']],
                'large' => ['img' => 'width:80px;height:80px;', 'span' => ['size' => '80px', 'font' => '32px', 'line' => '80px']],
            ];
            $s = $sizes[$size] ?? $sizes['default'];
            $imgStyle = $s['img'] . 'border-radius:50%;object-fit:cover;';
            $spanStyle = "display:inline-block;width:{$s['span']['size']};height:{$s['span']['size']};border-radius:50%;text-align:center;line-height:{$s['span']['line']};font-size:{$s['span']['font']};font-weight:500;";
        }
        
        if ($avatar && self::avatarFileExists($avatar)) {
            $colors = self::getAvatarColor($username);
            $firstChar = self::getFirstChar($username);
            return '<img src="' . self::uploadUrl($avatar) . '" alt="' . htmlspecialchars($username) . '" style="' . $imgStyle . '" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'inline-block\';"><span class="avatar-default" style="' . $spanStyle . 'background:' . $colors['bg'] . ';color:' . $colors['text'] . ';display:none;">' . htmlspecialchars($firstChar) . '</span>';
        }
        
        $firstChar = self::getFirstChar($username);
        $colors = self::getAvatarColor($username);
        
        return '<span class="avatar-default" style="' . $spanStyle . 'background:' . $colors['bg'] . ';color:' . $colors['text'] . ';">' . htmlspecialchars($firstChar) . '</span>';
    }

    /**
     * 检测头像文件是否存在
     * 
     * @param string $avatar 头像路径
     * @return bool 文件是否存在
     */
    private static function avatarFileExists($avatar)
    {
        if (empty($avatar)) {
            return false;
        }
        
        $filePath = UPLOAD_PATH . $avatar;
        return file_exists($filePath);
    }

    /**
     * 获取用户名首字符
     * 
     * @param string $username 用户名
     * @return string 首字符
     */
    private static function getFirstChar($username)
    {
        if (empty($username)) {
            return '?';
        }
        return mb_substr($username, 0, 1, 'UTF-8');
    }

    /**
     * 根据用户名生成头像颜色
     * 
     * @param string $username 用户名
     * @return array 包含背景色和文字色的数组
     */
    private static function getAvatarColor($username)
    {
        $colors = [
            ['bg' => '#667eea', 'text' => '#ffffff'],
            ['bg' => '#764ba2', 'text' => '#ffffff'],
            ['bg' => '#f093fb', 'text' => '#ffffff'],
            ['bg' => '#f5576c', 'text' => '#ffffff'],
            ['bg' => '#4facfe', 'text' => '#ffffff'],
            ['bg' => '#00f2fe', 'text' => '#ffffff'],
            ['bg' => '#43e97b', 'text' => '#ffffff'],
            ['bg' => '#38f9d7', 'text' => '#ffffff'],
            ['bg' => '#fa709a', 'text' => '#ffffff'],
            ['bg' => '#fee140', 'text' => '#333333'],
            ['bg' => '#30cfd0', 'text' => '#ffffff'],
            ['bg' => '#a8edea', 'text' => '#333333'],
            ['bg' => '#ff9a9e', 'text' => '#ffffff'],
            ['bg' => '#ffecd2', 'text' => '#333333'],
            ['bg' => '#a1c4fd', 'text' => '#ffffff'],
            ['bg' => '#c2e9fb', 'text' => '#333333'],
        ];
        
        $hash = 0;
        for ($i = 0; $i < strlen($username); $i++) {
            $hash = ord($username[$i]) + (($hash << 5) - $hash);
        }
        
        $index = abs($hash) % count($colors);
        return $colors[$index];
    }
    
    public static function formatNumber($num)
    {
        if ($num >= 10000) {
            return round($num / 10000, 1) . 'w';
        } elseif ($num >= 1000) {
            return round($num / 1000, 1) . 'k';
        }
        return $num;
    }

    /**
     * 计算图片网格布局
     * 
     * 根据图片数量自动计算最佳布局方式：
     * - 1张：1行1张（大图）
     * - 2张：1行2张
     * - 3张：1行3张
     * - 4张：2行，每行2张
     * - 5张：第一行3张，第二行2张
     * - 6张：2行，每行3张
     * - 7张及以上：每行最多3张，自动换行
     * 
     * @param int $count 图片数量
     * @return array 每行的图片数量数组，如 [3, 2] 表示第一行3张，第二行2张
     */
    public static function calculateImageGrid($count)
    {
        if ($count <= 0) {
            return [];
        }

        // 特殊情况处理
        switch ($count) {
            case 1:
                return [1];
            case 2:
                return [2];
            case 3:
                return [3];
            case 4:
                return [2, 2];
            case 5:
                return [3, 2];
            case 6:
                return [3, 3];
            case 7:
                return [3, 2, 2];
            case 8:
                return [3, 3, 2];
            case 9:
                return [3, 3, 3];
        }

        // 10张及以上：每行3张
        $layouts = [];
        $remaining = $count;
        
        while ($remaining > 0) {
            if ($remaining >= 3) {
                $layouts[] = 3;
                $remaining -= 3;
            } elseif ($remaining === 2) {
                $layouts[] = 2;
                $remaining -= 2;
            } else {
                $layouts[] = 1;
                $remaining -= 1;
            }
        }

        return $layouts;
    }

    /**
     * 渲染图片网格HTML
     * 
     * 根据图片数量自动生成响应式网格布局
     * 
     * @param array $images 图片路径数组
     * @param string $uploadBaseUrl 上传文件基础URL
     * @return string HTML代码
     */
    public static function renderImageGrid($images, $uploadBaseUrl = '')
    {
        if (empty($images) || !is_array($images)) {
            return '';
        }

        $count = count($images);
        $layouts = self::calculateImageGrid($count);
        
        $allImages = [];
        foreach ($images as $image) {
            $allImages[] = $uploadBaseUrl ? $uploadBaseUrl . '/' . $image : self::uploadUrl($image);
        }
        $imagesJson = htmlspecialchars(json_encode($allImages), ENT_QUOTES, 'UTF-8');
        
        $html = '<div class="image-grid image-grid-' . $count . '">';
        
        $imageIndex = 0;
        foreach ($layouts as $rowIndex => $cols) {
            $html .= '<div class="image-row image-row-' . $cols . '">';
            
            for ($i = 0; $i < $cols && $imageIndex < $count; $i++) {
                $image = $images[$imageIndex];
                $imageUrl = $allImages[$imageIndex];
                
                $html .= '<div class="image-item" onclick="previewImage(this.querySelector(\'img\'), ' . $imageIndex . ', ' . $imagesJson . ')">';
                $html .= '<img src="' . htmlspecialchars($imageUrl) . '" alt="" loading="lazy">';
                $html .= '</div>';
                
                $imageIndex++;
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    public static function parseEmojis($content)
    {
        static $emojiMap = null;
        
        if ($emojiMap === null) {
            $mapFile = ROOT_PATH . '/static/emoji/emoji_map.json';
            if (file_exists($mapFile)) {
                $emojiMap = json_decode(file_get_contents($mapFile), true);
            } else {
                $emojiMap = [];
            }
        }
        
        if (empty($emojiMap)) {
            return $content;
        }
        
        foreach ($emojiMap as $code => $filename) {
            $localUrl = self::asset('emoji/' . $filename);
            $content = str_replace($code, '<img class="emoji-img-inline" src="' . $localUrl . '" alt="' . $code . '" title="' . $code . '">', $content);
        }
        
        return $content;
    }

    /**
     * 解析微博内容（统一处理）
     * 
     * 使用分词机制：先提取所有特殊标记，只对普通文本转义，再还原HTML
     * 
     * @param string $content 原始内容
     * @param string $topicUrl 话题链接模板（可选）
     * @param string $userUrl 用户链接模板（可选）
     * @return string 解析后的内容
     */
    public static function parseContent($content, $topicUrl = null, $userUrl = null)
    {
        if ($topicUrl === null) {
            $topicUrl = self::url('post/topic?keyword=$1');
        }
        if ($userUrl === null) {
            $userUrl = self::url('user/profile?username=$1');
        }

        // 将内容分割成数组，特殊内容保持原样，普通文本进行转义
        $parts = [];
        $offset = 0;
        $contentLength = mb_strlen($content, 'UTF-8');

        // 定义所有需要匹配的模式
        $patterns = [
            'url' => '/https?:\/\/[^\s<]+/i',
            'topic' => '/#([^#\s]+)#/',
            'user' => '/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|$|[,，。！!?？、；;:：])/u'
        ];

        // 找到所有匹配的位置
        $allMatches = [];
        foreach ($patterns as $type => $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $idx => $match) {
                    $allMatches[] = [
                        'type' => $type,
                        'text' => $match[0],
                        'pos' => $match[1],
                        'length' => strlen($match[0]),
                        'capture' => isset($matches[1][$idx]) ? $matches[1][$idx][0] : null
                    ];
                }
            }
        }

        // 按位置排序
        usort($allMatches, function($a, $b) {
            return $a['pos'] - $b['pos'];
        });

        // 去重（处理重叠的匹配，优先保留前面的）
        $filteredMatches = [];
        $lastEnd = 0;
        foreach ($allMatches as $match) {
            if ($match['pos'] >= $lastEnd) {
                $filteredMatches[] = $match;
                $lastEnd = $match['pos'] + $match['length'];
            }
        }

        // 构建结果
        $result = '';
        $currentPos = 0;

        foreach ($filteredMatches as $match) {
            // 添加前面的普通文本（需要转义）
            if ($match['pos'] > $currentPos) {
                $normalText = substr($content, $currentPos, $match['pos'] - $currentPos);
                $result .= Security::escape($normalText);
            }

            // 添加特殊内容的HTML链接（不转义）
            switch ($match['type']) {
                case 'url':
                    $url = Security::escape($match['text']);
                    $result .= '<a href="' . $url . '" target="_blank" rel="noopener">' . $url . '</a>';
                    break;
                case 'topic':
                    $topicName = $match['capture'];
                    $result .= '<a href="' . str_replace('$1', urlencode($topicName), $topicUrl) . '">#' . Security::escape($topicName) . '#</a>';
                    break;
                case 'user':
                    $username = $match['capture'];
                    $result .= '<a href="' . str_replace('$1', urlencode($username), $userUrl) . '">@' . Security::escape($username) . '</a>';
                    break;
            }

            $currentPos = $match['pos'] + $match['length'];
        }

        // 添加剩余普通文本
        if ($currentPos < strlen($content)) {
            $result .= Security::escape(substr($content, $currentPos));
        }

        // 解析表情
        $result = self::parseEmojis($result);

        return $result;
    }
    
    public static function getEmojiList()
    {
        static $emojiMap = null;
        
        if ($emojiMap === null) {
            $mapFile = ROOT_PATH . '/static/emoji/emoji_map.json';
            if (file_exists($mapFile)) {
                $emojiMap = json_decode(file_get_contents($mapFile), true);
            } else {
                $emojiMap = [];
            }
        }
        
        return $emojiMap;
    }
    
    /**
     * 解析@用户
     * 
     * 将@username转换为可点击的用户链接
     * 支持原始@符号和HTML实体@
     * 
     * @param string $content 内容
     * @return string 解析后的内容
     */
    public static function parseMentions($content)
    {
        $content = preg_replace_callback(
            '/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|$|[,，。！!?？、；;:：])/u',
            function($matches) {
                $username = $matches[1];
                $url = self::url('user/profile?username=' . urlencode($username));
                return '<a href="' . $url . '" class="mention" data-username="' . htmlspecialchars($username) . '">@' . htmlspecialchars($username) . '</a>';
            },
            $content
        );
        
        $content = preg_replace_callback(
            '/&#64;([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|$|[,，。！!?？、；;:：])/u',
            function($matches) {
                $username = $matches[1];
                $url = self::url('user/profile?username=' . urlencode($username));
                return '<a href="' . $url . '" class="mention" data-username="' . htmlspecialchars($username) . '">@' . htmlspecialchars($username) . '</a>';
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * 提取@用户列表
     * 
     * 从内容中提取所有被@的用户名
     * 
     * @param string $content 内容
     * @return array 用户名数组
     */
    public static function extractMentions($content)
    {
        preg_match_all('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|$|[,，。！!?？、；;:：])/u', $content, $matches);
        return isset($matches[1]) ? array_unique($matches[1]) : [];
    }
    
    /**
     * 发送@通知
     * 
     * 给被@的用户发送通知
     * 
     * @param array $userIds 用户ID数组
     * @param int $fromUserId 发送者ID
     * @param int $postId 动态ID
     * @param int $commentId 评论ID
     * @param string $content 内容
     */
    public static function sendMentionNotifications($userIds, $fromUserId, $postId, $commentId, $content)
    {
        if (empty($userIds)) {
            return;
        }
        
        $db = Database::getInstance();
        $notificationModel = new NotificationModel();
        
        foreach ($userIds as $userId) {
            if ($userId == $fromUserId) {
                continue;
            }
            
            $notificationModel->send(
                $userId,
                'mention',
                '有人提到了你',
                $content,
                [
                    'post_id' => $postId,
                    'comment_id' => $commentId,
                    'from_user_id' => $fromUserId
                ]
            );
        }
    }

    /**
     * 检测是否为移动端设备
     * 
     * @return bool
     */
    public static function isMobile()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        // PC端浏览器关键字（包含这些关键字则不是移动端）
        $pcKeywords = ['Windows NT', 'Macintosh', 'X11', 'Linux x86_64'];
        $isPC = false;
        foreach ($pcKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                $isPC = true;
                break;
            }
        }

        $mobileKeywords = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'Windows Phone',
            'BlackBerry', 'webOS', 'Symbian', 'Opera Mini', 'IEMobile',
            'HTC', 'Nokia', 'Samsung', 'SonyEricsson', 'Motorola',
            'Kindle', 'Silk'
        ];

        // 国内主流移动端浏览器（仅在非PC端时检测）
        $mobileBrowsers = ['MQQBrowser', 'MicroMessenger', 'WeiBo', 'UCBrowser', 'Quark', 'Baidu', 'Sogou', 'LieBao'];

        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                if ($keyword === 'iPad' && stripos($userAgent, 'Macintosh') !== false) {
                    continue;
                }
                return true;
            }
        }

        // 对于国内浏览器，仅在非PC端时判定为移动端
        if (!$isPC) {
            foreach ($mobileBrowsers as $keyword) {
                if (stripos($userAgent, $keyword) !== false) {
                    return true;
                }
            }
        }

        if (isset($_SERVER['HTTP_ACCEPT'])) {
            if (strpos($_SERVER['HTTP_ACCEPT'], 'application/vnd.wap.xhtml+xml') !== false) {
                return true;
            }
        }

        if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
            return true;
        }

        if (isset($_SERVER['ALL_HTTP']) && stripos($_SERVER['ALL_HTTP'], 'OperaMini') !== false) {
            return true;
        }

        return false;
    }

    /**
     * 检测是否为平板设备
     * 
     * @return bool
     */
    public static function isTablet()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $tabletKeywords = ['iPad', 'Android', 'Kindle', 'Silk', 'PlayBook', 'Tablet'];

        if (stripos($userAgent, 'iPad') !== false) {
            return true;
        }

        if (stripos($userAgent, 'Android') !== false && stripos($userAgent, 'Mobile') === false) {
            return true;
        }

        foreach ($tabletKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}

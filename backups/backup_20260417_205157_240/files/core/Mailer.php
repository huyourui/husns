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
class Mailer
{
    private static $config = null;

    private static function loadConfig()
    {
        if (self::$config === null) {
            self::$config = [
                'enabled' => (int)Setting::get('mail_enabled', 0) === 1,
                'driver' => Setting::get('mail_driver', 'smtp'),
                'host' => Setting::get('mail_host', 'smtp.qq.com'),
                'port' => (int)Setting::get('mail_port', 465),
                'encryption' => Setting::get('mail_encryption', 'ssl'),
                'username' => Setting::get('mail_username', ''),
                'password' => Setting::get('mail_password', ''),
                'from_address' => Setting::get('mail_from_address', ''),
                'from_name' => Setting::get('mail_from_name', Setting::getSiteName()),
            ];
        }
        return self::$config;
    }

    public static function isEnabled()
    {
        $config = self::loadConfig();
        return $config['enabled'] && !empty($config['host']) && !empty($config['username']);
    }

    public static function send($to, $subject, $body, $options = [])
    {
        $config = self::loadConfig();
        
        if (!$config['enabled']) {
            return ['success' => false, 'message' => '邮件服务未启用'];
        }

        if (empty($config['host']) || empty($config['username'])) {
            return ['success' => false, 'message' => '邮件服务配置不完整'];
        }

        $fromName = $options['from_name'] ?? $config['from_name'];
        $fromAddress = $options['from_address'] ?? $config['from_address'] ?: $config['username'];
        $replyTo = $options['reply_to'] ?? null;
        $cc = $options['cc'] ?? [];
        $bcc = $options['bcc'] ?? [];
        $attachments = $options['attachments'] ?? [];
        $isHtml = $options['is_html'] ?? true;

        $headers = [];
        $headers[] = 'From: ' . self::encodeAddress($fromAddress, $fromName);
        $headers[] = 'Reply-To: ' . self::encodeAddress($replyTo ?: $fromAddress, $replyTo ? '' : $fromName);
        
        if (!empty($cc)) {
            $ccList = [];
            foreach ($cc as $email => $name) {
                $ccList[] = self::encodeAddress($email, $name);
            }
            $headers[] = 'Cc: ' . implode(', ', $ccList);
        }
        
        if (!empty($bcc)) {
            $bccList = [];
            foreach ($bcc as $email => $name) {
                $bccList[] = self::encodeAddress($email, $name);
            }
            $headers[] = 'Bcc: ' . implode(', ', $bccList);
        }

        $boundary = md5(time() . rand());
        
        if ($isHtml) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }

        $headers[] = 'X-Mailer: HuSNS Mailer';

        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $socket = @fsockopen(
            ($config['encryption'] === 'ssl' ? 'ssl://' : '') . $config['host'],
            $config['port'],
            $errno,
            $errstr,
            10
        );

        if (!$socket) {
            return ['success' => false, 'message' => '无法连接邮件服务器: ' . $errstr];
        }

        $response = fgets($socket);
        if (strpos($response, '220') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => '邮件服务器响应异常'];
        }

        $localhost = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        fputs($socket, "EHLO {$localhost}\r\n");
        $response = self::readResponse($socket);
        
        if (strpos($response, '250') === false) {
            fputs($socket, "HELO {$localhost}\r\n");
            $response = self::readResponse($socket);
        }

        if ($config['encryption'] === 'tls') {
            fputs($socket, "STARTTLS\r\n");
            $response = self::readResponse($socket);
            if (strpos($response, '220') === 0) {
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fputs($socket, "EHLO {$localhost}\r\n");
                $response = self::readResponse($socket);
            }
        }

        fputs($socket, "AUTH LOGIN\r\n");
        $response = self::readResponse($socket);
        if (strpos($response, '334') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => '邮件服务器不支持认证'];
        }

        fputs($socket, base64_encode($config['username']) . "\r\n");
        $response = self::readResponse($socket);
        if (strpos($response, '334') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => '邮箱用户名验证失败'];
        }

        fputs($socket, base64_encode($config['password']) . "\r\n");
        $response = self::readResponse($socket);
        if (strpos($response, '235') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => '邮箱密码验证失败'];
        }

        fputs($socket, "MAIL FROM: <{$fromAddress}>\r\n");
        $response = self::readResponse($socket);
        if (strpos($response, '250') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => '发件人地址设置失败'];
        }

        $recipients = is_array($to) ? $to : [$to];
        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            fputs($socket, "RCPT TO: <{$email}>\r\n");
            $response = self::readResponse($socket);
        }

        fputs($socket, "DATA\r\n");
        $response = self::readResponse($socket);
        if (strpos($response, '354') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => '邮件数据发送失败'];
        }

        $emailContent = '';
        foreach ($headers as $header) {
            $emailContent .= $header . "\r\n";
        }
        $emailContent .= "Subject: {$subject}\r\n";
        $emailContent .= "To: " . (is_array($to) ? implode(', ', array_map(function($t) {
            return is_array($t) ? self::encodeAddress($t['email'], $t['name'] ?? '') : $t;
        }, $to)) : $to) . "\r\n";
        $emailContent .= "\r\n";
        $emailContent .= $body . "\r\n";
        $emailContent .= ".\r\n";

        fputs($socket, $emailContent);
        $response = self::readResponse($socket);
        if (strpos($response, '250') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => '邮件发送失败'];
        }

        fputs($socket, "QUIT\r\n");
        fclose($socket);

        return ['success' => true, 'message' => '邮件发送成功'];
    }

    private static function readResponse($socket)
    {
        $response = '';
        while ($line = fgets($socket)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }

    private static function encodeAddress($email, $name = '')
    {
        if (empty($name)) {
            return $email;
        }
        return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
    }

    public static function sendTemplate($to, $subject, $templateName, $data = [], $options = [])
    {
        $template = self::loadTemplate($templateName, $data);
        
        if ($template === null) {
            return ['success' => false, 'message' => '邮件模板不存在'];
        }

        $siteName = Setting::getSiteName();
        $subject = str_replace('{site_name}', $siteName, $subject);
        $subject = str_replace('{sitename}', $siteName, $subject);

        return self::send($to, $subject, $template, $options);
    }

    private static function loadTemplate($name, $data = [])
    {
        $templates = [
            'verification_code' => self::getVerificationCodeTemplate($data),
            'welcome' => self::getWelcomeTemplate($data),
            'password_reset' => self::getPasswordResetTemplate($data),
            'notification' => self::getNotificationTemplate($data),
        ];

        return $templates[$name] ?? null;
    }

    private static function getVerificationCodeTemplate($data)
    {
        $code = $data['code'] ?? '';
        $siteName = Setting::getSiteName();
        $expireMinutes = $data['expire_minutes'] ?? 10;
        $purpose = $data['purpose'] ?? '验证';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; }
        .content { padding: 30px; }
        .code-box { background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
        .code { font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #1e293b; }
        .info { color: #64748b; font-size: 14px; line-height: 1.6; }
        .footer { background: #f8fafc; padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$siteName}</h1>
        </div>
        <div class="content">
            <p class="info">您好！</p>
            <p class="info">您正在进行{$purpose}操作，验证码如下：</p>
            <div class="code-box">
                <span class="code">{$code}</span>
            </div>
            <p class="info">验证码有效期为 {$expireMinutes} 分钟，请尽快使用。</p>
            <p class="info">如果这不是您本人的操作，请忽略此邮件。</p>
        </div>
        <div class="footer">
            此邮件由系统自动发送，请勿直接回复。
        </div>
    </div>
</body>
</html>
HTML;
    }

    private static function getWelcomeTemplate($data)
    {
        $siteName = Setting::getSiteName();
        $username = $data['username'] ?? '用户';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; }
        .content { padding: 30px; }
        .info { color: #64748b; font-size: 14px; line-height: 1.8; }
        .footer { background: #f8fafc; padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$siteName}</h1>
        </div>
        <div class="content">
            <p class="info">亲爱的 <strong>{$username}</strong>，欢迎加入！</p>
            <p class="info">感谢您注册成为 {$siteName} 的一员。在这里，您可以：</p>
            <p class="info">• 发布动态，分享生活点滴<br>• 关注好友，了解最新动态<br>• 参与互动，结交志同道合的朋友</p>
            <p class="info">祝您在这里度过愉快的时光！</p>
        </div>
        <div class="footer">
            此邮件由系统自动发送，请勿直接回复。
        </div>
    </div>
</body>
</html>
HTML;
    }

    private static function getPasswordResetTemplate($data)
    {
        $siteName = Setting::getSiteName();
        $resetLink = $data['reset_link'] ?? '';
        $expireMinutes = $data['expire_minutes'] ?? 30;

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; }
        .content { padding: 30px; }
        .info { color: #64748b; font-size: 14px; line-height: 1.6; }
        .btn { display: inline-block; background: #1e293b; color: #fff; padding: 12px 30px; border-radius: 6px; text-decoration: none; margin: 20px 0; }
        .footer { background: #f8fafc; padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$siteName}</h1>
        </div>
        <div class="content">
            <p class="info">您好！</p>
            <p class="info">您正在申请重置密码，请点击下方按钮完成操作：</p>
            <p style="text-align: center;">
                <a href="{$resetLink}" class="btn">重置密码</a>
            </p>
            <p class="info">链接有效期为 {$expireMinutes} 分钟。</p>
            <p class="info">如果这不是您本人的操作，请忽略此邮件。</p>
        </div>
        <div class="footer">
            此邮件由系统自动发送，请勿直接回复。
        </div>
    </div>
</body>
</html>
HTML;
    }

    private static function getNotificationTemplate($data)
    {
        $siteName = Setting::getSiteName();
        $title = $data['title'] ?? '新消息通知';
        $content = $data['content'] ?? '';
        $link = $data['link'] ?? '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: #fff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; }
        .content { padding: 30px; }
        .title { font-size: 18px; font-weight: bold; color: #1e293b; margin-bottom: 15px; }
        .info { color: #64748b; font-size: 14px; line-height: 1.6; }
        .btn { display: inline-block; background: #1e293b; color: #fff; padding: 12px 30px; border-radius: 6px; text-decoration: none; margin-top: 20px; }
        .footer { background: #f8fafc; padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$siteName}</h1>
        </div>
        <div class="content">
            <p class="title">{$title}</p>
            <p class="info">{$content}</p>
            {$link}
        </div>
        <div class="footer">
            此邮件由系统自动发送，请勿直接回复。
        </div>
    </div>
</body>
</html>
HTML;
    }

    public static function sendVerificationCode($email, $purpose = '注册')
    {
        try {
            $code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            
            $db = Database::getInstance();
            
            $db->query("DELETE FROM __PREFIX__verification_codes WHERE email = :email AND purpose = :purpose", [
                ':email' => $email,
                ':purpose' => $purpose
            ]);
            
            $expireMinutes = 10;
            $expireTime = date('Y-m-d H:i:s', time() + $expireMinutes * 60);
            
            $db->insert('verification_codes', [
                'email' => $email,
                'code' => $code,
                'purpose' => $purpose,
                'expire_time' => $expireTime,
                'created_at' => time()
            ]);

            $subject = '【' . Setting::getSiteName() . '】验证码';
            
            $result = self::sendTemplate($email, $subject, 'verification_code', [
                'code' => $code,
                'purpose' => $purpose,
                'expire_minutes' => $expireMinutes
            ]);

            if ($result['success']) {
                return ['success' => true, 'message' => '验证码已发送'];
            }
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => '系统错误：' . $e->getMessage()];
        }
    }

    public static function verifyCode($email, $code, $purpose = '注册')
    {
        $db = Database::getInstance();
        
        $record = $db->fetch(
            "SELECT * FROM __PREFIX__verification_codes 
            WHERE email = :email AND code = :code AND purpose = :purpose AND expire_time > :now",
            [
                ':email' => $email,
                ':code' => $code,
                ':purpose' => $purpose,
                ':now' => date('Y-m-d H:i:s')
            ]
        );

        if (!$record) {
            return false;
        }

        $db->query("DELETE FROM __PREFIX__verification_codes WHERE id = :id", [':id' => $record['id']]);

        return true;
    }

    public static function sendWelcome($email, $username)
    {
        return self::sendTemplate($email, '欢迎加入 ' . Setting::getSiteName(), 'welcome', [
            'username' => $username
        ]);
    }
}

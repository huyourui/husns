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
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->escape($siteName); ?><?php if ($subtitle): ?> - <?php echo $this->escape($subtitle); ?><?php endif; ?></title>
    <link rel="stylesheet" href="<?php echo $this->asset('css/style.css'); ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            overflow: hidden;
        }

        body {
            height: 100vh;
            background: linear-gradient(145deg, #1e293b 0%, #0f172a 50%, #1e1e2e 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 115, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(150, 180, 255, 0.04) 0%, transparent 40%);
            pointer-events: none;
        }

        .guest-container {
            width: 100%;
            max-width: 400px;
            max-height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 1;
        }

        .guest-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.05);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 30px);
        }

        .guest-header {
            text-align: center;
            padding: 30px 30px 20px;
            background: linear-gradient(180deg, #fafbfc 0%, #ffffff 100%);
            flex-shrink: 0;
        }

        .guest-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 4px 12px rgba(30, 41, 59, 0.3);
        }

        .guest-logo svg {
            width: 30px;
            height: 30px;
            fill: white;
        }

        .guest-title {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
            letter-spacing: -0.3px;
        }

        .guest-subtitle {
            font-size: 13px;
            color: #64748b;
            font-weight: 400;
        }

        .guest-body {
            padding: 0 30px 25px;
            overflow-y: auto;
            flex: 1;
        }

        .tab-switch {
            display: flex;
            background: #f1f5f9;
            border-radius: 10px;
            padding: 3px;
            margin-bottom: 20px;
        }

        .tab-switch-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
            text-align: center;
            display: block;
        }

        .tab-switch-btn:hover {
            color: #334155;
        }

        .tab-switch-btn.active {
            background: white;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .form-panel {
            display: none;
        }

        .form-panel.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            margin-bottom: 6px;
        }

        .remember-group {
            margin-bottom: 20px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 13px;
            color: #64748b;
        }

        .remember-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #334155;
        }

        .remember-text {
            user-select: none;
        }

        .form-input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #fafbfc;
            color: #0f172a;
        }

        .form-input:focus {
            outline: none;
            border-color: #475569;
            background: white;
            box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.1);
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #1e293b;
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            background: #334155;
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
            background: #1e293b;
        }

        .email-verify-group {
            display: flex;
            gap: 10px;
        }

        .email-code-input {
            flex: 1;
        }

        .btn-send-code {
            padding: 12px 16px;
            background: #f1f5f9;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            color: #334155;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-send-code:hover {
            background: #e2e8f0;
        }

        .btn-send-code:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .feature-item {
            text-align: center;
        }

        .feature-icon {
            width: 36px;
            height: 36px;
            background: #f1f5f9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-size: 16px;
        }

        .feature-text {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }

        .guest-footer {
            text-align: center;
            padding: 14px;
            background: #f8fafc;
            font-size: 11px;
            color: #94a3b8;
            flex-shrink: 0;
        }

        .guest-footer a {
            color: #94a3b8;
            text-decoration: none;
        }

        .guest-footer a:hover {
            color: #64748b;
        }

        .footer-separator {
            margin: 0 8px;
            color: #cbd5e1;
        }

        @media (max-height: 700px) {
            body {
                padding: 10px;
            }

            .guest-header {
                padding: 20px 25px 15px;
            }

            .guest-logo {
                width: 48px;
                height: 48px;
                margin-bottom: 12px;
            }

            .guest-logo svg {
                width: 26px;
                height: 26px;
            }

            .guest-title {
                font-size: 20px;
            }

            .guest-subtitle {
                font-size: 12px;
            }

            .guest-body {
                padding: 0 25px 20px;
            }

            .tab-switch {
                margin-bottom: 16px;
            }

            .tab-switch-btn {
                padding: 8px;
                font-size: 13px;
            }

            .form-group {
                margin-bottom: 12px;
            }

            .form-group label {
                font-size: 12px;
            }

            .form-input {
                padding: 10px 12px;
                font-size: 13px;
            }

            .btn-submit {
                padding: 10px;
                font-size: 13px;
            }

            .btn-send-code {
                padding: 10px 12px;
                font-size: 12px;
            }

            .features {
                margin-top: 16px;
                padding-top: 16px;
                gap: 10px;
            }

            .feature-icon {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }

            .feature-text {
                font-size: 10px;
            }

            .guest-footer {
                padding: 10px;
                font-size: 10px;
            }
        }

        @media (max-height: 550px) {
            .guest-header {
                padding: 15px 20px 10px;
            }

            .guest-logo {
                width: 40px;
                height: 40px;
                margin-bottom: 8px;
            }

            .guest-logo svg {
                width: 22px;
                height: 22px;
            }

            .guest-title {
                font-size: 18px;
            }

            .guest-subtitle {
                font-size: 11px;
            }

            .guest-body {
                padding: 0 20px 15px;
            }

            .tab-switch {
                margin-bottom: 12px;
                padding: 2px;
            }

            .tab-switch-btn {
                padding: 6px;
                font-size: 12px;
            }

            .form-group {
                margin-bottom: 10px;
            }

            .form-group label {
                font-size: 11px;
                margin-bottom: 4px;
            }

            .form-input {
                padding: 8px 10px;
                font-size: 12px;
            }

            .btn-submit {
                padding: 8px;
                font-size: 12px;
            }

            .btn-send-code {
                padding: 8px 10px;
                font-size: 11px;
            }

            .features {
                margin-top: 12px;
                padding-top: 12px;
                gap: 8px;
            }

            .feature-icon {
                width: 28px;
                height: 28px;
                font-size: 12px;
                margin-bottom: 4px;
            }

            .feature-text {
                font-size: 9px;
            }

            .guest-footer {
                padding: 8px;
                font-size: 9px;
            }
        }

        @media (max-width: 400px) {
            body {
                padding: 10px;
            }

            .guest-container {
                max-width: 100%;
            }

            .guest-header {
                padding: 20px 20px 15px;
            }

            .guest-body {
                padding: 0 20px 15px;
            }

            .guest-title {
                font-size: 18px;
            }

            .features {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .feature-item {
                display: flex;
                align-items: center;
                gap: 10px;
                text-align: left;
            }

            .feature-icon {
                margin: 0;
            }
        }

        body.dark-mode {
            background: linear-gradient(145deg, #0c0c0f 0%, #0a0a0c 50%, #0f0f14 100%);
        }

        body.dark-mode::before {
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(100, 100, 140, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(140, 100, 100, 0.04) 0%, transparent 50%);
        }

        body.dark-mode .guest-card {
            background: rgba(24, 24, 30, 0.98);
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.03);
        }

        body.dark-mode .guest-header {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.02) 0%, transparent 100%);
        }

        body.dark-mode .guest-logo {
            background: linear-gradient(135deg, #3f3f46 0%, #27272a 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        body.dark-mode .guest-title {
            color: #fafafa;
        }

        body.dark-mode .guest-subtitle {
            color: #71717a;
        }

        body.dark-mode .tab-switch {
            background: #27272a;
        }

        body.dark-mode .tab-switch-btn {
            color: #71717a;
        }

        body.dark-mode .tab-switch-btn:hover {
            color: #a1a1aa;
        }

        body.dark-mode .tab-switch-btn.active {
            background: #3f3f46;
            color: #fafafa;
        }

        body.dark-mode .form-group label {
            color: #d4d4d8;
        }

        body.dark-mode .remember-label {
            color: #71717a;
        }

        body.dark-mode .remember-label input[type="checkbox"] {
            accent-color: #fafafa;
        }

        body.dark-mode .form-input {
            background: #18181b;
            border-color: #3f3f46;
            color: #fafafa;
        }

        body.dark-mode .form-input:focus {
            background: #27272a;
            border-color: #52525b;
            box-shadow: 0 0 0 3px rgba(82, 82, 91, 0.2);
        }

        body.dark-mode .form-input::placeholder {
            color: #52525b;
        }

        body.dark-mode .btn-submit {
            background: #fafafa;
            color: #18181b;
        }

        body.dark-mode .btn-submit:hover {
            background: #e4e4e7;
        }

        body.dark-mode .btn-send-code {
            background: #27272a;
            border-color: #3f3f46;
            color: #a1a1aa;
        }

        body.dark-mode .btn-send-code:hover {
            background: #3f3f46;
        }

        body.dark-mode .features {
            border-top-color: #3f3f46;
        }

        body.dark-mode .feature-icon {
            background: #27272a;
        }

        body.dark-mode .feature-text {
            color: #71717a;
        }

        body.dark-mode .guest-footer {
            background: #18181b;
            color: #52525b;
        }

        body.dark-mode .guest-footer a {
            color: #52525b;
        }

        body.dark-mode .guest-footer a:hover {
            color: #71717a;
        }

        body.dark-mode .footer-separator {
            color: #3f3f46;
        }
    </style>
</head>
<body>
    <div class="guest-container">
        <div class="guest-card">
            <div class="guest-header">
                <div class="guest-logo">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <h1 class="guest-title"><?php echo $this->escape($siteName); ?></h1>
                <?php if ($subtitle): ?>
                <p class="guest-subtitle"><?php echo $this->escape($subtitle); ?></p>
                <?php endif; ?>
            </div>

            <div class="guest-body">
                <?php if ($this->hasFlash('error')): ?>
                <div class="alert alert-error"><?php echo $this->flash('error'); ?></div>
                <?php endif; ?>

                <?php if ($this->hasFlash('success')): ?>
                <div class="alert alert-success"><?php echo $this->flash('success'); ?></div>
                <?php endif; ?>

                <div class="tab-switch">
                    <button type="button" class="tab-switch-btn active" data-tab="login">登录</button>
                    <?php if ($registrationOpen): ?>
                    <a href="<?php echo $this->url('user/register'); ?>" class="tab-switch-btn">注册</a>
                    <?php endif; ?>
                </div>

                <div class="form-panel active" id="panel-login">
                    <form method="post" action="<?php echo $this->url('user/login'); ?>">
                        <?php echo $this->csrf(); ?>
                        <div class="form-group">
                            <label>用户名</label>
                            <input type="text" name="username" class="form-input" placeholder="请输入用户名" required>
                        </div>
                        <div class="form-group">
                            <label>密码</label>
                            <input type="password" name="password" class="form-input" placeholder="请输入密码" required>
                        </div>
                        <div class="form-group remember-group">
                            <label class="remember-label">
                                <input type="checkbox" name="remember" value="1">
                                <span class="remember-text">保持登录</span>
                            </label>
                        </div>
                        <button type="submit" class="btn-submit">登录</button>
                    </form>
                </div>

                <?php if ($registrationOpen): ?>
                <div class="form-panel" id="panel-register">
                    <form method="post" action="<?php echo $this->url('user/register'); ?>">
                        <?php echo $this->csrf(); ?>
                        <div class="form-group">
                            <label>用户名</label>
                            <input type="text" name="username" id="guest-username" class="form-input" placeholder="请输入用户名（<?php echo Setting::getUsernameMinLength(); ?>-<?php echo Setting::getUsernameMaxLength(); ?>个字符）" required minlength="<?php echo Setting::getUsernameMinLength(); ?>" maxlength="<?php echo Setting::getUsernameMaxLength(); ?>">
                        </div>
                        <div class="form-group">
                            <label>邮箱</label>
                            <input type="email" name="email" id="guest-email" class="form-input" placeholder="请输入邮箱" required>
                        </div>
                        <?php if ($emailVerifyEnabled): ?>
                        <div class="form-group email-verify-group">
                            <input type="text" name="email_code" class="form-input email-code-input" placeholder="验证码" required maxlength="6">
                            <button type="button" class="btn-send-code" id="guest-send-code-btn" onclick="sendGuestEmailCode()">获取验证码</button>
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label>密码</label>
                            <input type="password" name="password" id="register-password" class="form-input" placeholder="请输入密码（至少6位）" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>确认密码</label>
                            <input type="password" name="confirm_password" id="register-password-confirm" class="form-input" placeholder="请再次输入密码" required minlength="6">
                        </div>
                        <button type="submit" class="btn-submit">注册</button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="features">
                    <div class="feature-item">
                        <div class="feature-icon">📝</div>
                        <div class="feature-text">发布动态</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">💬</div>
                        <div class="feature-text">互动交流</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">👥</div>
                        <div class="feature-text">关注好友</div>
                    </div>
                </div>
            </div>

            <div class="guest-footer">
                <div>&copy; <?php echo date('Y'); ?> <?php echo $this->escape($siteName); ?> | Powered by <a href="https://huyourui.com" target="_blank" rel="noopener">HuSNS</a></div>
                <?php if (!empty($icpNumber)): ?>
                <div><a href="<?php echo $this->escape($icpUrl); ?>" target="_blank" rel="noopener"><?php echo $this->escape($icpNumber); ?></a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.tab-switch-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var tabId = this.dataset.tab;
                
                document.querySelectorAll('.tab-switch-btn').forEach(function(b) {
                    b.classList.remove('active');
                });
                document.querySelectorAll('.form-panel').forEach(function(p) {
                    p.classList.remove('active');
                });
                
                this.classList.add('active');
                document.getElementById('panel-' + tabId).classList.add('active');
            });
        });

        var registerForm = document.querySelector('#panel-register form');
        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                var password = document.getElementById('register-password').value;
                var passwordConfirm = document.getElementById('register-password-confirm').value;
                
                if (password !== passwordConfirm) {
                    e.preventDefault();
                    alert('两次输入的密码不一致，请重新输入');
                    document.getElementById('register-password-confirm').focus();
                }
            });
        }

        var sendCodeBtn = document.getElementById('guest-send-code-btn');
        var countdown = 0;
        var timer = null;

        function sendGuestEmailCode() {
            var email = document.getElementById('guest-email').value;
            if (!email) {
                alert('请先输入邮箱');
                return;
            }
            
            var form = document.querySelector('#panel-register form');
            var csrfToken = form.querySelector('input[name="csrf_token"]').value;
            
            sendCodeBtn.disabled = true;
            sendCodeBtn.textContent = '发送中...';
            
            var formData = new FormData();
            formData.append('email', email);
            formData.append('csrf_token', csrfToken);
            
            fetch('<?php echo $this->url("user/sendEmailCode"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { 
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json(); 
            })
            .then(function(data) {
                if (data.code === 0) {
                    alert(data.message);
                    startCountdown(60);
                } else {
                    alert(data.message || '发送失败');
                    sendCodeBtn.disabled = false;
                    sendCodeBtn.textContent = '获取验证码';
                }
            })
            .catch(function(err) {
                console.error(err);
                alert('发送失败：' + err.message);
                sendCodeBtn.disabled = false;
                sendCodeBtn.textContent = '获取验证码';
            });
        }

        function startCountdown(seconds) {
            countdown = seconds;
            sendCodeBtn.disabled = true;
            
            timer = setInterval(function() {
                countdown--;
                if (countdown <= 0) {
                    clearInterval(timer);
                    sendCodeBtn.disabled = false;
                    sendCodeBtn.textContent = '获取验证码';
                } else {
                    sendCodeBtn.textContent = countdown + '秒后重发';
                }
            }, 1000);
        }

        var theme = localStorage.getItem('theme');
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
        }

        (function() {
            var minLength = <?php echo Setting::getUsernameMinLength(); ?>;
            var maxLength = <?php echo Setting::getUsernameMaxLength(); ?>;
            var usernameInput = document.getElementById('guest-username');
            
            if (usernameInput) {
                usernameInput.addEventListener('input', function() {
                    var len = this.value.length;
                    if (len < minLength) {
                        this.setCustomValidity('用户名至少需要' + minLength + '个字符');
                    } else if (len > maxLength) {
                        this.setCustomValidity('用户名最多允许' + maxLength + '个字符');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        })();
    </script>
</body>
</html>

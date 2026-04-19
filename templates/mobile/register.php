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
$emailVerifyEnabled = $emailVerifyEnabled ?? false;
$requireInviteCode = $requireInviteCode ?? false;
?>
<div class="m-login-page">
    <div class="m-login-logo">注册账号</div>
    
    <form method="post" action="<?php echo $this->url('mobile/register'); ?>" id="registerForm">
        <?php echo $this->csrf(); ?>
        <div class="m-form-group">
            <input type="text" name="username" class="m-form-input" placeholder="用户名（<?php echo Setting::getUsernameMinLength(); ?>-<?php echo Setting::getUsernameMaxLength(); ?>位）" autocomplete="username" required>
        </div>
        <div class="m-form-group">
            <input type="email" name="email" class="m-form-input" placeholder="邮箱<?php echo $emailVerifyEnabled ? '（必填）' : '（选填）'; ?>" autocomplete="email" <?php echo $emailVerifyEnabled ? 'required' : ''; ?>>
        </div>
        <?php if ($emailVerifyEnabled): ?>
        <div class="m-form-group" style="display:flex;gap:10px;">
            <input type="text" name="email_code" class="m-form-input" placeholder="邮箱验证码" style="flex:1;" required>
            <button type="button" id="sendCodeBtn" class="m-btn m-btn-secondary" style="white-space:nowrap;padding:14px 15px;width:auto;">获取验证码</button>
        </div>
        <?php endif; ?>
        <div class="m-form-group">
            <input type="password" name="password" class="m-form-input" placeholder="密码（至少6位）" autocomplete="new-password" required>
        </div>
        <div class="m-form-group">
            <input type="password" name="confirm_password" class="m-form-input" placeholder="确认密码" autocomplete="new-password" required>
        </div>
        <?php if ($requireInviteCode): ?>
        <div class="m-form-group">
            <input type="text" name="invite_code" class="m-form-input" placeholder="邀请码" required>
        </div>
        <?php endif; ?>
        <button type="submit" class="m-btn m-btn-primary">注册</button>
    </form>
    
    <div class="m-divider">
        <span class="m-divider-text">已有账号？</span>
    </div>
    <a href="<?php echo $this->url('mobile/login'); ?>" class="m-btn m-btn-secondary">去登录</a>
</div>

<?php if ($emailVerifyEnabled): ?>
<script>
(function() {
    var sendBtn = document.getElementById('sendCodeBtn');
    var emailInput = document.querySelector('input[name="email"]');
    var countdown = 0;
    
    sendBtn.addEventListener('click', function() {
        if (countdown > 0) return;
        
        var email = emailInput.value.trim();
        if (!email) {
            alert('请输入邮箱');
            emailInput.focus();
            return;
        }
        
        sendBtn.disabled = true;
        sendBtn.textContent = '发送中...';
        
        var formData = new FormData();
        formData.append('email', email);
        formData.append('type', '注册');
        formData.append('csrf_token', CSRF_TOKEN);
        
        fetch(BASE_URL + '/?r=user/sendEmailCode', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.code === 0) {
                countdown = 60;
                updateCountdown();
                alert('验证码已发送');
            } else {
                alert(data.msg || '发送失败');
                sendBtn.disabled = false;
                sendBtn.textContent = '获取验证码';
            }
        })
        .catch(function() {
            alert('网络错误');
            sendBtn.disabled = false;
            sendBtn.textContent = '获取验证码';
        });
    });
    
    function updateCountdown() {
        if (countdown > 0) {
            sendBtn.textContent = countdown + 's';
            countdown--;
            setTimeout(updateCountdown, 1000);
        } else {
            sendBtn.disabled = false;
            sendBtn.textContent = '获取验证码';
        }
    }
})();
</script>
<?php endif; ?>

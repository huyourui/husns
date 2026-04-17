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
<div class="page-register">
    <div class="auth-box">
        <h2>注册</h2>
        <form method="post" action="<?php echo $this->url('user/register'); ?>" id="registerForm">
            <?php echo $this->csrf(); ?>
            <div class="form-group">
                <input type="text" name="username" id="username" placeholder="用户名（<?php echo Setting::getUsernameMinLength(); ?>-<?php echo Setting::getUsernameMaxLength(); ?>个字符）" required minlength="<?php echo Setting::getUsernameMinLength(); ?>" maxlength="<?php echo Setting::getUsernameMaxLength(); ?>">
            </div>
            <div class="form-group">
                <input type="email" name="email" id="email" placeholder="邮箱" required>
            </div>
            <?php if ($emailVerifyEnabled): ?>
            <div class="form-group email-code-group">
                <input type="text" name="email_code" placeholder="邮箱验证码" required maxlength="6" style="flex:1;">
                <button type="button" class="btn btn-default" id="sendCodeBtn" onclick="sendEmailCode()">获取验证码</button>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <input type="password" name="password" placeholder="密码（至少6位）" required>
            </div>
            <div class="form-group">
                <input type="password" name="confirm_password" placeholder="确认密码" required>
            </div>
            <?php if ($requireInviteCode): ?>
            <div class="form-group">
                <input type="text" name="invite_code" placeholder="邀请码" required>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary btn-block">注册</button>
        </form>
        <p class="auth-link">已有账号？<a href="<?php echo $this->url('user/login'); ?>">立即登录</a></p>
    </div>
</div>

<style>
.email-code-group {
    display: flex;
    gap: 10px;
}

.email-code-group input {
    flex: 1;
}

.email-code-group button {
    white-space: nowrap;
}

#sendCodeBtn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<?php if ($emailVerifyEnabled): ?>
<script>
var sendCodeBtn = document.getElementById('sendCodeBtn');
var countdown = 0;
var timer = null;

function sendEmailCode() {
    var email = document.getElementById('email').value;
    if (!email) {
        alert('请先输入邮箱');
        return;
    }
    
    var form = document.getElementById('registerForm');
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
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.code === 0) {
            alert(data.message);
            startCountdown(60);
        } else {
            alert(data.message);
            sendCodeBtn.disabled = false;
            sendCodeBtn.textContent = '获取验证码';
        }
    })
    .catch(function() {
        alert('发送失败，请重试');
        sendCodeBtn.disabled = false;
        sendCodeBtn.textContent = '获取验证码';
    });
}

function startCountdown(seconds) {
    countdown = seconds;
    sendCodeBtn.textContent = countdown + '秒后重发';
    
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
</script>
<?php endif; ?>

<script>
(function() {
    var minLength = <?php echo Setting::getUsernameMinLength(); ?>;
    var maxLength = <?php echo Setting::getUsernameMaxLength(); ?>;
    var usernameInput = document.getElementById('username');
    
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

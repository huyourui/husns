<?php
$pageType = 'register';
$bodyClass = 'register-page';
$hideTabBar = true;
$headerTitle = '注册';
$this->setLayout('app');
?>
<div class="auth-form">
    <form id="registerForm">
        <div class="form-group">
            <label>用户名</label>
            <input type="text" name="username" placeholder="请输入用户名" required>
        </div>
        <div class="form-group">
            <label>邮箱</label>
            <input type="email" name="email" placeholder="请输入邮箱" required>
        </div>
        <div class="form-group">
            <label>密码</label>
            <input type="password" name="password" placeholder="请输入密码" required>
        </div>
        <div class="form-group">
            <label>确认密码</label>
            <input type="password" name="confirm_password" placeholder="请再次输入密码" required>
        </div>
        <?php if ($emailVerifyEnabled): ?>
        <div class="form-group">
            <label>验证码</label>
            <input type="text" name="email_code" placeholder="请输入邮箱验证码" required>
        </div>
        <?php endif; ?>
        <?php if ($requireInviteCode): ?>
        <div class="form-group">
            <label>邀请码</label>
            <input type="text" name="invite_code" placeholder="请输入邀请码" required>
        </div>
        <?php endif; ?>
        <button type="submit" class="auth-btn">注册</button>
    </form>
    <div class="auth-link">
        <a href="<?php echo $this->url('mobile/login'); ?>">已有账号？立即登录</a>
    </div>
</div>

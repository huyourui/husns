<?php
$pageType = 'login';
$bodyClass = 'login-page';
$hideTabBar = true;
$headerTitle = '登录';
$this->setLayout('app');
?>
<div class="auth-form">
    <form id="loginForm">
        <div class="form-group">
            <label>用户名</label>
            <input type="text" name="username" placeholder="请输入用户名" required>
        </div>
        <div class="form-group">
            <label>密码</label>
            <input type="password" name="password" placeholder="请输入密码" required>
        </div>
        <button type="submit" class="auth-btn">登录</button>
    </form>
    <div class="auth-link">
        <a href="<?php echo $this->url('mobile/register'); ?>">没有账号？立即注册</a>
    </div>
</div>

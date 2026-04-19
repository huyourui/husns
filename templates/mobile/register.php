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
<div class="m-login-page">
    <div class="m-login-logo">注册账号</div>
    
    <form method="post" action="<?php echo $this->url('mobile/register'); ?>">
        <?php echo $this->csrf(); ?>
        <div class="m-form-group">
            <input type="text" name="username" class="m-form-input" placeholder="用户名（3-20位字母开头）" autocomplete="username" required>
        </div>
        <div class="m-form-group">
            <input type="email" name="email" class="m-form-input" placeholder="邮箱（选填）" autocomplete="email">
        </div>
        <div class="m-form-group">
            <input type="password" name="password" class="m-form-input" placeholder="密码（至少6位）" autocomplete="new-password" required>
        </div>
        <div class="m-form-group">
            <input type="password" name="confirm_password" class="m-form-input" placeholder="确认密码" autocomplete="new-password" required>
        </div>
        <button type="submit" class="m-btn m-btn-primary">注册</button>
    </form>
    
    <div class="m-divider">
        <span class="m-divider-text">已有账号？</span>
    </div>
    <a href="<?php echo $this->url('mobile/login'); ?>" class="m-btn m-btn-secondary">去登录</a>
</div>

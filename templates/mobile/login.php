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
    <div class="m-login-logo"><?php echo $this->escape(Setting::getSiteName()); ?></div>
    
    <form method="post" action="<?php echo $this->url('mobile/login'); ?>">
        <?php echo $this->csrf(); ?>
        <div class="m-form-group">
            <input type="text" name="username" class="m-form-input" placeholder="用户名" autocomplete="username" required>
        </div>
        <div class="m-form-group">
            <input type="password" name="password" class="m-form-input" placeholder="密码" autocomplete="current-password" required>
        </div>
        <button type="submit" class="m-btn m-btn-primary">登录</button>
    </form>
    
    <?php if (Setting::isRegistrationOpen()): ?>
    <div class="m-divider">
        <span class="m-divider-text">还没有账号？</span>
    </div>
    <a href="<?php echo $this->url('mobile/register'); ?>" class="m-btn m-btn-secondary">注册账号</a>
    <?php endif; ?>
    
    <div style="margin-top:30px;text-align:center;">
        <a href="<?php echo $this->url(); ?>" class="m-link">电脑版</a>
    </div>
</div>

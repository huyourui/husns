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
<div class="page-login">
    <div class="auth-box">
        <h2><?php echo $this->t('user.login_title'); ?></h2>
        <form method="post" action="<?php echo $this->url('user/login'); ?>">
            <?php echo $this->csrf(); ?>
            <div class="form-group">
                <input type="text" name="username" placeholder="<?php echo $this->t('user.username'); ?>" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="<?php echo $this->t('user.password'); ?>" required>
            </div>
            <div class="form-group remember-me">
                <label><input type="checkbox" name="remember" value="1"> <?php echo $this->t('user.remember_me'); ?></label>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?php echo $this->t('common.login'); ?></button>
        </form>
        <p class="auth-link"><?php echo $this->t('user.no_account'); ?> <a href="<?php echo $this->url('user/register'); ?>"><?php echo $this->t('user.register_now'); ?></a></p>
    </div>
</div>

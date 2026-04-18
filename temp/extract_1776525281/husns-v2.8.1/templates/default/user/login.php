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
        <h2>登录</h2>
        <form method="post" action="<?php echo $this->url('user/login'); ?>">
            <?php echo $this->csrf(); ?>
            <div class="form-group">
                <input type="text" name="username" placeholder="用户名" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="密码" required>
            </div>
            <div class="form-group remember-me">
                <label><input type="checkbox" name="remember" value="1"> 保持登录</label>
            </div>
            <button type="submit" class="btn btn-primary btn-block">登录</button>
        </form>
        <p class="auth-link">还没有账号？<a href="<?php echo $this->url('user/register'); ?>">立即注册</a></p>
    </div>
</div>

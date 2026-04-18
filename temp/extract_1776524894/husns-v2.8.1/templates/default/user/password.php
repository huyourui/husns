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
<div class="page-password">
    <h2>修改密码</h2>
    <form method="post" action="<?php echo $this->url('user/password'); ?>">
        <?php echo $this->csrf(); ?>
        <div class="form-group">
            <label>原密码</label>
            <input type="password" name="old_password" required>
        </div>
        <div class="form-group">
            <label>新密码</label>
            <input type="password" name="new_password" required>
        </div>
        <div class="form-group">
            <label>确认新密码</label>
            <input type="password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary">修改密码</button>
    </form>
</div>

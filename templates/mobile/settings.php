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
<div class="m-menu-list">
    <a href="<?php echo $this->url('user/settings'); ?>" class="m-menu-item">
        <div class="m-menu-left">
            <span class="m-menu-icon">👤</span>
            <span class="m-menu-text">个人资料</span>
        </div>
        <span class="m-menu-arrow">›</span>
    </a>
    <a href="<?php echo $this->url('user/password'); ?>" class="m-menu-item">
        <div class="m-menu-left">
            <span class="m-menu-icon">🔐</span>
            <span class="m-menu-text">修改密码</span>
        </div>
        <span class="m-menu-arrow">›</span>
    </a>
</div>

<div class="m-menu-list" style="margin-top:10px;">
    <div class="m-menu-item" onclick="toggleTheme()">
        <div class="m-menu-left">
            <span class="m-menu-icon">🌙</span>
            <span class="m-menu-text">深色模式</span>
        </div>
        <span id="themeStatus"><?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? '已开启' : '未开启'; ?></span>
    </div>
</div>

<div class="m-menu-list" style="margin-top:10px;">
    <a href="javascript:void(0)" onclick="switchToDesktop()" class="m-menu-item">
        <div class="m-menu-left">
            <span class="m-menu-icon">🖥️</span>
            <span class="m-menu-text">电脑版</span>
        </div>
        <span class="m-menu-arrow">›</span>
    </a>
    <a href="https://huyourui.com" target="_blank" class="m-menu-item">
        <div class="m-menu-left">
            <span class="m-menu-icon">ℹ️</span>
            <span class="m-menu-text">关于我们</span>
        </div>
        <span class="m-menu-arrow">›</span>
    </a>
</div>

<script>
function toggleTheme() {
    var body = document.body;
    var status = document.getElementById('themeStatus');
    
    if (body.classList.contains('dark-mode')) {
        body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
        status.textContent = '未开启';
    } else {
        body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
        status.textContent = '已开启';
    }
}

function switchToDesktop() {
    document.cookie = 'prefer_desktop=1; path=/; max-age=' + (365 * 24 * 60 * 60);
    location.href = BASE_URL + '/';
}
</script>

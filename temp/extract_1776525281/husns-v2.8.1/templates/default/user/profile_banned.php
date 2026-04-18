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
<div class="profile-page">
    <div class="profile-header">
        <div class="profile-avatar">
            <?php if (!empty($user['avatar'])): ?>
            <img src="<?php echo $this->escape($user['avatar']); ?>" alt="">
            <?php else: ?>
            <div class="avatar-default"><?php echo mb_substr($user['username'], 0, 1); ?></div>
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <h1><?php echo $this->escape($user['username']); ?></h1>
            <p class="banned-badge">🚫 该用户已被封禁</p>
        </div>
    </div>
    
    <div class="banned-notice">
        <div class="banned-notice-icon">🚫</div>
        <h2>用户已被封禁</h2>
        <p>该用户因违反社区规则已被封禁，相关内容暂时无法查看。</p>
        <?php if (!empty($banInfo['reason'])): ?>
        <p class="banned-reason">封禁原因：<?php echo $this->escape($banInfo['reason']); ?></p>
        <?php endif; ?>
    </div>
</div>

<style>
.profile-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.profile-header {
    display: flex;
    align-items: center;
    padding: 30px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.profile-avatar {
    margin-right: 20px;
}

.profile-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.avatar-default {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
}

.profile-info h1 {
    margin: 0 0 8px;
    font-size: 24px;
    color: #1e293b;
}

.banned-badge {
    display: inline-block;
    padding: 4px 12px;
    background: #fef2f2;
    color: #dc2626;
    border-radius: 20px;
    font-size: 14px;
    margin: 0;
}

.banned-notice {
    text-align: center;
    padding: 60px 30px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.banned-notice-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.banned-notice h2 {
    margin: 0 0 16px;
    color: #1e293b;
    font-size: 24px;
}

.banned-notice p {
    color: #64748b;
    font-size: 16px;
    margin: 0;
}

.banned-reason {
    margin-top: 16px !important;
    padding: 12px 20px;
    background: #fef2f2;
    color: #dc2626;
    border-radius: 8px;
    display: inline-block;
}

body.dark-mode .profile-header,
body.dark-mode .banned-notice {
    background: #1e293b;
}

body.dark-mode .profile-info h1,
body.dark-mode .banned-notice h2 {
    color: #f1f5f9;
}

body.dark-mode .banned-notice p {
    color: #94a3b8;
}
</style>

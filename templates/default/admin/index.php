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
<div class="admin-dashboard">
    <h2>仪表盘</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['users']; ?></div>
            <div class="stat-label">用户总数</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['posts']; ?></div>
            <div class="stat-label">动态总数</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['comments']; ?></div>
            <div class="stat-label">评论总数</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['topics']; ?></div>
            <div class="stat-label">话题总数</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['today_users']; ?></div>
            <div class="stat-label">今日新增用户</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['today_posts']; ?></div>
            <div class="stat-label">今日新增动态</div>
        </div>
    </div>

    <div class="server-info-section">
        <h3>服务器信息</h3>
        <table class="server-info-table">
            <tr>
                <td class="info-label">源码版本</td>
                <td>
                    <span class="version-current">v<?php echo APP_VERSION; ?></span>
                    <?php if (!empty($versionInfo['has_update'])): ?>
                    <a href="<?php echo $this->url('upgrade'); ?>" class="version-update-badge">
                        <span class="update-icon">🔄</span>
                        <span class="update-text">发现新版本 v<?php echo $this->escape($versionInfo['latest']); ?></span>
                        <span class="update-action">点击更新</span>
                    </a>
                    <?php elseif (!empty($versionInfo['latest'])): ?>
                    <span class="version-latest-badge">
                        <span class="latest-icon">✓</span>
                        <span class="latest-text">已是最新版本</span>
                    </span>
                    <?php endif; ?>
                </td>
                <td class="info-label">操作系统</td>
                <td><?php echo $this->escape($serverInfo['os']); ?></td>
            </tr>
            <tr>
                <td class="info-label">Web服务器</td>
                <td><?php echo $this->escape($serverInfo['server_software']); ?></td>
                <td class="info-label">PHP版本</td>
                <td><?php echo $this->escape($serverInfo['php_version']); ?></td>
            </tr>
            <tr>
                <td class="info-label">MySQL版本</td>
                <td><?php echo $this->escape($serverInfo['mysql_version']); ?></td>
                <td class="info-label">上传限制</td>
                <td><?php echo $this->escape($serverInfo['upload_max_filesize']); ?></td>
            </tr>
            <tr>
                <td class="info-label">POST限制</td>
                <td><?php echo $this->escape($serverInfo['post_max_size']); ?></td>
                <td class="info-label">内存限制</td>
                <td><?php echo $this->escape($serverInfo['memory_limit']); ?></td>
            </tr>
            <tr>
                <td class="info-label">执行时间</td>
                <td><?php echo $this->escape($serverInfo['max_execution_time']); ?></td>
                <td class="info-label">磁盘剩余空间</td>
                <td><?php echo $this->escape($serverInfo['disk_free_space']); ?></td>
            </tr>
        </table>
    </div>
</div>

<style>
.version-current {
    font-weight: 600;
    color: #374151;
}

.version-update-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-left: 12px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1px solid #f59e0b;
    border-radius: 20px;
    text-decoration: none;
    font-size: 13px;
    transition: all 0.2s ease;
    animation: pulse-badge 2s infinite;
}

.version-update-badge:hover {
    background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.update-icon {
    font-size: 14px;
    animation: rotate 1.5s linear infinite;
}

.update-text {
    color: #92400e;
    font-weight: 500;
}

.update-action {
    color: #d97706;
    font-weight: 600;
    padding-left: 6px;
    border-left: 1px solid #f59e0b;
}

.version-latest-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-left: 12px;
    padding: 4px 10px;
    background: #d1fae5;
    border: 1px solid #10b981;
    border-radius: 20px;
    font-size: 12px;
}

.latest-icon {
    color: #10b981;
    font-weight: bold;
}

.latest-text {
    color: #065f46;
}

@keyframes pulse-badge {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.85;
    }
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>

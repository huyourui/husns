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
                <td><?php echo APP_VERSION; ?></td>
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

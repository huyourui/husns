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
<div class="upgrade-page">
    <h2>系统更新</h2>
    
    <div class="version-info-card">
        <div class="version-current">
            <span class="version-label">当前版本</span>
            <span class="version-number">v<?php echo $this->escape($currentVersion); ?></span>
        </div>
        <div class="version-divider"></div>
        <div class="version-latest">
            <span class="version-label">最新版本</span>
            <span class="version-number" id="latestVersion">
                <?php if ($latestVersion): ?>
                    v<?php echo $this->escape($latestVersion); ?>
                <?php else: ?>
                    <span class="loading-text">检测中...</span>
                <?php endif; ?>
            </span>
        </div>
        <button class="btn btn-check" onclick="checkUpdate()">
            <span class="btn-icon">🔄</span> 检测更新
        </button>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-error">
        <strong>检测失败：</strong><?php echo $this->escape($error); ?>
    </div>
    <?php endif; ?>
    
    <div id="updateNotice" class="update-notice <?php echo $hasUpdate ? 'has-update' : 'no-update'; ?>" style="<?php echo $latestVersion ? '' : 'display:none;'; ?>">
        <?php if ($hasUpdate): ?>
        <div class="notice-content has-update">
            <div class="notice-icon">🎉</div>
            <div class="notice-text">
                <h3>发现新版本！</h3>
                <p>最新版本 <strong>v<?php echo $this->escape($latestVersion); ?></strong> 已发布，建议您尽快更新。</p>
            </div>
        </div>
        <?php else: ?>
        <div class="notice-content no-update">
            <div class="notice-icon">✅</div>
            <div class="notice-text">
                <h3>已是最新版本</h3>
                <p>您当前使用的是最新版本，无需更新。</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="upgrade-sections">
        <div class="upgrade-section">
            <h3>📦 上传更新包</h3>
            <div class="section-content">
                <div class="warning-box">
                    <strong>⚠️ 更新前请注意：</strong>
                    <ul>
                        <li>建议先备份当前系统和数据库</li>
                        <li>确保上传的更新包来源可信</li>
                        <li>更新过程中请勿关闭页面</li>
                    </ul>
                </div>
                
                <div class="upload-area" id="uploadArea">
                    <input type="file" id="packageFile" accept=".zip" style="display:none;">
                    <div class="upload-placeholder" onclick="document.getElementById('packageFile').click()">
                        <div class="upload-icon">📁</div>
                        <p>点击选择ZIP更新包</p>
                        <p class="upload-hint">支持最大100MB的ZIP文件</p>
                    </div>
                    <div class="upload-progress" style="display:none;">
                        <div class="progress-bar">
                            <div class="progress-fill" id="uploadProgress"></div>
                        </div>
                        <p id="uploadStatus">上传中...</p>
                    </div>
                    <div class="upload-success" style="display:none;">
                        <div class="success-icon">✅</div>
                        <p id="uploadFileName">文件已上传</p>
                    </div>
                </div>
                
                <div class="download-link">
                    <a href="<?php echo $this->escape($downloadPageUrl); ?>" target="_blank" rel="noopener">
                        🔗 前往Gitee下载最新版本
                    </a>
                </div>
                
                <div class="upgrade-options">
                    <label class="checkbox-label">
                        <input type="checkbox" id="autoBackup" checked>
                        <span>更新前自动备份（强烈推荐）</span>
                    </label>
                </div>
                
                <button class="btn btn-upgrade" id="upgradeBtn" onclick="doUpgrade()" disabled>
                    <span class="btn-icon">🚀</span> 开始更新
                </button>
            </div>
        </div>
        
        <div class="upgrade-section">
            <h3>💾 备份管理</h3>
            <div class="section-content">
                <div class="backup-actions">
                    <button class="btn btn-backup" onclick="createBackup()">
                        <span class="btn-icon">💾</span> 创建备份
                    </button>
                </div>
                
                <div class="backup-list" id="backupList">
                    <?php if (empty($backups)): ?>
                    <div class="no-backup">
                        <p>暂无备份记录</p>
                    </div>
                    <?php else: ?>
                    <table class="backup-table">
                        <thead>
                            <tr>
                                <th>备份名称</th>
                                <th>版本</th>
                                <th>创建时间</th>
                                <th>大小</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><?php echo $this->escape($backup['name']); ?></td>
                                <td>v<?php echo $this->escape($backup['version']); ?></td>
                                <td><?php echo $this->escape($backup['created_at_text']); ?></td>
                                <td><?php echo $this->escape($backup['size']); ?></td>
                                <td class="backup-actions-cell">
                                    <button class="btn btn-small btn-restore" onclick="restoreBackup('<?php echo $this->escape($backup['name']); ?>')">
                                        恢复
                                    </button>
                                    <button class="btn btn-small btn-delete" onclick="deleteBackup('<?php echo $this->escape($backup['name']); ?>')">
                                        删除
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalOverlay" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">提示</h3>
        </div>
        <div class="modal-body" id="modalBody"></div>
        <div class="modal-footer" id="modalFooter"></div>
    </div>
</div>

<style>
.upgrade-page {
    max-width: 900px;
    margin: 0 auto;
}

.upgrade-page h2 {
    margin-bottom: 20px;
    color: #1e293b;
}

.version-info-card {
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    color: #fff;
}

.version-current, .version-latest {
    flex: 1;
    text-align: center;
}

.version-label {
    display: block;
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 8px;
}

.version-number {
    font-size: 28px;
    font-weight: 700;
}

.version-divider {
    width: 1px;
    height: 50px;
    background: rgba(255,255,255,0.3);
    margin: 0 20px;
}

.btn-check {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-check:hover {
    background: rgba(255,255,255,0.3);
}

.update-notice {
    margin-bottom: 20px;
}

.notice-content {
    display: flex;
    align-items: center;
    padding: 20px;
    border-radius: 12px;
}

.notice-content.has-update {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #86efac;
}

.notice-content.no-update {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border: 1px solid #93c5fd;
}

.notice-icon {
    font-size: 40px;
    margin-right: 20px;
}

.notice-text h3 {
    margin: 0 0 5px;
    color: #1e293b;
}

.notice-text p {
    margin: 0;
    color: #64748b;
}

.upgrade-sections {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

.upgrade-section {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: visible;
}

.upgrade-section h3 {
    margin: 0;
    padding: 16px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    color: #1e293b;
}

.section-content {
    padding: 20px;
}

.warning-box {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 16px;
}

.warning-box strong {
    color: #92400e;
}

.warning-box ul {
    margin: 8px 0 0;
    padding-left: 20px;
    color: #92400e;
}

.warning-box li {
    margin: 4px 0;
}

.upload-area {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 16px;
}

.upload-area:hover {
    border-color: #667eea;
    background: #f8fafc;
}

.upload-icon {
    font-size: 48px;
    margin-bottom: 10px;
}

.upload-hint {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 5px;
}

.progress-bar {
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    width: 0%;
    transition: width 0.3s;
}

.download-link {
    text-align: center;
    margin-bottom: 16px;
}

.download-link a {
    color: #667eea;
    text-decoration: none;
}

.download-link a:hover {
    text-decoration: underline;
}

.upgrade-options {
    margin-bottom: 16px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.checkbox-label input {
    margin-right: 8px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-icon {
    margin-right: 6px;
}

.btn-upgrade {
    width: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.btn-upgrade:hover:not(:disabled) {
    opacity: 0.9;
    transform: translateY(-1px);
}

.btn-upgrade:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-backup {
    width: 100%;
    background: #10b981;
    color: #fff;
    margin-bottom: 16px;
}

.btn-backup:hover {
    background: #059669;
}

.btn-small {
    padding: 5px 10px;
    font-size: 12px;
}

.btn-restore {
    background: #3b82f6;
    color: #fff;
}

.btn-delete {
    background: #ef4444;
    color: #fff;
}

.backup-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: auto;
}

.backup-table th, .backup-table td {
    padding: 12px 10px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

.backup-table th:first-child, .backup-table td:first-child {
    min-width: 200px;
    white-space: normal;
    word-break: break-all;
}

.backup-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #64748b;
}

.backup-actions-cell {
    display: flex;
    gap: 8px;
    flex-wrap: nowrap;
}

.no-backup {
    text-align: center;
    color: #94a3b8;
    padding: 20px;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.modal-content {
    background: #fff;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow: auto;
}

.modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
    margin: 0;
    color: #1e293b;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 16px 20px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.modal-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.modal-btn-cancel {
    background: #e2e8f0;
    color: #64748b;
}

.modal-btn-confirm {
    background: #667eea;
    color: #fff;
}

.modal-btn-danger {
    background: #ef4444;
    color: #fff;
}

.loading-text {
    color: #94a3b8;
}

@media (max-width: 768px) {
    .version-info-card {
        flex-wrap: wrap;
    }
    
    .version-divider {
        display: none;
    }
    
    .backup-table th, .backup-table td {
        padding: 8px 5px;
        font-size: 12px;
    }
    
    .backup-table th:first-child, .backup-table td:first-child {
        min-width: 150px;
    }
    
    .btn-small {
        padding: 4px 8px;
        font-size: 11px;
    }
    
    .backup-actions-cell {
        flex-direction: column;
        gap: 4px;
    }
}
</style>

<script>
var tempFile = null;
var csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';

function checkUpdate() {
    var btn = document.querySelector('.btn-check');
    btn.disabled = true;
    btn.innerHTML = '<span class="btn-icon">⏳</span> 检测中...';
    
    fetch('<?php echo $this->url("upgrade/check"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<span class="btn-icon">🔄</span> 检测更新';
        
        if (data.code === 0) {
            var result = data.data;
            document.getElementById('latestVersion').textContent = 'v' + result.latest_version;
            
            var notice = document.getElementById('updateNotice');
            notice.style.display = 'block';
            
            if (result.has_update) {
                notice.className = 'update-notice has-update';
                notice.querySelector('.notice-content').className = 'notice-content has-update';
                notice.querySelector('.notice-icon').textContent = '🎉';
                notice.querySelector('h3').textContent = '发现新版本！';
                notice.querySelector('p').innerHTML = '最新版本 <strong>v' + result.latest_version + '</strong> 已发布，建议您尽快更新。';
            } else {
                notice.className = 'update-notice no-update';
                notice.querySelector('.notice-content').className = 'notice-content no-update';
                notice.querySelector('.notice-icon').textContent = '✅';
                notice.querySelector('h3').textContent = '已是最新版本';
                notice.querySelector('p').textContent = '您当前使用的是最新版本，无需更新。';
            }
        } else {
            alert(data.message || '检测失败');
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<span class="btn-icon">🔄</span> 检测更新';
        alert('网络错误，请稍后重试');
    });
}

document.getElementById('packageFile').addEventListener('change', function(e) {
    var file = e.target.files[0];
    if (!file) return;
    
    if (!file.name.endsWith('.zip')) {
        alert('请选择ZIP格式的文件');
        return;
    }
    
    if (file.size > 100 * 1024 * 1024) {
        alert('文件大小不能超过100MB');
        return;
    }
    
    var uploadArea = document.getElementById('uploadArea');
    uploadArea.querySelector('.upload-placeholder').style.display = 'none';
    uploadArea.querySelector('.upload-progress').style.display = 'block';
    
    var formData = new FormData();
    formData.append('package', file);
    formData.append('csrf_token', csrfToken);
    
    var xhr = new XMLHttpRequest();
    
    xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
            var percent = Math.round((e.loaded / e.total) * 100);
            document.getElementById('uploadProgress').style.width = percent + '%';
            document.getElementById('uploadStatus').textContent = '上传中... ' + percent + '%';
        }
    };
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.code === 0) {
                tempFile = response.data.temp_file;
                uploadArea.querySelector('.upload-progress').style.display = 'none';
                uploadArea.querySelector('.upload-success').style.display = 'block';
                document.getElementById('uploadFileName').textContent = file.name + ' 上传成功';
                document.getElementById('upgradeBtn').disabled = false;
            } else {
                alert(response.message || '上传失败');
                uploadArea.querySelector('.upload-progress').style.display = 'none';
                uploadArea.querySelector('.upload-placeholder').style.display = 'block';
            }
        } else {
            alert('上传失败');
            uploadArea.querySelector('.upload-progress').style.display = 'none';
            uploadArea.querySelector('.upload-placeholder').style.display = 'block';
        }
    };
    
    xhr.onerror = function() {
        alert('网络错误');
        uploadArea.querySelector('.upload-progress').style.display = 'none';
        uploadArea.querySelector('.upload-placeholder').style.display = 'block';
    };
    
    xhr.open('POST', '<?php echo $this->url("upgrade/upload"); ?>');
    xhr.send(formData);
});

function doUpgrade() {
    if (!tempFile) {
        alert('请先上传更新包');
        return;
    }
    
    var autoBackup = document.getElementById('autoBackup').checked ? 1 : 0;
    
    showModal('确认更新', '<p>确定要执行系统更新吗？</p><p style="color:#ef4444;">⚠️ 更新过程中请勿关闭页面</p>', [
        {text: '取消', class: 'modal-btn-cancel', onclick: 'hideModal()'},
        {text: '确认更新', class: 'modal-btn-confirm', onclick: 'confirmUpgrade(' + autoBackup + ')'}
    ]);
}

function confirmUpgrade(autoBackup) {
    hideModal();
    
    var btn = document.getElementById('upgradeBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="btn-icon">⏳</span> 更新中...';
    
    var formData = new FormData();
    formData.append('temp_file', tempFile);
    formData.append('auto_backup', autoBackup);
    formData.append('csrf_token', csrfToken);
    
    fetch('<?php echo $this->url("upgrade/doUpgrade"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.code === 0) {
            showModal('更新成功', '<p style="color:#10b981;">✅ ' + data.message + '</p><p>页面将在3秒后刷新...</p>', [
                {text: '立即刷新', class: 'modal-btn-confirm', onclick: 'location.reload()'}
            ]);
            setTimeout(function() {
                location.reload();
            }, 3000);
        } else {
            btn.disabled = false;
            btn.innerHTML = '<span class="btn-icon">🚀</span> 开始更新';
            showModal('更新失败', '<p style="color:#ef4444;">' + data.message + '</p>', [
                {text: '关闭', class: 'modal-btn-cancel', onclick: 'hideModal()'}
            ]);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<span class="btn-icon">🚀</span> 开始更新';
        showModal('错误', '<p style="color:#ef4444;">网络错误，请稍后重试</p>', [
            {text: '关闭', class: 'modal-btn-cancel', onclick: 'hideModal()'}
        ]);
    });
}

function createBackup() {
    showModal('创建备份', '<p>确定要创建系统备份吗？</p><p>备份包括数据库和程序代码文件。</p><p style="color:#64748b;font-size:12px;">注：logs和uploads目录不会被备份</p>', [
        {text: '取消', class: 'modal-btn-cancel', onclick: 'hideModal()'},
        {text: '确认备份', class: 'modal-btn-confirm', onclick: 'confirmBackup()'}
    ]);
}

function confirmBackup() {
    hideModal();
    
    fetch('<?php echo $this->url("upgrade/backup"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(response => response.json())
    .then(data => {
        if (data.code === 0) {
            showModal('备份成功', '<p style="color:#10b981;">✅ ' + data.message + '</p><p>备份大小：' + data.data.backup_size + '</p>', [
                {text: '确定', class: 'modal-btn-confirm', onclick: 'location.reload()'}
            ]);
        } else {
            showModal('备份失败', '<p style="color:#ef4444;">' + data.message + '</p>', [
                {text: '关闭', class: 'modal-btn-cancel', onclick: 'hideModal()'}
            ]);
        }
    })
    .catch(error => {
        showModal('错误', '<p style="color:#ef4444;">网络错误，请稍后重试</p>', [
            {text: '关闭', class: 'modal-btn-cancel', onclick: 'hideModal()'}
        ]);
    });
}

function restoreBackup(backupName) {
    showModal('恢复备份', '<p>确定要恢复备份 <strong>' + backupName + '</strong> 吗？</p><p style="color:#ef4444;">⚠️ 恢复将覆盖当前系统和数据库</p>', [
        {text: '取消', class: 'modal-btn-cancel', onclick: 'hideModal()'},
        {text: '确认恢复', class: 'modal-btn-danger', onclick: 'confirmRestore(\'' + backupName + '\')'}
    ]);
}

function confirmRestore(backupName) {
    hideModal();
    
    fetch('<?php echo $this->url("upgrade/restore"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'backup_name=' + encodeURIComponent(backupName) + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(response => response.json())
    .then(data => {
        if (data.code === 0) {
            showModal('恢复成功', '<p style="color:#10b981;">✅ ' + data.message + '</p>', [
                {text: '刷新页面', class: 'modal-btn-confirm', onclick: 'location.reload()'}
            ]);
        } else {
            showModal('恢复失败', '<p style="color:#ef4444;">' + data.message + '</p>', [
                {text: '关闭', class: 'modal-btn-cancel', onclick: 'hideModal()'}
            ]);
        }
    })
    .catch(error => {
        showModal('错误', '<p style="color:#ef4444;">网络错误，请稍后重试</p>', [
            {text: '关闭', class: 'modal-btn-cancel', onclick: 'hideModal()'}
        ]);
    });
}

function deleteBackup(backupName) {
    showModal('删除备份', '<p>确定要删除备份 <strong>' + backupName + '</strong> 吗？</p><p style="color:#ef4444;">⚠️ 此操作不可恢复</p>', [
        {text: '取消', class: 'modal-btn-cancel', onclick: 'hideModal()'},
        {text: '确认删除', class: 'modal-btn-danger', onclick: 'confirmDelete(\'' + backupName + '\')'}
    ]);
}

function confirmDelete(backupName) {
    hideModal();
    
    fetch('<?php echo $this->url("upgrade/deleteBackup"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'backup_name=' + encodeURIComponent(backupName) + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(response => response.json())
    .then(data => {
        if (data.code === 0) {
            location.reload();
        } else {
            showModal('删除失败', '<p style="color:#ef4444;">' + data.message + '</p>', [
                {text: '关闭', class: 'modal-btn-cancel', onclick: 'hideModal()'}
            ]);
        }
    })
    .catch(error => {
        showModal('错误', '<p style="color:#ef4444;">网络错误，请稍后重试</p>', [
            {text: '关闭', class: 'modal-btn-cancel', onclick: 'hideModal()'}
        ]);
    });
}

function showModal(title, body, buttons) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML = body;
    
    var footer = document.getElementById('modalFooter');
    footer.innerHTML = '';
    buttons.forEach(function(btn) {
        var button = document.createElement('button');
        button.className = 'modal-btn ' + btn.class;
        button.textContent = btn.text;
        button.setAttribute('onclick', btn.onclick);
        footer.appendChild(button);
    });
    
    document.getElementById('modalOverlay').style.display = 'flex';
}

function hideModal() {
    document.getElementById('modalOverlay').style.display = 'none';
}

document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        hideModal();
    }
});
</script>

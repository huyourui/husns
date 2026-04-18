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
<div class="admin-page">
    <h2>插件管理</h2>
    
    <?php if ($this->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $this->flash('success'); ?></div>
    <?php endif; ?>
    
    <?php if ($this->hasFlash('error')): ?>
    <div class="alert alert-error"><?php echo $this->flash('error'); ?></div>
    <?php endif; ?>
    
    <div class="plugin-list">
        <?php if (empty($plugins)): ?>
        <p class="empty">暂无插件</p>
        <?php else: ?>
        <?php foreach ($plugins as $plugin): ?>
        <div class="plugin-item" data-plugin="<?php echo $this->escape($plugin['name']); ?>">
            <div class="plugin-info">
                <h3><?php echo $this->escape($plugin['info']['title'] ?? $plugin['name']); ?></h3>
                <p class="plugin-desc"><?php echo $this->escape($plugin['info']['description'] ?? ''); ?></p>
                <div class="plugin-meta">
                    <span>版本：<?php echo $this->escape($plugin['info']['version'] ?? '1.0.0'); ?></span>
                    <span>作者：<?php echo $this->escape($plugin['info']['author'] ?? 'Unknown'); ?></span>
                </div>
            </div>
            <div class="plugin-actions">
                <?php if ($plugin['installed']): ?>
                    <?php if ($plugin['status']): ?>
                    <span class="status-badge active">已启用</span>
                    <button class="btn" onclick="disablePlugin('<?php echo $plugin['name']; ?>')">禁用</button>
                    <?php else: ?>
                    <span class="status-badge inactive">已禁用</span>
                    <button class="btn btn-primary" onclick="enablePlugin('<?php echo $plugin['name']; ?>')">启用</button>
                    <?php endif; ?>
                    <button class="btn btn-danger" onclick="uninstallPlugin('<?php echo $plugin['name']; ?>')">卸载</button>
                <?php else: ?>
                <span class="status-badge">未安装</span>
                <button class="btn btn-primary" onclick="installPlugin('<?php echo $plugin['name']; ?>')">安装</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
var csrfToken = '<?php echo $this->csrf(); ?>'.match(/value="([^"]+)"/)[1];

function showMessage(msg, isError) {
    var existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    var div = document.createElement('div');
    div.className = 'alert ' + (isError ? 'alert-error' : 'alert-success');
    div.textContent = msg;
    
    var h2 = document.querySelector('.admin-page h2');
    h2.parentNode.insertBefore(div, h2.nextSibling);
    
    setTimeout(function() {
        div.remove();
    }, 3000);
}

function updatePluginUI(name, installed, status) {
    var item = document.querySelector('.plugin-item[data-plugin="' + name + '"]');
    if (!item) return;
    
    var actionsDiv = item.querySelector('.plugin-actions');
    
    if (!installed) {
        actionsDiv.innerHTML = '<span class="status-badge">未安装</span>' +
            '<button class="btn btn-primary" onclick="installPlugin(\'' + name + '\')">安装</button>';
    } else if (status) {
        actionsDiv.innerHTML = '<span class="status-badge active">已启用</span>' +
            '<button class="btn" onclick="disablePlugin(\'' + name + '\')">禁用</button>' +
            '<button class="btn btn-danger" onclick="uninstallPlugin(\'' + name + '\')">卸载</button>';
    } else {
        actionsDiv.innerHTML = '<span class="status-badge inactive">已禁用</span>' +
            '<button class="btn btn-primary" onclick="enablePlugin(\'' + name + '\')">启用</button>' +
            '<button class="btn btn-danger" onclick="uninstallPlugin(\'' + name + '\')">卸载</button>';
    }
}

function enablePlugin(name) {
    fetch('<?php echo $this->url("plugin/enable"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'name=' + encodeURIComponent(name) + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            showMessage(data.message);
            updatePluginUI(name, true, true);
        } else {
            showMessage(data.message, true);
        }
    });
}

function disablePlugin(name) {
    fetch('<?php echo $this->url("plugin/disable"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'name=' + encodeURIComponent(name) + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            showMessage(data.message);
            updatePluginUI(name, true, false);
        } else {
            showMessage(data.message, true);
        }
    });
}

function installPlugin(name) {
    fetch('<?php echo $this->url("plugin/install"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'name=' + encodeURIComponent(name) + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            showMessage(data.message);
            updatePluginUI(name, true, true);
        } else {
            showMessage(data.message, true);
        }
    });
}

function uninstallPlugin(name) {
    if (!confirm('确定要卸载此插件吗？')) return;
    
    fetch('<?php echo $this->url("plugin/uninstall"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'name=' + encodeURIComponent(name) + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            showMessage(data.message);
            updatePluginUI(name, false, false);
        } else {
            showMessage(data.message, true);
        }
    });
}
</script>

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
    <div class="page-header">
        <h2>话题管理</h2>
    </div>
    
    <div class="topic-settings">
        <div class="form-group">
            <label>热门话题统计天数</label>
            <input type="number" id="topicStatDays" value="<?php echo intval($statDays ?? 7); ?>" min="1" max="365">
            <small>设置热门话题排行榜的数据统计天数，默认7天</small>
        </div>
        <button type="button" class="btn btn-primary" onclick="saveStatDays()">保存设置</button>
    </div>
    
    <div style="margin: 20px 0;">
        <button type="button" class="btn btn-primary" onclick="document.getElementById('addModal').style.display='flex'">添加人工热榜话题</button>
    </div>
    
    <form method="get" class="search-form">
        <input type="hidden" name="r" value="topic">
        <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword ?? ''); ?>" placeholder="搜索话题名称">
        <select name="status">
            <option value="">全部状态</option>
            <option value="pinned" <?php echo ($status ?? '') === 'pinned' ? 'selected' : ''; ?>>人工置顶</option>
            <option value="blocked" <?php echo ($status ?? '') === 'blocked' ? 'selected' : ''; ?>>已屏蔽</option>
            <option value="normal" <?php echo ($status ?? '') === 'normal' ? 'selected' : ''; ?>>正常</option>
        </select>
        <button type="submit" class="btn">搜索</button>
    </form>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>话题名称</th>
                <th>微博数</th>
                <th>状态</th>
                <th>排序</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($topics)): ?>
            <tr>
                <td colspan="6" style="text-align: center; color: #999;">暂无数据</td>
            </tr>
            <?php else: ?>
            <?php foreach ($topics as $topic): ?>
            <tr data-name="<?php echo htmlspecialchars($topic['name']); ?>">
                <td><?php echo $topic['id'] ? intval($topic['id']) : '-'; ?></td>
                <td>#<?php echo htmlspecialchars($topic['name']); ?>#</td>
                <td><?php echo intval($topic['count']); ?></td>
                <td>
                    <?php if (!empty($topic['is_blocked'])): ?>
                    <span class="status-badge status-blocked">已屏蔽</span>
                    <?php elseif (!empty($topic['is_pinned'])): ?>
                    <span class="status-badge status-pinned">人工置顶</span>
                    <?php else: ?>
                    <span class="status-badge status-normal">正常</span>
                    <?php endif; ?>
                </td>
                <td><?php echo intval($topic['sort_order']); ?></td>
                <td class="actions">
                    <?php if (!empty($topic['is_blocked'])): ?>
                    <button type="button" class="btn btn-sm" onclick="unblockTopic('<?php echo htmlspecialchars($topic['name']); ?>')">取消屏蔽</button>
                    <?php else: ?>
                    <?php if (empty($topic['is_pinned'])): ?>
                    <button type="button" class="btn btn-sm btn-primary" onclick="pinTopic('<?php echo htmlspecialchars($topic['name']); ?>')">置顶</button>
                    <?php else: ?>
                    <button type="button" class="btn btn-sm" onclick="unpinTopic('<?php echo htmlspecialchars($topic['name']); ?>')">取消置顶</button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-danger" onclick="blockTopic('<?php echo htmlspecialchars($topic['name']); ?>')">屏蔽</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if (!empty($totalPages) && $totalPages > 1): ?>
    <div class="pagination">
        <?php if (($page ?? 1) > 1): ?>
        <a href="?r=topic&page=<?php echo $page - 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&status=<?php echo htmlspecialchars($status ?? ''); ?>" class="btn">上一页</a>
        <?php endif; ?>
        
        <span class="page-info">第 <?php echo $page ?? 1; ?> / <?php echo $totalPages; ?> 页</span>
        
        <?php if (($page ?? 1) < $totalPages): ?>
        <a href="?r=topic&page=<?php echo $page + 1; ?>&keyword=<?php echo urlencode($keyword ?? ''); ?>&status=<?php echo htmlspecialchars($status ?? ''); ?>" class="btn">下一页</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>添加人工热榜话题</h3>
            <span class="modal-close" onclick="this.closest('.modal').style.display='none'">&times;</span>
        </div>
        <div class="form-group">
            <label>话题名称</label>
            <input type="text" id="topicName" placeholder="输入话题名称（可带#也可不带）">
        </div>
        <div class="form-group">
            <label>排序（数字越小越靠前）</label>
            <input type="number" id="topicSortOrder" value="0" min="0">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" onclick="this.closest('.modal').style.display='none'">取消</button>
            <button type="button" class="btn btn-primary" onclick="createTopic()">添加</button>
        </div>
    </div>
</div>

<style>
.topic-settings {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.topic-settings .form-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}
.topic-settings .form-group label {
    width: 140px;
    flex-shrink: 0;
}
.topic-settings .form-group input {
    width: 100px;
}
.topic-settings .form-group small {
    color: #666;
}
.status-badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
}
.status-badge.status-pinned {
    background: #dbeafe;
    color: #1d4ed8;
}
.status-badge.status-blocked {
    background: #fee2e2;
    color: #dc2626;
}
.status-badge.status-normal {
    background: #f3f4f6;
    color: #6b7280;
}
body.dark-mode .topic-settings {
    background: #1e293b;
}
body.dark-mode .topic-settings .form-group small {
    color: #94a3b8;
}
</style>

<script>
function saveStatDays() {
    var days = document.getElementById('topicStatDays').value;
    var csrf = '<?php echo isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : ""; ?>';
    
    fetch('<?php echo $this->url("topic/saveStatDays"); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'days=' + encodeURIComponent(days) + '&csrf_token=' + encodeURIComponent(csrf)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) { alert(data.message || '操作完成'); })
    .catch(function() { alert('请求失败'); });
}

function createTopic() {
    var name = document.getElementById('topicName').value;
    var sortOrder = document.getElementById('topicSortOrder').value;
    var csrf = '<?php echo isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : ""; ?>';
    
    if (!name.trim()) { alert('请输入话题名称'); return; }
    
    fetch('<?php echo $this->url("topic/create"); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'name=' + encodeURIComponent(name) + '&sort_order=' + encodeURIComponent(sortOrder) + '&csrf_token=' + encodeURIComponent(csrf)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) { 
        if(data.code === 0) {
            location.reload();
        } else {
            alert(data.message || '操作失败');
        }
    })
    .catch(function() { alert('请求失败'); });
}

function updateRowStatus(row, isPinned, isBlocked) {
    var statusCell = row.querySelector('td:nth-child(4)');
    var actionsCell = row.querySelector('td:nth-child(6)');
    var name = row.dataset.name;
    
    if (isBlocked) {
        statusCell.innerHTML = '<span class="status-badge status-blocked">已屏蔽</span>';
        actionsCell.innerHTML = '<button type="button" class="btn btn-sm" onclick="unblockTopic(\'' + name.replace(/'/g, "\\'") + '\')">取消屏蔽</button>';
    } else if (isPinned) {
        statusCell.innerHTML = '<span class="status-badge status-pinned">人工置顶</span>';
        actionsCell.innerHTML = '<button type="button" class="btn btn-sm" onclick="unpinTopic(\'' + name.replace(/'/g, "\\'") + '\')">取消置顶</button><button type="button" class="btn btn-sm btn-danger" onclick="blockTopic(\'' + name.replace(/'/g, "\\'") + '\')">屏蔽</button>';
    } else {
        statusCell.innerHTML = '<span class="status-badge status-normal">正常</span>';
        actionsCell.innerHTML = '<button type="button" class="btn btn-sm btn-primary" onclick="pinTopic(\'' + name.replace(/'/g, "\\'") + '\')">置顶</button><button type="button" class="btn btn-sm btn-danger" onclick="blockTopic(\'' + name.replace(/'/g, "\\'") + '\')">屏蔽</button>';
    }
}

function pinTopic(name) {
    var csrf = '<?php echo isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : ""; ?>';
    var row = document.querySelector('tr[data-name="' + name.replace(/"/g, '\\"') + '"]');
    
    fetch('<?php echo $this->url("topic/pin"); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'name=' + encodeURIComponent(name) + '&sort_order=0&csrf_token=' + encodeURIComponent(csrf)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) { 
        if(data.code === 0) {
            updateRowStatus(row, true, false);
        } else {
            alert(data.message || '操作失败');
        }
    })
    .catch(function() { alert('请求失败'); });
}

function unpinTopic(name) {
    var csrf = '<?php echo isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : ""; ?>';
    var row = document.querySelector('tr[data-name="' + name.replace(/"/g, '\\"') + '"]');
    
    fetch('<?php echo $this->url("topic/unpin"); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'name=' + encodeURIComponent(name) + '&csrf_token=' + encodeURIComponent(csrf)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) { 
        if(data.code === 0) {
            updateRowStatus(row, false, false);
        } else {
            alert(data.message || '操作失败');
        }
    })
    .catch(function() { alert('请求失败'); });
}

function blockTopic(name) {
    var csrf = '<?php echo isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : ""; ?>';
    var row = document.querySelector('tr[data-name="' + name.replace(/"/g, '\\"') + '"]');
    
    fetch('<?php echo $this->url("topic/block"); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'name=' + encodeURIComponent(name) + '&csrf_token=' + encodeURIComponent(csrf)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) { 
        if(data.code === 0) {
            updateRowStatus(row, false, true);
        } else {
            alert(data.message || '操作失败');
        }
    })
    .catch(function() { alert('请求失败'); });
}

function unblockTopic(name) {
    var csrf = '<?php echo isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : ""; ?>';
    var row = document.querySelector('tr[data-name="' + name.replace(/"/g, '\\"') + '"]');
    
    fetch('<?php echo $this->url("topic/unblock"); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'name=' + encodeURIComponent(name) + '&csrf_token=' + encodeURIComponent(csrf)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) { 
        if(data.code === 0) {
            updateRowStatus(row, false, false);
        } else {
            alert(data.message || '操作失败');
        }
    })
    .catch(function() { alert('请求失败'); });
}
</script>

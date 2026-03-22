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
        <h2>邀请码管理</h2>
    </div>
    
    <div class="invite-settings">
        <div class="form-group">
            <label>生成数量</label>
            <input type="number" id="generateCount" value="10" min="1" max="100">
            <small>一次最多生成100个邀请码</small>
        </div>
        <button type="button" class="btn btn-primary" onclick="generateCodes()">生成邀请码</button>
        <button type="button" class="btn btn-danger" onclick="clearAllCodes()">清空全部</button>
    </div>
    
    <?php if (!empty($codes)): ?>
    <div class="invite-actions">
        <button type="button" class="btn btn-sm" onclick="copyAllCodes()">复制全部邀请码</button>
    </div>
    <?php endif; ?>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>邀请码</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($codes)): ?>
            <tr>
                <td colspan="4" style="text-align: center; color: #999;">暂无邀请码</td>
            </tr>
            <?php else: ?>
            <?php foreach ($codes as $code): ?>
            <tr data-id="<?php echo intval($code['id']); ?>" data-code="<?php echo htmlspecialchars($code['code']); ?>">
                <td><?php echo intval($code['id']); ?></td>
                <td>
                    <code class="invite-code"><?php echo htmlspecialchars($code['code']); ?></code>
                    <button type="button" class="btn btn-sm copy-btn" onclick="copyCode(this)">复制</button>
                </td>
                <td><?php echo date('Y-m-d H:i:s', $code['created_at']); ?></td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteCode(<?php echo intval($code['id']); ?>)">删除</button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if (!empty($totalPages) && $totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?r=invite&page=<?php echo $page - 1; ?>" class="btn">上一页</a>
        <?php endif; ?>
        
        <span class="page-info">第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页（共 <?php echo intval($total); ?> 个）</span>
        
        <?php if ($page < $totalPages): ?>
        <a href="?r=invite&page=<?php echo $page + 1; ?>" class="btn">下一页</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.invite-settings {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}
.invite-settings .form-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}
.invite-settings .form-group label {
    width: auto;
    flex-shrink: 0;
}
.invite-settings .form-group input {
    width: 80px;
}
.invite-settings .form-group small {
    color: #666;
}
.invite-actions {
    margin-bottom: 15px;
}
.invite-code {
    font-family: monospace;
    font-size: 14px;
    background: #f0f0f0;
    padding: 4px 8px;
    border-radius: 4px;
    letter-spacing: 1px;
}
.copy-btn {
    margin-left: 8px;
    padding: 2px 8px;
    font-size: 12px;
}
body.dark-mode .invite-settings {
    background: #1e293b;
}
body.dark-mode .invite-settings .form-group small {
    color: #94a3b8;
}
body.dark-mode .invite-code {
    background: #334155;
}
</style>

<script>
function generateCodes() {
    var count = document.getElementById('generateCount').value;
    var csrf = '<?php echo isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : ""; ?>';
    
    fetch('<?php echo $this->url("invite/generate"); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'count=' + encodeURIComponent(count) + '&csrf_token=' + encodeURIComponent(csrf)
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

function deleteCode(id) {
    var csrf = '<?php echo isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : ""; ?>';
    
    fetch('<?php echo $this->url("invite/delete"); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id + '&csrf_token=' + encodeURIComponent(csrf)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if(data.code === 0) {
            var row = document.querySelector('tr[data-id="' + id + '"]');
            if (row) row.remove();
        } else {
            alert(data.message || '删除失败');
        }
    })
    .catch(function() { alert('请求失败'); });
}

function clearAllCodes() {
    if (!confirm('确定要清空所有未使用的邀请码吗？')) return;
    
    var csrf = '<?php echo isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : ""; ?>';
    
    fetch('<?php echo $this->url("invite/clear"); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent(csrf)
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

function copyCode(btn) {
    var code = btn.parentElement.querySelector('.invite-code').textContent;
    navigator.clipboard.writeText(code).then(function() {
        btn.textContent = '已复制';
        setTimeout(function() { btn.textContent = '复制'; }, 1500);
    }).catch(function() {
        alert('复制失败');
    });
}

function copyAllCodes() {
    var codes = [];
    document.querySelectorAll('tr[data-code]').forEach(function(row) {
        codes.push(row.dataset.code);
    });
    
    if (codes.length === 0) {
        alert('没有可复制的邀请码');
        return;
    }
    
    navigator.clipboard.writeText(codes.join('\n')).then(function() {
        alert('已复制 ' + codes.length + ' 个邀请码');
    }).catch(function() {
        alert('复制失败');
    });
}
</script>

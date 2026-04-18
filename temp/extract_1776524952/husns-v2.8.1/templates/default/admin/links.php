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
        <h2>友情链接</h2>
    </div>
    
    <?php if ($this->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $this->flash('success'); ?></div>
    <?php endif; ?>
    
    <?php if ($this->hasFlash('error')): ?>
    <div class="alert alert-error"><?php echo $this->flash('error'); ?></div>
    <?php endif; ?>

    <div class="page-actions">
        <button class="btn btn-primary" onclick="showAddModal()">添加链接</button>
    </div>

    <?php if (empty($links)): ?>
    <p class="empty">暂无友情链接</p>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>网站名称</th>
                <th>网站地址</th>
                <th>描述</th>
                <th>排序</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($links as $link): ?>
            <tr data-id="<?php echo $link['id']; ?>">
                <td><?php echo $link['id']; ?></td>
                <td><strong><?php echo $this->escape($link['name']); ?></strong></td>
                <td><a href="<?php echo $this->escape($link['url']); ?>" target="_blank" rel="noopener"><?php echo $this->escape($link['url']); ?></a></td>
                <td><?php echo $this->escape($link['description'] ?? '-'); ?></td>
                <td><?php echo $link['sort_order']; ?></td>
                <td>
                    <span class="status-badge <?php echo $link['status'] ? 'active' : 'inactive'; ?>">
                        <?php echo $link['status'] ? '启用' : '禁用'; ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm" onclick='showEditModal(<?php echo json_encode($link); ?>)'>编辑</button>
                    <button class="btn btn-sm" onclick="toggleStatus(<?php echo $link['id']; ?>, this)">
                        <?php echo $link['status'] ? '禁用' : '启用'; ?>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteLink(<?php echo $link['id']; ?>)">删除</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div id="linkModal" class="modal" style="display:none;">
    <div class="modal-content" style="width:500px;">
        <div class="modal-header">
            <h3 id="modalTitle">添加链接</h3>
            <span class="modal-close" onclick="closeModal()">&times;</span>
        </div>
        <form id="linkForm">
            <input type="hidden" name="id" id="linkId">
            <div class="form-group">
                <label>网站名称</label>
                <input type="text" name="name" id="linkName" required>
            </div>
            <div class="form-group">
                <label>网站地址</label>
                <input type="url" name="url" id="linkUrl" placeholder="https://" required>
            </div>
            <div class="form-group">
                <label>网站描述</label>
                <input type="text" name="description" id="linkDescription">
            </div>
            <div class="form-group">
                <label>排序</label>
                <input type="number" name="sort_order" id="linkSortOrder" value="0" min="0">
                <small>数字越小越靠前</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal()">取消</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 90%;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}
.modal-header h3 {
    margin: 0;
}
.modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #999;
}
.modal-close:hover {
    color: #333;
}
.modal-content form {
    padding: 20px;
}
.modal-content .form-group {
    margin-bottom: 15px;
}
.modal-content .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
.modal-content .form-group input {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}
.modal-content .form-group small {
    color: #888;
    font-size: 12px;
}
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
    margin-top: 10px;
}
</style>

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

function showAddModal() {
    document.getElementById('modalTitle').textContent = '添加链接';
    document.getElementById('linkId').value = '';
    document.getElementById('linkName').value = '';
    document.getElementById('linkUrl').value = '';
    document.getElementById('linkDescription').value = '';
    document.getElementById('linkSortOrder').value = '0';
    document.getElementById('linkModal').style.display = 'flex';
}

function showEditModal(link) {
    document.getElementById('modalTitle').textContent = '编辑链接';
    document.getElementById('linkId').value = link.id;
    document.getElementById('linkName').value = link.name;
    document.getElementById('linkUrl').value = link.url;
    document.getElementById('linkDescription').value = link.description || '';
    document.getElementById('linkSortOrder').value = link.sort_order;
    document.getElementById('linkModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('linkModal').style.display = 'none';
}

document.getElementById('linkForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    formData.append('csrf_token', csrfToken);
    
    var id = formData.get('id');
    var url = id ? '<?php echo $this->url("link/edit"); ?>' : '<?php echo $this->url("link/add"); ?>';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            showMessage(data.message);
            closeModal();
            setTimeout(function() { location.reload(); }, 500);
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(function() {
        showMessage('操作失败', true);
    });
});

function toggleStatus(id, btn) {
    btn.disabled = true;
    
    fetch('<?php echo $this->url("link/toggle"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + id + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        if (data.code === 0) {
            showMessage(data.message);
            var row = document.querySelector('tr[data-id="' + id + '"]');
            if (row) {
                var badge = row.querySelector('.status-badge');
                badge.className = 'status-badge ' + (data.data.status ? 'active' : 'inactive');
                badge.textContent = data.data.status ? '启用' : '禁用';
                btn.textContent = data.data.status ? '禁用' : '启用';
            }
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(function() {
        btn.disabled = false;
        showMessage('操作失败', true);
    });
}

function deleteLink(id) {
    if (!confirm('确定要删除这个链接吗？')) return;
    
    fetch('<?php echo $this->url("link/delete"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + id + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            showMessage(data.message);
            var row = document.querySelector('tr[data-id="' + id + '"]');
            if (row) {
                row.remove();
            }
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(function() {
        showMessage('操作失败', true);
    });
}
</script>

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
        <h2>公告管理</h2>
    </div>
    
    <?php if ($this->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $this->flash('success'); ?></div>
    <?php endif; ?>
    
    <?php if ($this->hasFlash('error')): ?>
    <div class="alert alert-error"><?php echo $this->flash('error'); ?></div>
    <?php endif; ?>

    <div class="page-actions">
        <button class="btn btn-primary" onclick="showAddModal()">添加公告</button>
    </div>

    <?php if (empty($announcements)): ?>
    <p class="empty">暂无公告</p>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>内容预览</th>
                <th>颜色</th>
                <th>排序</th>
                <th>状态</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($announcements as $announcement): ?>
            <tr data-id="<?php echo $announcement['id']; ?>">
                <td><?php echo $announcement['id']; ?></td>
                <td>
                    <div class="announcement-preview announcement-<?php echo $announcement['color']; ?>">
                        <?php echo $announcement['content']; ?>
                    </div>
                </td>
                <td>
                    <span class="color-badge color-<?php echo $announcement['color']; ?>">
                        <?php 
                        $colorNames = [
                            'blue' => '蓝色',
                            'green' => '绿色',
                            'yellow' => '黄色',
                            'red' => '红色',
                            'purple' => '紫色',
                            'cyan' => '青色'
                        ];
                        echo $colorNames[$announcement['color']] ?? $announcement['color'];
                        ?>
                    </span>
                </td>
                <td><?php echo $announcement['sort_order']; ?></td>
                <td>
                    <span class="status-badge <?php echo $announcement['status'] ? 'active' : 'inactive'; ?>">
                        <?php echo $announcement['status'] ? '启用' : '禁用'; ?>
                    </span>
                </td>
                <td><?php echo date('Y-m-d H:i', $announcement['created_at']); ?></td>
                <td>
                    <button class="btn btn-sm" onclick='showEditModal(<?php echo json_encode($announcement); ?>)'>编辑</button>
                    <button class="btn btn-sm" onclick="toggleStatus(<?php echo $announcement['id']; ?>, this)">
                        <?php echo $announcement['status'] ? '禁用' : '启用'; ?>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)">删除</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div id="announcementModal" class="modal" style="display:none;">
    <div class="modal-content" style="width:600px;">
        <div class="modal-header">
            <h3 id="modalTitle">添加公告</h3>
            <span class="modal-close" onclick="closeModal()">&times;</span>
        </div>
        <form id="announcementForm">
            <input type="hidden" name="id" id="announcementId">
            <div class="form-group">
                <label>公告内容（支持HTML）</label>
                <textarea name="content" id="announcementContent" rows="4" style="width:100%;" placeholder="请输入公告内容..."></textarea>
            </div>
            <div class="form-group">
                <label>背景颜色</label>
                <select name="color" id="colorSelect" class="color-select">
                    <option value="blue" data-color="blue">蓝色</option>
                    <option value="green" data-color="green">绿色</option>
                    <option value="yellow" data-color="yellow">黄色</option>
                    <option value="red" data-color="red">红色</option>
                    <option value="purple" data-color="purple">紫色</option>
                    <option value="cyan" data-color="cyan">青色</option>
                </select>
                <div class="color-preview-box" id="colorPreviewBox">
                    <span class="preview-text">预览效果</span>
                </div>
            </div>
            <div class="form-group">
                <label>排序（数字越小越靠前）</label>
                <input type="number" name="sort_order" id="announcementSortOrder" value="0" min="0">
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
.modal-content .form-group input,
.modal-content .form-group textarea,
.modal-content .form-group select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
    margin-top: 10px;
}
.color-select {
    cursor: pointer;
}
.color-preview-box {
    margin-top: 10px;
    padding: 12px 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
}
.color-preview-box.announcement-blue {
    background: linear-gradient(135deg, #e3f2fd 0%, #d1e8ff 100%);
    border-left: 4px solid #3b82f6;
    color: #1e40af;
}
.color-preview-box.announcement-green {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    border-left: 4px solid #22c55e;
    color: #166534;
}
.color-preview-box.announcement-yellow {
    background: linear-gradient(135deg, #fef9c3 0%, #fef08a 100%);
    border-left: 4px solid #eab308;
    color: #854d0e;
}
.color-preview-box.announcement-red {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-left: 4px solid #ef4444;
    color: #991b1b;
}
.color-preview-box.announcement-purple {
    background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
    border-left: 4px solid #a855f7;
    color: #6b21a8;
}
.color-preview-box.announcement-cyan {
    background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%);
    border-left: 4px solid #06b6d4;
    color: #0e7491;
}
</style>

<script>
var csrfToken = '<?php echo $this->csrf(); ?>'.match(/value="([^"]+)"/)[1];

function updateColorPreview() {
    var select = document.getElementById('colorSelect');
    var previewBox = document.getElementById('colorPreviewBox');
    var color = select.value;
    
    previewBox.className = 'color-preview-box announcement-' + color;
}

document.getElementById('colorSelect').addEventListener('change', updateColorPreview);

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
    document.getElementById('modalTitle').textContent = '添加公告';
    document.getElementById('announcementId').value = '';
    document.getElementById('announcementContent').value = '';
    document.getElementById('announcementSortOrder').value = '0';
    document.getElementById('colorSelect').value = 'blue';
    updateColorPreview();
    document.getElementById('announcementModal').style.display = 'flex';
}

function showEditModal(announcement) {
    document.getElementById('modalTitle').textContent = '编辑公告';
    document.getElementById('announcementId').value = announcement.id;
    document.getElementById('announcementContent').value = announcement.content;
    document.getElementById('announcementSortOrder').value = announcement.sort_order;
    document.getElementById('colorSelect').value = announcement.color;
    updateColorPreview();
    document.getElementById('announcementModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('announcementModal').style.display = 'none';
}

document.getElementById('announcementForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    formData.append('csrf_token', csrfToken);
    
    var id = formData.get('id');
    var url = id ? '<?php echo $this->url("announcement/edit"); ?>' : '<?php echo $this->url("announcement/add"); ?>';
    
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
    
    fetch('<?php echo $this->url("announcement/toggle"); ?>', {
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

function deleteAnnouncement(id) {
    if (!confirm('确定要删除这条公告吗？')) return;
    
    fetch('<?php echo $this->url("announcement/delete"); ?>', {
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

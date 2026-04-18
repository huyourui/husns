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
        <h2><?php echo $this->escape(Setting::getPointName()); ?>管理</h2>
    </div>
    
    <?php if ($this->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $this->flash('success'); ?></div>
    <?php endif; ?>
    
    <?php if ($this->hasFlash('error')): ?>
    <div class="alert alert-error"><?php echo $this->flash('error'); ?></div>
    <?php endif; ?>

    <div class="page-actions">
        <button class="btn btn-primary" onclick="showAddModal()">添加规则</button>
    </div>

    <div class="point-tips">
        <p><strong>说明：</strong></p>
        <ul>
            <li><?php echo $this->escape(Setting::getPointName()); ?>正数表示增加，负数表示扣减</li>
            <li>每日上限为0表示不限制，大于0表示每天最多获得该次数的<?php echo $this->escape(Setting::getPointName()); ?></li>
            <li>动作标识用于程序调用，建议使用英文下划线格式，如：publish_post</li>
        </ul>
    </div>

    <?php if (empty($rules)): ?>
    <p class="empty">暂无积分规则</p>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>动作标识</th>
                <th>动作名称</th>
                <th><?php echo $this->escape(Setting::getPointName()); ?>变化</th>
                <th>每日上限</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rules as $rule): ?>
            <tr data-id="<?php echo $rule['id']; ?>">
                <td><?php echo $rule['id']; ?></td>
                <td><code><?php echo $this->escape($rule['action']); ?></code></td>
                <td><?php echo $this->escape($rule['name']); ?></td>
                <td>
                    <span class="point-badge <?php echo $rule['points'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $rule['points'] >= 0 ? '+' . $rule['points'] : $rule['points']; ?>
                    </span>
                </td>
                <td><?php echo $rule['daily_limit'] == 0 ? '不限' : $rule['daily_limit'] . '次'; ?></td>
                <td>
                    <span class="status-badge <?php echo $rule['status'] ? 'active' : 'inactive'; ?>">
                        <?php echo $rule['status'] ? '启用' : '禁用'; ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm" onclick='showEditModal(<?php echo json_encode($rule); ?>)'>编辑</button>
                    <button class="btn btn-sm" onclick="toggleStatus(<?php echo $rule['id']; ?>, this)">
                        <?php echo $rule['status'] ? '禁用' : '启用'; ?>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteRule(<?php echo $rule['id']; ?>)">删除</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div id="ruleModal" class="modal" style="display:none;">
    <div class="modal-content" style="width:500px;">
        <div class="modal-header">
            <h3 id="modalTitle">添加规则</h3>
            <span class="modal-close" onclick="closeModal()">&times;</span>
        </div>
        <form id="ruleForm">
            <input type="hidden" name="id" id="ruleId">
            <div class="form-group">
                <label>动作标识</label>
                <input type="text" name="action" id="ruleAction" placeholder="如：publish_post">
                <small>只能包含小写字母和下划线，创建后不可修改</small>
            </div>
            <div class="form-group">
                <label>动作名称</label>
                <input type="text" name="name" id="ruleName" placeholder="如：发布微博">
            </div>
            <div class="form-group">
                <label><?php echo $this->escape(Setting::getPointName()); ?>变化</label>
                <input type="number" name="points" id="rulePoints" value="0">
                <small>正数增加<?php echo $this->escape(Setting::getPointName()); ?>，负数扣减<?php echo $this->escape(Setting::getPointName()); ?></small>
            </div>
            <div class="form-group">
                <label>每日上限</label>
                <input type="number" name="daily_limit" id="ruleDailyLimit" value="0" min="0">
                <small>0表示不限制</small>
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
.point-tips {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.point-tips p {
    margin: 0 0 10px 0;
}
.point-tips ul {
    margin: 0;
    padding-left: 20px;
}
.point-tips li {
    margin-bottom: 5px;
    color: #666;
}
.point-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: bold;
}
.point-badge.positive {
    background: #d4edda;
    color: #155724;
}
.point-badge.negative {
    background: #f8d7da;
    color: #721c24;
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
    document.getElementById('modalTitle').textContent = '添加规则';
    document.getElementById('ruleId').value = '';
    document.getElementById('ruleAction').value = '';
    document.getElementById('ruleAction').readOnly = false;
    document.getElementById('ruleName').value = '';
    document.getElementById('rulePoints').value = '0';
    document.getElementById('ruleDailyLimit').value = '0';
    document.getElementById('ruleModal').style.display = 'flex';
}

function showEditModal(rule) {
    document.getElementById('modalTitle').textContent = '编辑规则';
    document.getElementById('ruleId').value = rule.id;
    document.getElementById('ruleAction').value = rule.action;
    document.getElementById('ruleAction').readOnly = true;
    document.getElementById('ruleName').value = rule.name;
    document.getElementById('rulePoints').value = rule.points;
    document.getElementById('ruleDailyLimit').value = rule.daily_limit;
    document.getElementById('ruleModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('ruleModal').style.display = 'none';
}

document.getElementById('ruleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    formData.append('csrf_token', csrfToken);
    
    var id = formData.get('id');
    var url = id ? '<?php echo $this->url("point/edit"); ?>' : '<?php echo $this->url("point/add"); ?>';
    
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
    
    fetch('<?php echo $this->url("point/toggle"); ?>', {
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

function deleteRule(id) {
    if (!confirm('确定要删除这条规则吗？')) return;
    
    fetch('<?php echo $this->url("point/delete"); ?>', {
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

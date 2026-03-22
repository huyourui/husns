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
        <h2>用户管理</h2>
        <form method="get" class="search-form">
            <input type="hidden" name="r" value="admin/users">
            <input type="text" name="keyword" value="<?php echo $this->escape($keyword); ?>" placeholder="搜索用户名或邮箱">
            <button type="submit" class="btn">搜索</button>
        </form>
    </div>
    
    <?php if ($this->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $this->flash('success'); ?></div>
    <?php endif; ?>
    
    <?php if ($this->hasFlash('error')): ?>
    <div class="alert alert-error"><?php echo $this->flash('error'); ?></div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>邮箱</th>
                <th>管理员</th>
                <th>状态</th>
                <th>限制状态</th>
                <th>注册时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users['items'] as $user): ?>
            <tr data-user-id="<?php echo $user['id']; ?>">
                <td><?php echo $user['id']; ?></td>
                <td><?php echo $this->escape($user['username']); ?></td>
                <td><?php echo $this->escape($user['email']); ?></td>
                <td><?php echo $user['is_admin'] ? '是' : '否'; ?></td>
                <td>
                    <span class="status-badge <?php echo $user['status'] ? 'active' : 'inactive'; ?>">
                        <?php echo $user['status'] ? '正常' : '封禁'; ?>
                    </span>
                </td>
                <td>
                    <?php 
                    $banType = isset($user['ban_type']) ? (int)$user['ban_type'] : 0;
                    $banUntil = isset($user['ban_until']) ? (int)$user['ban_until'] : 0;
                    $banReason = isset($user['ban_reason']) ? $user['ban_reason'] : '';
                    
                    if ($banType === 1) {
                        $isExpired = $banUntil > 0 && $banUntil < time();
                        if ($isExpired) {
                            echo '<span class="status-badge">正常</span>';
                        } else {
                            $untilText = $banUntil === 0 ? '永久' : date('Y-m-d', $banUntil);
                            echo '<span class="status-badge inactive" title="' . $this->escape($banReason) . '">禁言至' . $untilText . '</span>';
                        }
                    } elseif ($banType === 2) {
                        echo '<span class="status-badge inactive" title="' . $this->escape($banReason) . '">已封禁</span>';
                    } else {
                        echo '<span class="status-badge active">正常</span>';
                    }
                    ?>
                </td>
                <td><?php echo date('Y-m-d H:i', $user['created_at']); ?></td>
                <td>
                    <?php if (!$user['is_admin']): ?>
                    <button class="btn btn-sm" onclick="editUser(<?php echo $user['id']; ?>)">编辑</button>
                    <button class="btn btn-sm" onclick="showBanModal(<?php echo $user['id']; ?>, <?php echo $banType; ?>, <?php echo $banUntil; ?>, '<?php echo $this->escape($banReason); ?>')">禁言</button>
                    <button class="btn btn-sm" onclick="toggleStatus(<?php echo $user['id']; ?>, <?php echo $user['status'] ? 0 : 1; ?>, this)">
                        <?php echo $user['status'] ? '封禁' : '解封'; ?>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo $this->escape($user['username']); ?>')">删除</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($users['totalPages'] > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $users['totalPages']; $i++): ?>
        <a href="?r=admin/users&page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword); ?>" class="<?php echo $i == $users['page'] ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>编辑用户</h3>
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
        </div>
        <form id="editForm">
            <input type="hidden" name="id" id="editUserId">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" id="editUsername">
            </div>
            <div class="form-group">
                <label>邮箱</label>
                <input type="email" name="email" id="editEmail">
            </div>
            <div class="form-group">
                <label>个人简介</label>
                <textarea name="bio" id="editBio" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>新密码（留空不修改）</label>
                <input type="password" name="password" id="editPassword" placeholder="留空则不修改密码">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeEditModal()">取消</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<div id="banModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>禁言设置</h3>
            <span class="modal-close" onclick="closeBanModal()">&times;</span>
        </div>
        <form id="banForm">
            <input type="hidden" name="id" id="banUserId">
            <div class="form-group">
                <label>禁言天数</label>
                <select name="ban_days" id="banDays">
                    <option value="0">永久禁言</option>
                    <option value="1">1天</option>
                    <option value="3">3天</option>
                    <option value="7">7天</option>
                    <option value="30">30天</option>
                    <option value="90">90天</option>
                    <option value="-1">解除禁言</option>
                </select>
            </div>
            <div class="form-group">
                <label>禁言原因</label>
                <textarea name="ban_reason" id="banReason" rows="3" placeholder="请填写禁言原因"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeBanModal()">取消</button>
                <button type="submit" class="btn btn-primary">确定</button>
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
    width: 400px;
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
.btn-danger {
    background: #dc3545;
    color: #fff;
    border-color: #dc3545;
}
.btn-danger:hover {
    background: #c82333;
    border-color: #bd2130;
}
</style>

<script>
var csrfToken = '<?php echo $this->csrf(); ?>'.match(/value="([^"]+)"/)[1];
var usersData = <?php echo json_encode(array_column($users['items'], null, 'id')); ?>;

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

function toggleStatus(id, status, btn) {
    var confirmMsg = status ? '确定要解封该用户吗？' : '确定要封禁该用户吗？';
    if (!confirm(confirmMsg)) return;
    
    btn.disabled = true;
    
    fetch('<?php echo $this->url("admin/userStatus"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'id=' + id + '&status=' + status + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        if (data.code === 0) {
            showMessage(data.message);
            var row = document.querySelector('tr[data-user-id="' + id + '"]');
            if (row) {
                var badge = row.querySelector('.status-badge');
                badge.className = 'status-badge ' + (status ? 'active' : 'inactive');
                badge.textContent = status ? '正常' : '封禁';
                btn.textContent = status ? '封禁' : '解封';
                btn.setAttribute('onclick', 'toggleStatus(' + id + ', ' + (status ? 0 : 1) + ', this)');
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

function editUser(id) {
    var user = usersData[id];
    if (!user) return;
    
    document.getElementById('editUserId').value = id;
    document.getElementById('editUsername').value = user.username || '';
    document.getElementById('editEmail').value = user.email || '';
    document.getElementById('editBio').value = user.bio || '';
    document.getElementById('editPassword').value = '';
    
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    formData.append('csrf_token', csrfToken);
    
    fetch('<?php echo $this->url("admin/userEdit"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            showMessage(data.message);
            closeEditModal();
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(function() {
        showMessage('操作失败', true);
    });
});

function showBanModal(id, banType, banUntil, banReason) {
    document.getElementById('banUserId').value = id;
    document.getElementById('banReason').value = banReason || '';
    
    var banDaysSelect = document.getElementById('banDays');
    if (banType === 1 && banUntil > 0) {
        var daysLeft = Math.ceil((banUntil - Date.now() / 1000) / 86400);
        if (daysLeft > 0) {
            for (var i = 0; i < banDaysSelect.options.length; i++) {
                if (banDaysSelect.options[i].value == daysLeft) {
                    banDaysSelect.selectedIndex = i;
                    break;
                }
            }
        }
    } else if (banType === 1 && banUntil === 0) {
        banDaysSelect.value = '0';
    } else {
        banDaysSelect.value = '7';
    }
    
    document.getElementById('banModal').style.display = 'flex';
}

function closeBanModal() {
    document.getElementById('banModal').style.display = 'none';
}

document.getElementById('banForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    formData.append('csrf_token', csrfToken);
    
    fetch('<?php echo $this->url("admin/userBan"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            showMessage(data.message);
            closeBanModal();
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(function() {
        showMessage('操作失败', true);
    });
});

function deleteUser(id, username) {
    if (!confirm('确定要删除用户 "' + username + '" 吗？\n\n警告：此操作将删除该用户的所有数据，包括微博、评论、点赞、关注等，且不可恢复！')) {
        return;
    }
    
    if (!confirm('再次确认：您真的要删除用户 "' + username + '" 吗？此操作不可撤销！')) {
        return;
    }
    
    fetch('<?php echo $this->url("admin/userDelete"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'id=' + id + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(function(r) { 
        if (!r.ok) {
            throw new Error('HTTP error ' + r.status);
        }
        return r.json(); 
    })
    .then(function(data) {
        if (data.code === 0) {
            showMessage(data.message);
            var row = document.querySelector('tr[data-user-id="' + id + '"]');
            if (row) {
                row.style.transition = 'opacity 0.3s';
                row.style.opacity = '0';
                setTimeout(function() { row.remove(); }, 300);
            }
        } else {
            showMessage(data.message, true);
        }
    })
    .catch(function(err) {
        console.error(err);
        showMessage('操作失败：' + err.message, true);
    });
}
</script>

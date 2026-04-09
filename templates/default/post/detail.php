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
<div class="page-detail">
    <div class="post-item" data-id="<?php echo $post['id']; ?>">
        <div class="post-avatar">
            <a href="<?php echo $this->url('user/profile?id=' . $post['user_id']); ?>">
                <?php echo $this->avatar($post['avatar'] ?? null, $post['username']); ?>
            </a>
        </div>
        <div class="post-content">
            <div class="post-header">
                <a href="<?php echo $this->url('user/profile?id=' . $post['user_id']); ?>" class="username">
                    <?php echo $this->escape($post['username']); ?>
                </a>
                <?php if (!empty($post['repost_id'])): ?>
                <span class="repost-label">转发</span>
                <?php endif; ?>
                <span class="time"><?php echo $post['time_ago']; ?></span>
            </div>
            
            <?php if (!empty($post['content'])): ?>
            <div class="post-text"><?php echo $post['formatted_content']; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($post['original_post'])): ?>
            <div class="repost-box">
                <?php if (!empty($post['original_post']['deleted'])): ?>
                <div class="repost-deleted">原文已删除</div>
                <?php else: ?>
                <div class="repost-header">
                    <a href="<?php echo $this->url('user/profile?id=' . $post['original_post']['user_id']); ?>" class="username">@<?php echo $this->escape($post['original_post']['username']); ?></a>
                </div>
                <div class="repost-content"><?php echo $post['original_post']['content']; ?></div>
                <?php if (!empty($post['original_post']['images'])): ?>
                <?php echo Helper::renderImageGrid($post['original_post']['images']); ?>
                <?php endif; ?>
                <?php if (!empty($post['original_post']['attachments'])): ?>
                <div class="post-attachments">
                    <div class="post-attachments-title">📎 附件</div>
                    <?php foreach ($post['original_post']['attachments'] as $index => $attachment): ?>
                    <a href="<?php echo $this->url('download/attachment?id=' . $post['original_post']['id'] . '&index=' . $index); ?>" class="post-attachment-item">
                        <span class="post-attachment-icon">📄</span>
                        <span class="post-attachment-name"><?php echo $this->escape($attachment['name']); ?></span>
                        <span class="post-attachment-size"><?php echo $this->formatFileSize($attachment['size']); ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php elseif (empty($post['repost_id'])): ?>
            <?php if (!empty($post['images'])): ?>
            <?php echo Helper::renderImageGrid($post['images']); ?>
            <?php endif; ?>
            <?php if (!empty($post['videos'])): ?>
            <div class="post-videos">
                <?php foreach ($post['videos'] as $video): ?>
                <div class="post-video-item" onclick="playVideo(this)">
                    <video preload="metadata" playsinline>
                        <source src="<?php echo $this->uploadUrl($video['path']); ?>#t=3" type="video/<?php echo $video['ext']; ?>">
                        您的浏览器不支持视频播放
                    </video>
                    <div class="video-overlay">
                        <div class="video-play-btn">▶</div>
                    </div>
                    <div class="video-name-tag"><?php echo $this->escape($video['name']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($post['attachments'])): ?>
            <div class="post-attachments">
                <div class="post-attachments-title">📎 附件</div>
                <?php foreach ($post['attachments'] as $index => $attachment): ?>
                <a href="<?php echo $this->url('download/attachment?id=' . $post['id'] . '&index=' . $index); ?>" class="post-attachment-item">
                    <span class="post-attachment-icon">📄</span>
                    <span class="post-attachment-name"><?php echo $this->escape($attachment['name']); ?></span>
                    <span class="post-attachment-size"><?php echo $this->formatFileSize($attachment['size']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php echo $this->partial('partials/post-actions', ['post' => $post]); ?>
            <?php if (!empty($post['edit_count']) && $post['edit_count'] > 0): ?>
            <div class="post-edit-info">
                已编辑<?php echo $post['edit_count']; ?>次，最近编辑：<?php echo date('Y-m-d H:i', $post['edited_at']); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php echo CommentHelper::renderCommentSection($post['id'], $post['comments'], $comments); ?>
</div>

<div class="repost-modal" id="repostModal" style="display:none;">
    <div class="repost-modal-content">
        <div class="repost-modal-header">
            <h3>转发微博</h3>
            <span class="repost-modal-close" onclick="closeRepostModal()">×</span>
        </div>
        <form id="repostForm">
            <?php echo $this->csrf(); ?>
            <input type="hidden" name="post_id" id="repostPostId">
            <textarea name="content" placeholder="说点什么吧..." rows="3" data-max-length="<?php echo Setting::getMaxPostLength(); ?>"></textarea>
            <div class="repost-modal-footer">
                <label class="repost-checkbox">
                    <input type="checkbox" name="also_comment" value="1"> 同时评论
                </label>
                <div class="char-counter">
                    <span class="char-count">0</span>/<span><?php echo Setting::getMaxPostLength(); ?></span>
                </div>
                <button type="submit" class="btn btn-primary">转发</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content" style="width:600px;">
        <div class="modal-header">
            <h3>编辑微博</h3>
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
        </div>
        <form id="editForm">
            <input type="hidden" name="id" id="editPostId">
            <div class="form-group">
                <textarea name="content" id="editContent" rows="5" style="width:100%;resize:vertical;" placeholder="请输入内容..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeEditModal()">取消</button>
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
.modal-content .form-group textarea {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
    margin-top: 10px;
}
.post-edit-info {
    font-size: 12px;
    color: #888;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px dashed #eee;
}
</style>

<script>
function toggleActionMenu(el) {
    var dropdown = el.parentElement.querySelector('.dropdown-menu');
    var allDropdowns = document.querySelectorAll('.dropdown-menu.show');
    
    allDropdowns.forEach(function(menu) {
        if (menu !== dropdown) {
            menu.classList.remove('show');
        }
    });
    
    dropdown.classList.toggle('show');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.action-dropdown')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
            menu.classList.remove('show');
        });
    }
});

function openEditModal(postId) {
    var csrfInput = document.querySelector('#repostForm input[name="csrf_token"]');
    var csrfToken = csrfInput ? csrfInput.value : '';
    
    fetch('<?php echo $this->url("post/getEditData"); ?>&id=' + postId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            document.getElementById('editPostId').value = data.data.id;
            document.getElementById('editContent').value = data.data.content;
            document.getElementById('editModal').style.display = 'flex';
        } else {
            alert(data.message || '获取数据失败');
        }
    })
    .catch(function() {
        alert('获取数据失败');
    });
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    var csrfInput = document.querySelector('#repostForm input[name="csrf_token"]');
    var csrfToken = csrfInput ? csrfInput.value : '';
    formData.append('csrf_token', csrfToken);
    
    fetch('<?php echo $this->url("post/edit"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            var contentEl = document.querySelector('.post-text');
            if (contentEl) {
                contentEl.innerHTML = data.data.content.replace(/\n/g, '<br>');
            }
            closeEditModal();
            location.reload();
        } else {
            alert(data.message || '编辑失败');
        }
    })
    .catch(function() {
        alert('编辑失败');
    });
});

document.querySelectorAll('.action-dropdown .edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var postId = this.dataset.id;
        openEditModal(postId);
    });
});

document.querySelectorAll('.action-dropdown .delete-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        if (!confirm('确定要删除这条微博吗？')) return;
        
        var postId = this.dataset.id;
        var csrfInput = document.querySelector('#repostForm input[name="csrf_token"]');
        var csrfToken = csrfInput ? csrfInput.value : '';
        
        fetch('<?php echo $this->url("post/delete"); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + postId + '&csrf_token=' + encodeURIComponent(csrfToken)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code === 0) {
                alert('删除成功');
                window.location.href = '<?php echo $this->url(""); ?>';
            } else {
                alert(data.message || '删除失败');
            }
        })
        .catch(function() {
            alert('删除失败');
        });
    });
});

document.querySelectorAll('.action-dropdown .pin-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        
        var postId = this.dataset.id;
        var btnEl = this;
        var csrfInput = document.querySelector('#repostForm input[name="csrf_token"]');
        var csrfToken = csrfInput ? csrfInput.value : '';
        
        var formData = new FormData();
        formData.append('id', postId);
        formData.append('csrf_token', csrfToken);
        
        fetch('<?php echo $this->url("admin/togglePin"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.code === 0) {
                var newStatus = data.data.is_pinned;
                btnEl.dataset.pinned = newStatus;
                btnEl.textContent = newStatus ? '📌 取消置顶' : '📌 置顶';
                alert(newStatus ? '置顶成功' : '取消置顶成功');
            } else {
                alert(data.message);
            }
        })
        .catch(function() {
            alert('操作失败');
        });
    });
});

document.querySelectorAll('.action-dropdown .feature-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        
        var postId = this.dataset.id;
        var btnEl = this;
        var csrfInput = document.querySelector('#repostForm input[name="csrf_token"]');
        var csrfToken = csrfInput ? csrfInput.value : '';
        
        var formData = new FormData();
        formData.append('id', postId);
        formData.append('csrf_token', csrfToken);
        
        fetch('<?php echo $this->url("admin/toggleFeature"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.code === 0) {
                var newStatus = data.data.is_featured;
                btnEl.dataset.featured = newStatus;
                btnEl.textContent = newStatus ? '⭐ 取消加精' : '⭐ 加精';
                alert(newStatus ? '加精成功' : '取消加精成功');
            } else {
                alert(data.message);
            }
        })
        .catch(function() {
            alert('操作失败');
        });
    });
});
</script>

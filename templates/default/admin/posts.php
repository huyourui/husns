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
        <h2>动态管理</h2>
        <form method="get" class="search-form">
            <input type="hidden" name="r" value="admin/posts">
            <input type="text" name="keyword" value="<?php echo $this->escape($keyword); ?>" placeholder="搜索内容或用户名">
            <button type="submit" class="btn">搜索</button>
        </form>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>发布者</th>
                <th>内容</th>
                <th>IP地址</th>
                <th>归属地</th>
                <th>点赞/评论</th>
                <th>发布时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $post): ?>
            <tr data-id="<?php echo $post['id']; ?>">
                <td><?php echo $post['id']; ?></td>
                <td><?php echo $this->escape($post['username']); ?></td>
                <td class="content-cell">
                    <span class="pinned-badge" style="<?php echo empty($post['is_pinned']) ? 'display:none;' : ''; ?>">置顶</span>
                    <span class="featured-badge" style="<?php echo empty($post['is_featured']) ? 'display:none;' : ''; ?>">精华</span>
                    <?php echo $this->escape(Helper::truncate($post['content'], 50)); ?>
                </td>
                <td><?php echo $this->escape($post['ip']); ?></td>
                <td class="location-cell"><?php echo $this->escape($post['location']); ?></td>
                <td><?php echo $post['likes']; ?>/<?php echo $post['comments']; ?></td>
                <td><?php echo $post['time_ago']; ?></td>
                <td>
                    <a href="<?php echo $this->url('post/detail?id=' . $post['id']); ?>" class="btn btn-sm">查看</a>
                    <button class="btn btn-sm pin-btn <?php echo !empty($post['is_pinned']) ? 'btn-warning' : 'btn-primary'; ?>" data-id="<?php echo $post['id']; ?>" data-pinned="<?php echo !empty($post['is_pinned']) ? 1 : 0; ?>">
                        <?php echo !empty($post['is_pinned']) ? '取消置顶' : '置顶'; ?>
                    </button>
                    <button class="btn btn-sm feature-btn <?php echo !empty($post['is_featured']) ? 'btn-warning' : 'btn-success'; ?>" data-id="<?php echo $post['id']; ?>" data-featured="<?php echo !empty($post['is_featured']) ? 1 : 0; ?>">
                        <?php echo !empty($post['is_featured']) ? '取消加精' : '加精'; ?>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deletePost(<?php echo $post['id']; ?>)">删除</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?r=admin/posts&page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<form id="deleteForm" method="post" action="<?php echo $this->url('admin/postDelete'); ?>">
    <?php echo $this->csrf(); ?>
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function deletePost(id) {
    if (confirm('确定要删除这条动态吗？')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

document.querySelectorAll('.pin-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.id;
        var btnEl = this;
        var row = document.querySelector('tr[data-id="' + id + '"]');
        var pinnedBadge = row.querySelector('.pinned-badge');
        var csrfToken = document.querySelector('input[name="csrf_token"]').value;
        
        var formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', csrfToken);
        
        fetch('<?php echo $this->url("admin/togglePin"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(function(data) {
            if (data.code === 0) {
                var newStatus = data.data.is_pinned;
                btnEl.dataset.pinned = newStatus;
                btnEl.textContent = newStatus ? '取消置顶' : '置顶';
                btnEl.className = 'btn btn-sm pin-btn ' + (newStatus ? 'btn-warning' : 'btn-primary');
                pinnedBadge.style.display = newStatus ? 'inline-block' : 'none';
            } else {
                alert(data.message);
            }
        })
        .catch(function(error) {
            alert('操作失败');
        });
    });
});

document.querySelectorAll('.feature-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.id;
        var btnEl = this;
        var row = document.querySelector('tr[data-id="' + id + '"]');
        var featuredBadge = row.querySelector('.featured-badge');
        var csrfToken = document.querySelector('input[name="csrf_token"]').value;
        
        var formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', csrfToken);
        
        fetch('<?php echo $this->url("admin/toggleFeature"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(function(data) {
            if (data.code === 0) {
                var newStatus = data.data.is_featured;
                btnEl.dataset.featured = newStatus;
                btnEl.textContent = newStatus ? '取消加精' : '加精';
                btnEl.className = 'btn btn-sm feature-btn ' + (newStatus ? 'btn-warning' : 'btn-success');
                featuredBadge.style.display = newStatus ? 'inline-block' : 'none';
            } else {
                alert(data.message);
            }
        })
        .catch(function(error) {
            alert('操作失败');
        });
    });
});
</script>

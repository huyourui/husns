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
        <h2>评论管理</h2>
        <form method="get" class="search-form">
            <input type="hidden" name="r" value="admin/comments">
            <input type="text" name="keyword" value="<?php echo $this->escape($keyword); ?>" placeholder="搜索评论内容或用户名">
            <button type="submit" class="btn">搜索</button>
        </form>
    </div>

 <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>评论者</th>
                <th>评论内容</th>
                <th>所属微博</th>
                <th>IP地址</th>
                <th>归属地</th>
                <th>评论时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($comments as $comment): ?>
            <tr data-id="<?php echo $comment['id']; ?>">
                <td><?php echo $comment['id']; ?></td>
                <td><?php echo $this->escape($comment['username']); ?></td>
                <td class="content-cell"><?php echo $this->escape(Helper::truncate($comment['content'], 50)); ?></td>
                <td>
                    <a href="<?php echo $this->url('post/detail?id=' . $comment['post_id']); ?>" class="btn btn-sm" target="_blank">查看微博</a>
                </td>
                <td><?php echo $this->escape($comment['ip'] ?? ''); ?></td>
                <td class="location-cell"><?php echo $this->escape($comment['location']); ?></td>
                <td><?php echo $comment['time_ago']; ?></td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="deleteComment(<?php echo $comment['id']; ?>)">删除</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?r=admin/comments&page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword); ?>" class="<?php echo $i == $page ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<form id="deleteForm" method="post" action="<?php echo $this->url('admin/commentDelete'); ?>">
    <?php echo $this->csrf(); ?>
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function deleteComment(id) {
    if (confirm('确定要删除这条评论吗？')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

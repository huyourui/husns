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
<div class="page-notification">
    <div class="notification-header">
        <h2>消息通知</h2>
        <?php if ($unreadCount > 0): ?>
        <a href="javascript:void(0)" class="mark-all-read" onclick="markAllRead()">全部标记已读</a>
        <?php endif; ?>
    </div>
    
    <div class="notification-list">
        <?php if (empty($notifications)): ?>
        <div class="empty">暂无消息通知</div>
        <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
        <?php
        $typeIcon = '🔔';
        $typeClass = '';
        switch ($notification['type']) {
            case 'comment':
                $typeIcon = '💬';
                $typeClass = 'type-comment';
                break;
            case 'like':
                $typeIcon = '❤️';
                $typeClass = 'type-like';
                break;
            case 'follow':
                $typeIcon = '👤';
                $typeClass = 'type-follow';
                break;
            case 'mention':
                $typeIcon = '@';
                $typeClass = 'type-mention';
                break;
            case 'system':
                $typeIcon = '🔔';
                $typeClass = 'type-system';
                break;
        }
        ?>
        <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?> <?php echo $typeClass; ?>" data-id="<?php echo $notification['id']; ?>">
            <div class="notification-avatar">
                <?php if ($notification['sender_id']): ?>
                <a href="<?php echo $this->url('user/profile?id=' . $notification['sender_id']); ?>">
                    <?php echo $this->avatar($notification['sender_avatar'] ?? null, $notification['sender_name'] ?? '', 'small'); ?>
                </a>
                <?php else: ?>
                <span class="system-icon"><?php echo $typeIcon; ?></span>
                <?php endif; ?>
            </div>
            <div class="notification-content">
                <div class="notification-title">
                    <?php if (!$notification['is_read']): ?>
                    <span class="unread-dot"></span>
                    <?php endif; ?>
                    <?php if ($notification['type'] == 'mention'): ?>
                    <span class="mention-badge">@</span>
                    <?php endif; ?>
                    <?php echo $this->escape($notification['title']); ?>
                </div>
                <?php if ($notification['content']): ?>
                <div class="notification-text"><?php echo $this->escape($notification['content']); ?></div>
                <?php endif; ?>
                <div class="notification-meta">
                    <span class="time"><?php echo $notification['time_ago']; ?></span>
                    <?php
                    $detailUrl = '';
                    $data = is_array($notification['data']) ? $notification['data'] : json_decode($notification['data'] ?? '{}', true);
                    
                    if ($notification['target_type'] == 'post' && $notification['target_id']) {
                        $detailUrl = $this->url('post/detail?id=' . $notification['target_id']);
                        if (isset($data['comment_id'])) {
                            $detailUrl .= '#comment-' . $data['comment_id'];
                        }
                    } elseif ($notification['target_type'] == 'user' && $notification['target_id']) {
                        $detailUrl = $this->url('user/profile?id=' . $notification['target_id']);
                    } elseif ($notification['target_type'] == 'comment' && $notification['target_id']) {
                        if (isset($data['post_id'])) {
                            $detailUrl = $this->url('post/detail?id=' . $data['post_id'] . '#comment-' . $notification['target_id']);
                        }
                    }
                    
                    if ($detailUrl):
                    ?>
                    <a href="<?php echo $detailUrl; ?>" target="_blank" onclick="markAsReadAndOpen(<?php echo $notification['id']; ?>, this)">查看详情</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="notification-actions">
                <button class="btn-delete" onclick="deleteNotification(<?php echo $notification['id']; ?>)">删除</button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if (count($notifications) == $pageSize): ?>
    <div class="load-more">
        <a href="<?php echo $this->url('notification?page=' . ($page + 1)); ?>" class="btn">加载更多</a>
    </div>
    <?php endif; ?>
</div>

<script>
function markAsReadAndOpen(id, link) {
    const formData = new FormData();
    formData.append('id', id);
    
    fetch(BASE_URL + '/?r=notification/markRead', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.code === 0) {
            const item = document.querySelector('.notification-item[data-id="' + id + '"]');
            if (item) {
                item.classList.remove('unread');
                item.classList.add('read');
                item.querySelector('.unread-dot')?.remove();
            }
            updateNotificationCount();
        }
    });
}

function markAllRead() {
    fetch(BASE_URL + '/?r=notification/markAllRead', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.code === 0) {
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
                item.classList.add('read');
                item.querySelector('.unread-dot')?.remove();
            });
            document.querySelector('.mark-all-read')?.remove();
            updateNotificationCount();
        }
    });
}

function deleteNotification(id) {
    if (!confirm('确定要删除这条通知吗？')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch(BASE_URL + '/?r=notification/delete', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.code === 0) {
            document.querySelector('.notification-item[data-id="' + id + '"]')?.remove();
            updateNotificationCount();
        }
    });
}

function updateNotificationCount() {
    fetch(BASE_URL + '/?r=notification/getUnreadCount')
        .then(response => response.json())
        .then(data => {
            if (data.code === 0) {
                const badge = document.getElementById('notificationBadge');
                if (data.data.count > 0) {
                    badge.textContent = data.data.count > 99 ? '99+' : data.data.count;
                    badge.style.display = 'inline';
                } else {
                    badge.style.display = 'none';
                }
            }
        });
}
</script>

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
<div class="m-notification-header">
    <div class="m-notification-title-bar">
        <span class="m-notification-title-text">消息通知</span>
        <?php if ($unreadCount > 0): ?>
        <button class="m-mark-all-read" onclick="markAllRead()">全部已读</button>
        <?php endif; ?>
    </div>
</div>

<div class="m-notification-list">
    <?php if (empty($notifications)): ?>
    <div class="m-empty">
        <div class="m-empty-icon">🔔</div>
        <div class="m-empty-text">暂无消息</div>
    </div>
    <?php else: ?>
    <?php foreach ($notifications as $notification): ?>
    <?php
    $typeIcon = '🔔';
    switch ($notification['type']) {
        case 'comment': $typeIcon = '💬'; break;
        case 'like': $typeIcon = '❤️'; break;
        case 'follow': $typeIcon = '👤'; break;
        case 'mention': $typeIcon = '@'; break;
        case 'favorite': $typeIcon = '⭐'; break;
    }
    
    $detailUrl = '';
    $data = is_array($notification['data']) ? $notification['data'] : json_decode($notification['data'] ?? '{}', true);
    
    if ($notification['target_type'] == 'post' && $notification['target_id']) {
        $detailUrl = $this->url('mobile/detail?id=' . $notification['target_id']);
        if (isset($data['comment_id'])) {
            $detailUrl .= '#comment-' . $data['comment_id'];
        }
    } elseif ($notification['target_type'] == 'user' && $notification['target_id']) {
        $detailUrl = $this->url('mobile/user?id=' . $notification['target_id']);
    }
    ?>
    <a href="<?php echo $detailUrl; ?>" class="m-notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notification['id']; ?>" onclick="markAsRead(this, event)">
        <?php if ($notification['sender_id']): ?>
        <?php echo $this->avatar($notification['sender_avatar'] ?? null, $notification['sender_name'] ?? '', 'small'); ?>
        <?php else: ?>
        <div class="m-notification-avatar" style="display:flex;align-items:center;justify-content:center;background:var(--bg-secondary);font-size:20px;"><?php echo $typeIcon; ?></div>
        <?php endif; ?>
        <div class="m-notification-content">
            <div class="m-notification-title">
                <?php if ($notification['type'] == 'mention'): ?>
                <span style="color:var(--primary-color);font-weight:600;">@</span>
                <?php endif; ?>
                <?php echo $this->escape($notification['title']); ?>
            </div>
            <?php if ($notification['content']): ?>
            <div style="font-size:13px;color:var(--text-secondary);margin-top:3px;"><?php echo $this->escape(mb_substr($notification['content'], 0, 50, 'UTF-8')); ?></div>
            <?php endif; ?>
            <div class="m-notification-time"><?php echo $notification['time_ago']; ?></div>
        </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (count($notifications) >= 20): ?>
<div class="m-load-more">
    <a href="<?php echo $this->url('mobile/notification?page=' . ($page + 1)); ?>" style="color:var(--primary-color);text-decoration:none;">加载更多</a>
</div>
<?php endif; ?>

<script>
function markAsRead(element, event) {
    var id = element.dataset.id;
    if (!id) return;
    
    if (element.classList.contains('unread')) {
        element.classList.remove('unread');
        
        fetch(BASE_URL + '/?r=mobile/markRead', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&csrf_token=' + CSRF_TOKEN
        }).then(function(res) { return res.json(); })
          .then(function(data) {
              if (data.code === 0 && data.data.unread_count !== undefined) {
                  updateUnreadBadge(data.data.unread_count);
              }
          }).catch(function() {});
    }
}

function markAllRead() {
    fetch(BASE_URL + '/?r=mobile/markAllRead', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + CSRF_TOKEN
    }).then(function(res) { return res.json(); })
      .then(function(data) {
          if (data.code === 0) {
              document.querySelectorAll('.m-notification-item.unread').forEach(function(item) {
                  item.classList.remove('unread');
              });
              updateUnreadBadge(0);
              var btn = document.querySelector('.m-mark-all-read');
              if (btn) btn.style.display = 'none';
          } else {
              alert(data.msg || '操作失败');
          }
      }).catch(function() {});
}

function updateUnreadBadge(count) {
    var badge = document.querySelector('.m-tabbar-badge');
    if (!badge) return;
    
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}
</script>

<style>
.m-notification-header {
    padding: 12px 15px;
    background: var(--bg-color);
    border-bottom: 1px solid var(--border-color);
}

.m-notification-title-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.m-notification-title-text {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary);
}

.m-mark-all-read {
    background: none;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
    font-size: 13px;
    padding: 5px 12px;
    border-radius: 15px;
    cursor: pointer;
}

.m-mark-all-read:active {
    opacity: 0.7;
}
</style>

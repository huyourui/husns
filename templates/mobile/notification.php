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
<div class="m-notification-container">
    <!-- 头部 -->
    <div class="m-notification-header">
        <h1 class="m-notification-title">消息</h1>
        <?php if ($unreadCount > 0): ?>
        <button class="m-mark-all-read" onclick="markAllRead()">全部已读</button>
        <?php endif; ?>
    </div>

    <!-- 消息列表 -->
    <div class="m-notification-list">
        <?php if (empty($notifications)): ?>
        <div class="m-empty-state">
            <div class="m-empty-icon">📭</div>
            <div class="m-empty-text">暂无消息通知</div>
        </div>
        <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
        <?php
        $typeIcon = '🔔';
        $typeColor = '#999';
        switch ($notification['type']) {
            case 'comment': 
                $typeIcon = '💬'; 
                $typeColor = '#10b981';
                break;
            case 'like': 
                $typeIcon = '❤️'; 
                $typeColor = '#ef4444';
                break;
            case 'follow': 
                $typeIcon = '👤'; 
                $typeColor = '#8b5cf6';
                break;
            case 'mention': 
                $typeIcon = '@'; 
                $typeColor = '#1da1f2';
                break;
            case 'favorite': 
                $typeIcon = '⭐'; 
                $typeColor = '#f59e0b';
                break;
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
        <a href="<?php echo $detailUrl; ?>" class="m-notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notification['id']; ?>" onclick="markAsRead(this, event)">
            <!-- 左侧：头像或图标 -->
            <div class="m-notification-left">
                <?php if ($notification['sender_id']): ?>
                <div class="m-notification-avatar-wrap">
                    <?php echo $this->avatar($notification['sender_avatar'] ?? null, $notification['sender_name'] ?? '', 44); ?>
                    <span class="m-notification-type-icon" style="background:<?php echo $typeColor; ?>"><?php echo $typeIcon; ?></span>
                </div>
                <?php else: ?>
                <div class="m-notification-system-icon" style="background:<?php echo $typeColor; ?>">
                    <?php echo $typeIcon; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 右侧：内容 -->
            <div class="m-notification-body">
                <div class="m-notification-main">
                    <?php if ($notification['type'] == 'mention' && $notification['sender_id']): ?>
                    <span class="m-notification-sender"><?php echo $this->escape($notification['sender_name'] ?? ''); ?></span>
                    <span class="m-notification-action">在<?php echo $data['comment_id'] ? '评论中' : '动态中'; ?>提到了你</span>
                    <?php else: ?>
                    <span class="m-notification-text"><?php echo $this->escape($notification['title']); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($notification['content']): ?>
                <div class="m-notification-preview"><?php echo $this->escape(mb_substr($notification['content'], 0, 60, 'UTF-8')); ?></div>
                <?php endif; ?>
                
                <div class="m-notification-meta">
                    <span class="m-notification-time"><?php echo $notification['time_ago']; ?></span>
                    <?php if (!$notification['is_read']): ?>
                    <span class="m-notification-dot"></span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (count($notifications) >= 20): ?>
    <div class="m-load-more">
        <a href="<?php echo $this->url('mobile/notification?page=' . ($page + 1)); ?>" class="m-load-more-link">加载更多</a>
    </div>
    <?php endif; ?>
</div>

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
              document.querySelectorAll('.m-notification-card.unread').forEach(function(item) {
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
/* 消息页面容器 */
.m-notification-container {
    min-height: 100vh;
    background: #f5f5f5;
}

/* 头部 */
.m-notification-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #fff;
    border-bottom: 1px solid #eee;
    position: sticky;
    top: 0;
    z-index: 10;
}

.m-notification-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.m-mark-all-read {
    background: transparent;
    border: 1px solid var(--primary-color, #1da1f2);
    color: var(--primary-color, #1da1f2);
    font-size: 13px;
    padding: 6px 14px;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.2s;
}

.m-mark-all-read:active {
    background: var(--primary-color, #1da1f2);
    color: #fff;
}

/* 消息列表 */
.m-notification-list {
    padding: 8px 12px;
}

/* 消息卡片 */
.m-notification-card {
    display: flex;
    align-items: flex-start;
    padding: 14px 12px;
    margin-bottom: 8px;
    background: #fff;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.m-notification-card.unread {
    background: #fff;
    border-left: 3px solid var(--primary-color, #1da1f2);
}

.m-notification-card:active {
    background: #f8f9fa;
}

/* 左侧头像区域 */
.m-notification-left {
    flex-shrink: 0;
    margin-right: 12px;
}

.m-notification-avatar-wrap {
    position: relative;
    width: 44px;
    height: 44px;
}

.m-notification-avatar-wrap .avatar-default,
.m-notification-avatar-wrap img {
    width: 44px !important;
    height: 44px !important;
    border-radius: 50%;
    object-fit: cover;
}

.m-notification-type-icon {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: #fff;
    border: 2px solid #fff;
}

.m-notification-system-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

/* 右侧内容区域 */
.m-notification-body {
    flex: 1;
    min-width: 0;
}

.m-notification-main {
    font-size: 14px;
    line-height: 1.5;
    color: #333;
    margin-bottom: 4px;
}

.m-notification-sender {
    font-weight: 600;
    color: #333;
}

.m-notification-action {
    color: #666;
}

.m-notification-text {
    color: #333;
}

.m-notification-preview {
    font-size: 13px;
    color: #888;
    line-height: 1.4;
    margin-top: 6px;
    padding: 8px 10px;
    background: #f5f5f5;
    border-radius: 8px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.m-notification-meta {
    display: flex;
    align-items: center;
    margin-top: 8px;
    gap: 8px;
}

.m-notification-time {
    font-size: 12px;
    color: #999;
}

.m-notification-dot {
    width: 6px;
    height: 6px;
    background: var(--primary-color, #1da1f2);
    border-radius: 50%;
}

/* 空状态 */
.m-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
}

.m-empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.m-empty-text {
    font-size: 15px;
    color: #999;
}

/* 加载更多 */
.m-load-more {
    text-align: center;
    padding: 20px;
}

.m-load-more-link {
    color: var(--primary-color, #1da1f2);
    text-decoration: none;
    font-size: 14px;
}

/* 深色模式适配 */
.dark-mode .m-notification-container {
    background: #1a1a1a;
}

.dark-mode .m-notification-header {
    background: #242424;
    border-bottom-color: #333;
}

.dark-mode .m-notification-title {
    color: #fff;
}

.dark-mode .m-notification-card {
    background: #242424;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.dark-mode .m-notification-card.unread {
    background: #242424;
}

.dark-mode .m-notification-card:active {
    background: #2a2a2a;
}

.dark-mode .m-notification-main,
.dark-mode .m-notification-sender {
    color: #fff;
}

.dark-mode .m-notification-action,
.dark-mode .m-notification-text {
    color: #ccc;
}

.dark-mode .m-notification-preview {
    background: #1a1a1a;
    color: #999;
}

.dark-mode .m-empty-text {
    color: #666;
}
</style>

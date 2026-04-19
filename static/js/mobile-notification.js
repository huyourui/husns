/**
 * HuSNS Mobile Notification Page
 * 移动端消息页面专用脚本
 * @version 4.0.0
 */

(function() {
    'use strict';

    // 消息页面应用对象
    var NotificationApp = {
        // 配置
        config: {
            baseUrl: '',
            pageSize: 20,
            currentPage: 1,
            loading: false,
            hasMore: true
        },

        // 初始化
        init: function(baseUrl) {
            console.log('[NotificationApp] Initializing...');
            this.config.baseUrl = baseUrl || '';
            
            // 绑定事件
            this.bindEvents();
            
            // 立即加载数据
            this.loadNotifications();
            
            console.log('[NotificationApp] Initialized');
        },

        // 绑定事件
        bindEvents: function() {
            var self = this;
            
            // 全部已读按钮
            var markAllReadBtn = document.getElementById('markAllRead');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    self.markAllRead();
                });
            }

            // 加载更多按钮
            var loadMoreBtn = document.getElementById('loadMoreBtn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    self.loadMore();
                });
            }

            // 滚动加载
            var notificationList = document.getElementById('notificationList');
            if (notificationList) {
                notificationList.addEventListener('scroll', function() {
                    self.handleScroll(this);
                });
            }

            // 消息项点击事件（事件委托）
            var list = document.getElementById('notificationList');
            if (list) {
                list.addEventListener('click', function(e) {
                    var item = e.target.closest('.notification-item');
                    if (item) {
                        self.handleItemClick(item);
                    }
                });
            }
        },

        // 加载消息列表
        loadNotifications: function(isLoadMore) {
            var self = this;
            
            if (this.config.loading) {
                console.log('[NotificationApp] Already loading, skip');
                return;
            }

            this.config.loading = true;
            console.log('[NotificationApp] Loading notifications, page:', this.config.currentPage);

            // 显示加载状态
            if (!isLoadMore) {
                this.showLoading();
            }

            // 构建API URL
            var url = this.config.baseUrl + '/?r=mobileApi/notifications' + 
                      '&page=' + this.config.currentPage;

            console.log('[NotificationApp] API URL:', url);

            // 发送请求
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                console.log('[NotificationApp] Response status:', response.status);
                return response.json();
            })
            .then(function(data) {
                console.log('[NotificationApp] Response data:', data);
                self.config.loading = false;
                self.hideLoading();

                if (data.code === 0) {
                    self.renderNotifications(data.data.items || [], isLoadMore);
                    self.config.hasMore = data.data.pagination ? data.data.pagination.has_more : false;
                    self.updateLoadMoreButton();
                    self.updateUnreadBadge(data.data.unread_count || 0);
                } else if (data.code === 401) {
                    // 未登录，跳转到登录页
                    window.location.href = self.config.baseUrl + '/?r=mobile/login';
                } else {
                    self.showError(data.message || '加载失败');
                }
            })
            .catch(function(error) {
                console.error('[NotificationApp] Load error:', error);
                self.config.loading = false;
                self.hideLoading();
                self.showError('网络错误，请稍后重试');
            });
        },

        // 加载更多
        loadMore: function() {
            if (!this.config.hasMore || this.config.loading) {
                return;
            }
            this.config.currentPage++;
            this.loadNotifications(true);
        },

        // 渲染消息列表
        renderNotifications: function(notifications, isAppend) {
            var container = document.getElementById('notificationList');
            if (!container) {
                console.error('[NotificationApp] Container not found');
                return;
            }

            if (!isAppend) {
                container.innerHTML = '';
            }

            if (notifications.length === 0 && !isAppend) {
                container.innerHTML = '<div class="empty-state">暂无消息</div>';
                return;
            }

            var html = '';
            var self = this;
            notifications.forEach(function(notification) {
                html += self.renderNotificationItem(notification);
            });

            if (isAppend) {
                container.insertAdjacentHTML('beforeend', html);
            } else {
                container.innerHTML = html;
            }
        },

        // 渲染单条消息
        renderNotificationItem: function(notification) {
            var avatar = notification.sender_avatar || '<div class="avatar-placeholder">?</div>';
            var title = notification.title || '系统消息';
            var content = notification.content || '';
            var time = notification.time_ago || '';
            var isRead = notification.is_read ? true : false;
            
            var html = '<div class="notification-item ' + (isRead ? '' : 'unread') + '" ' +
                'data-id="' + notification.id + '" ' +
                'data-type="' + (notification.type || '') + '" ' +
                'data-target-type="' + (notification.target_type || '') + '" ' +
                'data-target-id="' + (notification.target_id || '') + '">' +
                '<div class="notification-avatar">' + avatar + '</div>' +
                '<div class="notification-body">' +
                '<div class="notification-title">' + this.escapeHtml(title) + '</div>' +
                '<div class="notification-content">' + this.escapeHtml(content) + '</div>' +
                '<div class="notification-time">' + time + '</div>' +
                '</div>' +
                (isRead ? '' : '<div class="notification-dot"></div>') +
                '</div>';

            return html;
        },

        // 处理消息项点击
        handleItemClick: function(item) {
            var id = item.dataset.id;
            var targetType = item.dataset.targetType;
            var targetId = item.dataset.targetId;
            
            console.log('[NotificationApp] Item clicked:', id, targetType, targetId);
            
            // 标记为已读
            this.markAsRead(id, item);
            
            // 跳转到相关页面
            if (targetType && targetId) {
                var url = '';
                switch (targetType) {
                    case 'post':
                        url = this.config.baseUrl + '/?r=mobile/detail&id=' + targetId;
                        break;
                    case 'user':
                        url = this.config.baseUrl + '/?r=mobile/user&id=' + targetId;
                        break;
                    case 'comment':
                        // 评论需要找到对应的微博
                        url = this.config.baseUrl + '/?r=mobile/detail&id=' + targetId;
                        break;
                    default:
                        url = this.config.baseUrl + '/?r=mobile';
                }
                if (url) {
                    window.location.href = url;
                }
            }
        },

        // 标记单条消息为已读
        markAsRead: function(id, itemEl) {
            var self = this;
            
            var url = this.config.baseUrl + '/?r=mobileApi/markRead';
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'id=' + encodeURIComponent(id)
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.code === 0) {
                    // 更新UI
                    if (itemEl) {
                        itemEl.classList.remove('unread');
                        var dot = itemEl.querySelector('.notification-dot');
                        if (dot) {
                            dot.remove();
                        }
                    }
                    self.updateUnreadBadge(data.data.unread_count || 0);
                }
            })
            .catch(function(error) {
                console.error('[NotificationApp] Mark as read error:', error);
            });
        },

        // 标记全部已读
        markAllRead: function() {
            var self = this;
            
            var url = this.config.baseUrl + '/?r=mobileApi/markAllRead';
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.code === 0) {
                    // 更新所有消息项为已读
                    document.querySelectorAll('.notification-item.unread').forEach(function(item) {
                        item.classList.remove('unread');
                        var dot = item.querySelector('.notification-dot');
                        if (dot) {
                            dot.remove();
                        }
                    });
                    self.updateUnreadBadge(0);
                    self.showToast('全部已读');
                } else {
                    self.showToast(data.message || '操作失败', 'error');
                }
            })
            .catch(function(error) {
                console.error('[NotificationApp] Mark all read error:', error);
                self.showToast('操作失败', 'error');
            });
        },

        // 更新未读消息数
        updateUnreadBadge: function(count) {
            // 更新底部导航栏的徽章
            var badges = document.querySelectorAll('.unread-badge');
            badges.forEach(function(badge) {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            });
        },

        // 显示加载状态
        showLoading: function() {
            var container = document.getElementById('notificationList');
            if (container && container.children.length === 0) {
                container.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div>加载中...</div>';
            }
        },

        // 隐藏加载状态
        hideLoading: function() {
            var loading = document.querySelector('.loading-state');
            if (loading) {
                loading.remove();
            }
        },

        // 显示错误
        showError: function(message) {
            var container = document.getElementById('notificationList');
            if (container) {
                container.innerHTML = '<div class="error-state">' + message + '<button onclick="location.reload()">重试</button></div>';
            }
        },

        // 更新加载更多按钮
        updateLoadMoreButton: function() {
            var btn = document.getElementById('loadMoreBtn');
            if (btn) {
                btn.style.display = this.config.hasMore ? 'block' : 'none';
            }
        },

        // 处理滚动
        handleScroll: function(container) {
            if (this.config.loading || !this.config.hasMore) return;
            
            var scrollTop = container.scrollTop;
            var scrollHeight = container.scrollHeight;
            var clientHeight = container.clientHeight;
            
            if (scrollTop + clientHeight >= scrollHeight - 100) {
                this.loadMore();
            }
        },

        // 显示提示
        showToast: function(message, type) {
            type = type || 'success';
            var toast = document.createElement('div');
            toast.className = 'm-toast m-toast-' + type;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(function() {
                toast.classList.add('fade-out');
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 2000);
        },

        // HTML转义
        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // 页面加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            var baseUrl = document.body.dataset.baseUrl || '';
            NotificationApp.init(baseUrl);
        });
    } else {
        // DOM已加载
        var baseUrl = document.body.dataset.baseUrl || '';
        NotificationApp.init(baseUrl);
    }

    // 暴露到全局
    window.NotificationApp = NotificationApp;

})();

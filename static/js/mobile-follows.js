/**
 * HuSNS Mobile Follows Page
 * 移动端关注页面专用脚本
 * @version 4.0.0
 */

(function() {
    'use strict';

    var FollowsApp = {
        config: {
            baseUrl: '',
            pageSize: 20,
            currentPage: 1,
            loading: false,
            hasMore: true
        },

        init: function(baseUrl) {
            console.log('[FollowsApp] Initializing...');
            this.config.baseUrl = baseUrl || '';
            this.bindEvents();
            this.loadFollows();
            console.log('[FollowsApp] Initialized');
        },

        bindEvents: function() {
            var self = this;
            
            var loadMoreBtn = document.getElementById('loadMoreBtn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    self.loadMore();
                });
            }

            var followsList = document.getElementById('followsList');
            if (followsList) {
                followsList.addEventListener('scroll', function() {
                    self.handleScroll(this);
                });
                
                followsList.addEventListener('click', function(e) {
                    var followBtn = e.target.closest('.follow-btn');
                    if (followBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        self.handleFollow(followBtn);
                    }
                });
            }
        },

        loadFollows: function(isLoadMore) {
            var self = this;
            
            if (this.config.loading) return;
            this.config.loading = true;
            
            if (!isLoadMore) this.showLoading();

            var url = this.config.baseUrl + '/?r=mobileApi/follows' + 
                      '&page=' + this.config.currentPage;

            fetch(url, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                self.config.loading = false;
                self.hideLoading();
                
                if (data.code === 0) {
                    self.renderUsers(data.data.items || [], isLoadMore);
                    self.config.hasMore = data.data.pagination ? data.data.pagination.has_more : false;
                    self.updateLoadMoreButton();
                } else if (data.code === 401) {
                    window.location.href = self.config.baseUrl + '/?r=mobile/login';
                } else {
                    self.showError(data.message || '加载失败');
                }
            })
            .catch(function(error) {
                console.error('[FollowsApp] Load error:', error);
                self.config.loading = false;
                self.hideLoading();
                self.showError('网络错误，请稍后重试');
            });
        },

        loadMore: function() {
            if (!this.config.hasMore || this.config.loading) return;
            this.config.currentPage++;
            this.loadFollows(true);
        },

        renderUsers: function(users, isAppend) {
            var container = document.getElementById('followsList');
            if (!container) return;

            if (!isAppend) container.innerHTML = '';
            
            if (users.length === 0 && !isAppend) {
                container.innerHTML = '<div class="empty-state">暂无关注</div>';
                return;
            }

            var html = '';
            var self = this;
            users.forEach(function(user) {
                html += self.renderUserItem(user);
            });

            if (isAppend) {
                container.insertAdjacentHTML('beforeend', html);
            } else {
                container.innerHTML = html;
            }
        },

        renderUserItem: function(user) {
            var html = '<div class="user-item" data-user-id="' + user.id + '">' +
                '<a href="' + this.config.baseUrl + '/?r=mobile/user&id=' + user.id + '" class="user-avatar">' +
                user.avatar +
                '</a>' +
                '<div class="user-info">' +
                '<a href="' + this.config.baseUrl + '/?r=mobile/user&id=' + user.id + '" class="user-name">' +
                this.escapeHtml(user.username) +
                '</a>' +
                '<div class="user-bio">' + this.escapeHtml(user.bio || '') + '</div>' +
                '</div>' +
                '<button class="follow-btn ' + (user.is_following ? 'following' : '') + '" data-user-id="' + user.id + '">' +
                (user.is_following ? '已关注' : '关注') +
                '</button>' +
                '</div>';

            return html;
        },

        handleFollow: function(btn) {
            var self = this;
            var userId = btn.dataset.userId;
            var isFollowing = btn.classList.contains('following');
            var action = isFollowing ? 'unfollow' : 'follow';
            
            var url = this.config.baseUrl + '/?r=mobileApi/' + action;
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'id=' + encodeURIComponent(userId)
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.code === 0) {
                    btn.classList.toggle('following');
                    btn.textContent = isFollowing ? '关注' : '已关注';
                } else if (data.code === 401) {
                    window.location.href = self.config.baseUrl + '/?r=mobile/login';
                }
            })
            .catch(function(error) {
                console.error('[FollowsApp] Follow error:', error);
            });
        },

        showLoading: function() {
            var container = document.getElementById('followsList');
            if (container && container.children.length === 0) {
                container.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div>加载中...</div>';
            }
        },

        hideLoading: function() {
            var loading = document.querySelector('.loading-state');
            if (loading) loading.remove();
        },

        showError: function(message) {
            var container = document.getElementById('followsList');
            if (container) container.innerHTML = '<div class="error-state">' + message + '</div>';
        },

        updateLoadMoreButton: function() {
            var btn = document.getElementById('loadMoreBtn');
            if (btn) btn.style.display = this.config.hasMore ? 'block' : 'none';
        },

        handleScroll: function(container) {
            if (this.config.loading || !this.config.hasMore) return;
            if (container.scrollTop + container.clientHeight >= container.scrollHeight - 100) {
                this.loadMore();
            }
        },

        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            FollowsApp.init(document.body.dataset.baseUrl || '');
        });
    } else {
        FollowsApp.init(document.body.dataset.baseUrl || '');
    }

    window.FollowsApp = FollowsApp;
})();

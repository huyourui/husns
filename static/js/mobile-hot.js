/**
 * HuSNS Mobile Hot Page
 * 移动端热门页面专用脚本
 * @version 4.0.0
 */

(function() {
    'use strict';

    // 热门页面应用对象
    var HotApp = {
        // 配置
        config: {
            baseUrl: '',
            pageSize: 15,
            currentPage: 1,
            loading: false,
            hasMore: true
        },

        // 初始化
        init: function(baseUrl) {
            console.log('[HotApp] Initializing...');
            this.config.baseUrl = baseUrl || '';
            
            // 绑定事件
            this.bindEvents();
            
            // 立即加载数据
            this.loadHotPosts();
            
            console.log('[HotApp] Initialized');
        },

        // 绑定事件
        bindEvents: function() {
            var self = this;
            
            // 加载更多按钮
            var loadMoreBtn = document.getElementById('loadMoreBtn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    self.loadMore();
                });
            }

            // 滚动加载
            var postList = document.getElementById('postList');
            if (postList) {
                postList.addEventListener('scroll', function() {
                    self.handleScroll(this);
                });
            }

            // 微博列表点击事件（事件委托）
            if (postList) {
                postList.addEventListener('click', function(e) {
                    var repostBtn = e.target.closest('.repost-btn');
                    if (repostBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        self.handleRepost(repostBtn);
                    }
                    
                    var likeBtn = e.target.closest('.like-btn');
                    if (likeBtn) {
                        e.preventDefault();
                        e.stopPropagation();
                        self.handleLike(likeBtn);
                    }
                });
            }
        },

        // 处理转发
        handleRepost: function(btn) {
            var self = this;
            var postId = btn.dataset.postId;
            
            console.log('[HotApp] Repost clicked:', postId);
            
            // 跳转到转发页面
            window.location.href = this.config.baseUrl + '/?r=mobile/repost&id=' + postId;
        },

        // 处理点赞
        handleLike: function(btn) {
            var self = this;
            var postId = btn.dataset.postId;
            var isLiked = btn.classList.contains('liked');
            var action = isLiked ? 'unlike' : 'like';
            
            console.log('[HotApp] Like clicked:', postId, 'action:', action);
            
            var url = this.config.baseUrl + '/?r=mobileApi/' + action;
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'id=' + encodeURIComponent(postId)
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.code === 0) {
                    btn.classList.toggle('liked');
                    var countEl = btn.querySelector('.action-count');
                    if (countEl) {
                        countEl.textContent = data.data.likes || 0;
                    }
                } else if (data.code === 401) {
                    window.location.href = self.config.baseUrl + '/?r=mobile/login';
                } else {
                    self.showToast(data.message || '操作失败', 'error');
                }
            })
            .catch(function(error) {
                console.error('[HotApp] Like error:', error);
                self.showToast('操作失败', 'error');
            });
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

        // 加载热门微博列表
        loadHotPosts: function(isLoadMore) {
            var self = this;
            
            if (this.config.loading) {
                console.log('[HotApp] Already loading, skip');
                return;
            }

            this.config.loading = true;
            console.log('[HotApp] Loading hot posts, page:', this.config.currentPage);

            // 显示加载状态
            if (!isLoadMore) {
                this.showLoading();
            }

            // 构建API URL
            var url = this.config.baseUrl + '/?r=mobileApi/hotPosts' + 
                      '&page=' + this.config.currentPage;

            console.log('[HotApp] API URL:', url);

            // 发送请求
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                console.log('[HotApp] Response status:', response.status);
                return response.json();
            })
            .then(function(data) {
                console.log('[HotApp] Response data:', data);
                self.config.loading = false;
                self.hideLoading();

                if (data.code === 0) {
                    self.renderPosts(data.data.items || [], isLoadMore);
                    self.config.hasMore = data.data.pagination ? data.data.pagination.has_more : false;
                    self.updateLoadMoreButton();
                } else {
                    self.showError(data.message || '加载失败');
                }
            })
            .catch(function(error) {
                console.error('[HotApp] Load error:', error);
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
            this.loadHotPosts(true);
        },

        // 渲染微博列表
        renderPosts: function(posts, isAppend) {
            var container = document.getElementById('postList');
            if (!container) {
                console.error('[HotApp] Container not found');
                return;
            }

            if (!isAppend) {
                container.innerHTML = '';
            }

            if (posts.length === 0 && !isAppend) {
                container.innerHTML = '<div class="empty-state">暂无热门内容</div>';
                return;
            }

            var html = '';
            var self = this;
            posts.forEach(function(post) {
                html += self.renderPostItem(post);
            });

            if (isAppend) {
                container.insertAdjacentHTML('beforeend', html);
            } else {
                container.innerHTML = html;
            }
        },

        // 渲染单条微博
        renderPostItem: function(post) {
            var html = '<div class="post-item" data-post-id="' + post.id + '">' +
                '<div class="post-header">' +
                '<a href="' + this.config.baseUrl + '/?r=mobile/user&id=' + post.user_id + '" class="post-avatar">' +
                post.avatar +
                '</a>' +
                '<div class="post-info">' +
                '<a href="' + this.config.baseUrl + '/?r=mobile/user&id=' + post.user_id + '" class="post-username">' +
                this.escapeHtml(post.username) +
                '</a>' +
                '<span class="post-time">' + post.time_ago + '</span>' +
                '</div>' +
                '</div>' +
                '<div class="post-content">' +
                '<a href="' + this.config.baseUrl + '/?r=mobile/detail&id=' + post.id + '" class="post-text">' +
                post.content +
                '</a>';

            // 转发微博，显示原文
            if (post.is_repost && post.original_post) {
                html += this.renderRepostContent(post.original_post);
            }

            if (post.images && post.images.length > 0) {
                html += '<div class="post-images post-images-' + Math.min(post.images.length, 3) + '">';
                post.images.forEach(function(img, index) {
                    var imgUrl = this.config.baseUrl + '/uploads/' + img;
                    html += '<img src="' + imgUrl + '" alt="">';
                }.bind(this));
                html += '</div>';
            }

            html += '</div>' +
                '<div class="post-actions">' +
                '<button class="action-btn repost-btn" data-post-id="' + post.id + '">' +
                '<span class="action-icon">↗️</span>' +
                '<span class="action-count">' + (post.reposts || 0) + '</span>' +
                '</button>' +
                '<a href="' + this.config.baseUrl + '/?r=mobile/detail&id=' + post.id + '" class="action-btn comment-btn">' +
                '<span class="action-icon">💬</span>' +
                '<span class="action-count">' + post.comments + '</span>' +
                '</a>' +
                '<button class="action-btn like-btn ' + (post.is_liked ? 'liked' : '') + '" data-post-id="' + post.id + '">' +
                '<span class="action-icon">❤️</span>' +
                '<span class="action-count">' + post.likes + '</span>' +
                '</button>' +
                '</div>' +
                '</div>';

            return html;
        },

        // 渲染转发内容
        renderRepostContent: function(originalPost) {
            var html = '<div class="repost-content">' +
                '<div class="repost-header">' +
                '<a href="' + this.config.baseUrl + '/?r=mobile/user&id=' + originalPost.user_id + '" class="repost-username">' +
                '@' + this.escapeHtml(originalPost.username) +
                '</a>' +
                '</div>' +
                '<div class="repost-text">' + originalPost.content + '</div>';

            if (originalPost.images && originalPost.images.length > 0) {
                html += '<div class="post-images post-images-' + Math.min(originalPost.images.length, 3) + '">';
                originalPost.images.forEach(function(img) {
                    var imgUrl = this.config.baseUrl + '/uploads/' + img;
                    html += '<img src="' + imgUrl + '" alt="">';
                }.bind(this));
                html += '</div>';
            }

            html += '</div>';
            return html;
        },

        // 显示加载状态
        showLoading: function() {
            var container = document.getElementById('postList');
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
            var container = document.getElementById('postList');
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
            HotApp.init(baseUrl);
        });
    } else {
        // DOM已加载
        var baseUrl = document.body.dataset.baseUrl || '';
        HotApp.init(baseUrl);
    }

    // 暴露到全局
    window.HotApp = HotApp;

})();

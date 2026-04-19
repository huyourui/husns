/**
 * HuSNS Mobile Favorites Page
 * 移动端收藏页面专用脚本
 * @version 4.0.0
 */

(function() {
    'use strict';

    var FavoritesApp = {
        config: {
            baseUrl: '',
            pageSize: 15,
            currentPage: 1,
            loading: false,
            hasMore: true
        },

        init: function(baseUrl) {
            console.log('[FavoritesApp] Initializing...');
            this.config.baseUrl = baseUrl || '';
            this.bindEvents();
            this.loadFavorites();
            console.log('[FavoritesApp] Initialized');
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

            var favoritesList = document.getElementById('favoritesList');
            if (favoritesList) {
                favoritesList.addEventListener('scroll', function() {
                    self.handleScroll(this);
                });
                
                favoritesList.addEventListener('click', function(e) {
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

        loadFavorites: function(isLoadMore) {
            var self = this;
            
            if (this.config.loading) return;
            this.config.loading = true;
            
            if (!isLoadMore) this.showLoading();

            var url = this.config.baseUrl + '/?r=mobileApi/favorites' + 
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
                    self.renderPosts(data.data.items || [], isLoadMore);
                    self.config.hasMore = data.data.pagination ? data.data.pagination.has_more : false;
                    self.updateLoadMoreButton();
                } else if (data.code === 401) {
                    window.location.href = self.config.baseUrl + '/?r=mobile/login';
                } else {
                    self.showError(data.message || '加载失败');
                }
            })
            .catch(function(error) {
                console.error('[FavoritesApp] Load error:', error);
                self.config.loading = false;
                self.hideLoading();
                self.showError('网络错误，请稍后重试');
            });
        },

        loadMore: function() {
            if (!this.config.hasMore || this.config.loading) return;
            this.config.currentPage++;
            this.loadFavorites(true);
        },

        renderPosts: function(posts, isAppend) {
            var container = document.getElementById('favoritesList');
            if (!container) return;

            if (!isAppend) container.innerHTML = '';
            
            if (posts.length === 0 && !isAppend) {
                container.innerHTML = '<div class="empty-state">暂无收藏</div>';
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

            if (post.is_repost && post.original_post) {
                html += this.renderRepostContent(post.original_post);
            }

            if (post.images && post.images.length > 0) {
                html += '<div class="post-images post-images-' + Math.min(post.images.length, 3) + '">';
                post.images.forEach(function(img) {
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

        handleRepost: function(btn) {
            var postId = btn.dataset.postId;
            window.location.href = this.config.baseUrl + '/?r=mobile/repost&id=' + postId;
        },

        handleLike: function(btn) {
            var self = this;
            var postId = btn.dataset.postId;
            var isLiked = btn.classList.contains('liked');
            var action = isLiked ? 'unlike' : 'like';
            
            var url = this.config.baseUrl + '/?r=mobileApi/' + action;
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'id=' + encodeURIComponent(postId)
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.code === 0) {
                    btn.classList.toggle('liked');
                    var countEl = btn.querySelector('.action-count');
                    if (countEl) countEl.textContent = data.data.likes || 0;
                } else if (data.code === 401) {
                    window.location.href = self.config.baseUrl + '/?r=mobile/login';
                }
            })
            .catch(function(error) {
                console.error('[FavoritesApp] Like error:', error);
            });
        },

        showLoading: function() {
            var container = document.getElementById('favoritesList');
            if (container && container.children.length === 0) {
                container.innerHTML = '<div class="loading-state"><div class="loading-spinner"></div>加载中...</div>';
            }
        },

        hideLoading: function() {
            var loading = document.querySelector('.loading-state');
            if (loading) loading.remove();
        },

        showError: function(message) {
            var container = document.getElementById('favoritesList');
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
            FavoritesApp.init(document.body.dataset.baseUrl || '');
        });
    } else {
        FavoritesApp.init(document.body.dataset.baseUrl || '');
    }

    window.FavoritesApp = FavoritesApp;
})();

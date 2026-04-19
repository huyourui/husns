/**
 * HuSNS Mobile Detail Page
 * 移动端微博详情页面专用脚本
 * @version 4.0.0
 */

(function() {
    'use strict';

    // 详情页面应用对象
    var DetailApp = {
        // 配置
        config: {
            baseUrl: '',
            postId: null,
            loading: false
        },

        // 初始化
        init: function(baseUrl) {
            console.log('[DetailApp] Initializing...');
            this.config.baseUrl = baseUrl || '';
            
            // 从DOM获取微博ID
            var postIdEl = document.getElementById('postDetail');
            if (postIdEl && postIdEl.dataset.postId) {
                this.config.postId = postIdEl.dataset.postId;
            }
            
            console.log('[DetailApp] Post ID:', this.config.postId);
            
            // 绑定事件
            this.bindEvents();
            
            // 立即加载数据
            if (this.config.postId) {
                this.loadPostDetail();
                this.loadComments();
            } else {
                this.showError('微博ID不存在');
            }
            
            console.log('[DetailApp] Initialized');
        },

        // 绑定事件
        bindEvents: function() {
            var self = this;
            
            // 评论表单提交
            var commentForm = document.getElementById('commentForm');
            if (commentForm) {
                commentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    self.submitComment(this);
                });
            }

            // 点赞按钮（事件委托）
            var postDetail = document.getElementById('postDetail');
            if (postDetail) {
                postDetail.addEventListener('click', function(e) {
                    var likeBtn = e.target.closest('.like-btn');
                    if (likeBtn) {
                        e.preventDefault();
                        self.handleLike(likeBtn);
                    }
                    
                    var favoriteBtn = e.target.closest('.favorite-btn');
                    if (favoriteBtn) {
                        e.preventDefault();
                        self.handleFavorite(favoriteBtn);
                    }
                });
            }
        },

        // 加载微博详情
        loadPostDetail: function() {
            var self = this;
            
            if (this.config.loading) {
                return;
            }

            this.config.loading = true;
            console.log('[DetailApp] Loading post detail:', this.config.postId);

            // 构建API URL
            var url = this.config.baseUrl + '/?r=mobileApi/postDetail' + 
                      '&id=' + encodeURIComponent(this.config.postId);

            console.log('[DetailApp] API URL:', url);

            // 发送请求
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                console.log('[DetailApp] Response status:', response.status);
                return response.json();
            })
            .then(function(data) {
                console.log('[DetailApp] Response data:', data);
                self.config.loading = false;

                if (data.code === 0) {
                    self.renderPostDetail(data.data.post);
                } else if (data.code === 401) {
                    // 未登录，跳转到登录页
                    window.location.href = self.config.baseUrl + '/?r=mobile/login';
                } else {
                    self.showError(data.message || '加载失败');
                }
            })
            .catch(function(error) {
                console.error('[DetailApp] Load error:', error);
                self.config.loading = false;
                self.showError('网络错误，请稍后重试');
            });
        },

        // 渲染微博详情
        renderPostDetail: function(post) {
            var container = document.getElementById('postDetail');
            if (!container) {
                console.error('[DetailApp] Container not found');
                return;
            }

            var html = '<div class="post-detail">' +
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
                '<div class="post-text">' + post.content + '</div>';

            // 转发微博，显示原文
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
                '<button class="action-btn comment-btn">' +
                '<span class="action-icon">💬</span>' +
                '<span class="action-count">' + post.comments + '</span>' +
                '</button>' +
                '<button class="action-btn like-btn ' + (post.is_liked ? 'liked' : '') + '" data-post-id="' + post.id + '">' +
                '<span class="action-icon">❤️</span>' +
                '<span class="action-count">' + post.likes + '</span>' +
                '</button>' +
                '</div>' +
                '</div>';

            container.innerHTML = html;
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

        // 加载评论列表
        loadComments: function() {
            var self = this;
            
            var url = this.config.baseUrl + '/?r=mobileApi/comments' + 
                      '&post_id=' + encodeURIComponent(this.config.postId);

            console.log('[DetailApp] Loading comments:', url);

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                console.log('[DetailApp] Comments data:', data);
                if (data.code === 0) {
                    self.renderComments(data.data.items || []);
                }
            })
            .catch(function(error) {
                console.error('[DetailApp] Load comments error:', error);
            });
        },

        // 渲染评论列表
        renderComments: function(comments) {
            var container = document.getElementById('commentList');
            if (!container) {
                return;
            }

            if (comments.length === 0) {
                container.innerHTML = '<div class="empty-state">暂无评论</div>';
                return;
            }

            var html = '';
            var self = this;
            comments.forEach(function(comment) {
                html += self.renderCommentItem(comment);
            });

            container.innerHTML = html;
        },

        // 渲染单条评论
        renderCommentItem: function(comment) {
            var html = '<div class="comment-item" data-comment-id="' + comment.id + '">' +
                '<div class="comment-avatar">' + comment.avatar + '</div>' +
                '<div class="comment-body">' +
                '<div class="comment-header">' +
                '<a href="' + this.config.baseUrl + '/?r=mobile/user&id=' + comment.user_id + '" class="comment-username">' +
                this.escapeHtml(comment.username) +
                '</a>' +
                '<span class="comment-time">' + comment.time_ago + '</span>' +
                '</div>' +
                '<div class="comment-content">' + this.escapeHtml(comment.content) + '</div>' +
                '</div>' +
                '</div>';

            return html;
        },

        // 提交评论
        submitComment: function(form) {
            var self = this;
            var input = form.querySelector('input[name="content"]');
            var content = input ? input.value.trim() : '';
            
            if (!content) {
                this.showToast('请输入评论内容', 'error');
                return;
            }

            var url = this.config.baseUrl + '/?r=mobileApi/comment';
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'post_id=' + encodeURIComponent(this.config.postId) + 
                      '&content=' + encodeURIComponent(content)
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.code === 0) {
                    input.value = '';
                    self.showToast('评论成功');
                    self.loadComments(); // 重新加载评论列表
                } else if (data.code === 401) {
                    window.location.href = self.config.baseUrl + '/?r=mobile/login';
                } else {
                    self.showToast(data.message || '评论失败', 'error');
                }
            })
            .catch(function(error) {
                console.error('[DetailApp] Submit comment error:', error);
                self.showToast('评论失败', 'error');
            });
        },

        // 处理点赞
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
                console.error('[DetailApp] Like error:', error);
                self.showToast('操作失败', 'error');
            });
        },

        // 处理收藏
        handleFavorite: function(btn) {
            var self = this;
            var postId = btn.dataset.postId;
            var isFavorited = btn.classList.contains('favorited');
            var action = isFavorited ? 'unfavorite' : 'favorite';
            
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
                    btn.classList.toggle('favorited');
                    self.showToast(isFavorited ? '已取消收藏' : '收藏成功');
                } else if (data.code === 401) {
                    window.location.href = self.config.baseUrl + '/?r=mobile/login';
                } else {
                    self.showToast(data.message || '操作失败', 'error');
                }
            })
            .catch(function(error) {
                console.error('[DetailApp] Favorite error:', error);
                self.showToast('操作失败', 'error');
            });
        },

        // 显示错误
        showError: function(message) {
            var container = document.getElementById('postDetail');
            if (container) {
                container.innerHTML = '<div class="error-state">' + message + '<button onclick="history.back()">返回</button></div>';
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
            DetailApp.init(baseUrl);
        });
    } else {
        // DOM已加载
        var baseUrl = document.body.dataset.baseUrl || '';
        DetailApp.init(baseUrl);
    }

    // 暴露到全局
    window.DetailApp = DetailApp;

})();

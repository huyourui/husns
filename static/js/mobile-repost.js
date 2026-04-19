/**
 * HuSNS Mobile Repost Page
 * 移动端转发页面专用脚本
 * @version 4.0.0
 */

(function() {
    'use strict';

    // 转发页面应用对象
    var RepostApp = {
        // 配置
        config: {
            baseUrl: '',
            postId: null
        },

        // 初始化
        init: function(baseUrl) {
            console.log('[RepostApp] Initializing...');
            this.config.baseUrl = baseUrl || '';
            
            // 从DOM获取微博ID
            var postIdInput = document.querySelector('input[name="post_id"]');
            if (postIdInput) {
                this.config.postId = postIdInput.value;
            }
            
            console.log('[RepostApp] Post ID:', this.config.postId);
            
            // 绑定事件
            this.bindEvents();
            
            // 加载原文预览
            if (this.config.postId) {
                this.loadOriginalPost();
            }
            
            console.log('[RepostApp] Initialized');
        },

        // 绑定事件
        bindEvents: function() {
            var self = this;
            
            // 转发表单提交
            var repostForm = document.getElementById('repostForm');
            if (repostForm) {
                repostForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    self.submitRepost(this);
                });
            }
        },

        // 加载原文预览
        loadOriginalPost: function() {
            var self = this;
            
            console.log('[RepostApp] Loading original post:', this.config.postId);

            // 构建API URL
            var url = this.config.baseUrl + '/?r=mobileApi/postDetail' + 
                      '&id=' + encodeURIComponent(this.config.postId);

            console.log('[RepostApp] API URL:', url);

            // 发送请求
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                console.log('[RepostApp] Response status:', response.status);
                return response.json();
            })
            .then(function(data) {
                console.log('[RepostApp] Response data:', data);

                if (data.code === 0) {
                    self.renderOriginalPost(data.data.post);
                } else if (data.code === 401) {
                    window.location.href = self.config.baseUrl + '/?r=mobile/login';
                } else {
                    self.showError(data.message || '加载失败');
                }
            })
            .catch(function(error) {
                console.error('[RepostApp] Load error:', error);
                self.showError('网络错误，请稍后重试');
            });
        },

        // 渲染原文预览
        renderOriginalPost: function(post) {
            var container = document.getElementById('originalPostPreview');
            if (!container) {
                console.error('[RepostApp] Container not found');
                return;
            }

            var html = '<div class="original-post">' +
                '<div class="original-post-header">' +
                '<div class="original-post-avatar">' + post.avatar + '</div>' +
                '<div class="original-post-info">' +
                '<span class="original-post-username">' + this.escapeHtml(post.username) + '</span>' +
                '<span class="original-post-time">' + post.time_ago + '</span>' +
                '</div>' +
                '</div>' +
                '<div class="original-post-content">' + post.content + '</div>';

            if (post.images && post.images.length > 0) {
                html += '<div class="original-post-images">';
                post.images.forEach(function(img) {
                    var imgUrl = this.config.baseUrl + '/uploads/' + img;
                    html += '<img src="' + imgUrl + '" alt="">';
                }.bind(this));
                html += '</div>';
            }

            html += '</div>';

            container.innerHTML = html;
        },

        // 提交转发
        submitRepost: function(form) {
            var self = this;
            var formData = new FormData(form);
            var content = formData.get('content') || '';
            var postId = formData.get('post_id');
            
            console.log('[RepostApp] Submitting repost:', postId, content);
            
            var url = this.config.baseUrl + '/?r=mobileApi/repost';
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'post_id=' + encodeURIComponent(postId) + 
                      '&content=' + encodeURIComponent(content)
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                console.log('[RepostApp] Response:', data);
                if (data.code === 0) {
                    self.showToast('转发成功');
                    setTimeout(function() {
                        window.location.href = self.config.baseUrl + '/?r=mobile';
                    }, 1000);
                } else if (data.code === 401) {
                    window.location.href = self.config.baseUrl + '/?r=mobile/login';
                } else {
                    self.showToast(data.message || '转发失败', 'error');
                }
            })
            .catch(function(error) {
                console.error('[RepostApp] Submit error:', error);
                self.showToast('转发失败', 'error');
            });
        },

        // 显示错误
        showError: function(message) {
            var container = document.getElementById('originalPostPreview');
            if (container) {
                container.innerHTML = '<div class="error-state">' + message + '</div>';
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
            RepostApp.init(baseUrl);
        });
    } else {
        // DOM已加载
        var baseUrl = document.body.dataset.baseUrl || '';
        RepostApp.init(baseUrl);
    }

    // 暴露到全局
    window.RepostApp = RepostApp;

})();

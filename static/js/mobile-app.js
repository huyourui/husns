/**
 * HuSNS Mobile App - 基于API的移动端应用
 * @version 3.5.0
 */

(function() {
    'use strict';

    var App = {
        baseUrl: '',
        currentPage: 1,
        loading: false,
        hasMore: true,
        currentTab: 'all',
        currentUser: null,
        
        init: function(baseUrl) {
            this.baseUrl = baseUrl;
            this.checkLogin();
            this.bindEvents();
            this.initPage();
        },

        api: function(action, data, method) {
            var self = this;
            method = method || 'GET';
            
            var options = {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };

            var url = this.baseUrl + '/?r=mobileApi/' + action;
            
            if (method === 'GET' && data) {
                var params = [];
                for (var key in data) {
                    if (data.hasOwnProperty(key)) {
                        params.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
                    }
                }
                url += '&' + params.join('&');
            } else if (method === 'POST' && data) {
                if (data instanceof FormData) {
                    options.body = data;
                } else {
                    options.body = JSON.stringify(data);
                    options.headers['Content-Type'] = 'application/json';
                }
            }

            return fetch(url, options)
                .then(function(res) {
                    return res.text().then(function(text) {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('API Error:', text);
                            throw new Error('服务器返回错误');
                        }
                    });
                });
        },

        checkLogin: function() {
            var self = this;
            this.api('userInfo').then(function(res) {
                if (res.code === 0 && res.data.logged_in) {
                    self.currentUser = res.data.user;
                    self.updateUserUI(res.data);
                    self.updateUnreadBadge(res.data.stats.unread_count);
                } else {
                    self.currentUser = null;
                    self.updateUserUI(null);
                }
            }).catch(function(err) {
                console.error('Check login error:', err);
            });
        },

        updateUserUI: function(data) {
            var userAvatar = document.getElementById('userAvatar');
            var userName = document.getElementById('userName');
            var userStats = document.getElementById('userStats');
            
            if (data && data.user) {
                if (userAvatar) userAvatar.innerHTML = data.user.avatar;
                if (userName) userName.textContent = data.user.username;
                if (userStats) {
                    userStats.innerHTML = '<span>微博 ' + data.stats.post_count + '</span>' +
                        '<span>关注 ' + data.stats.following + '</span>' +
                        '<span>粉丝 ' + data.stats.followers + '</span>';
                }
            } else {
                if (userAvatar) userAvatar.innerHTML = '<div class="avatar-placeholder">?</div>';
                if (userName) userName.textContent = '未登录';
                if (userStats) userStats.innerHTML = '';
            }
        },

        updateUnreadBadge: function(count) {
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

        bindEvents: function() {
            var self = this;

            document.addEventListener('click', function(e) {
                var target = e.target;

                if (target.closest('.like-btn')) {
                    e.preventDefault();
                    self.handleLike(target.closest('.like-btn'));
                }

                if (target.closest('.favorite-btn')) {
                    e.preventDefault();
                    self.handleFavorite(target.closest('.favorite-btn'));
                }

                if (target.closest('.follow-btn')) {
                    e.preventDefault();
                    self.handleFollow(target.closest('.follow-btn'));
                }

                if (target.closest('.delete-btn')) {
                    e.preventDefault();
                    if (confirm('确定要删除这条微博吗？')) {
                        self.handleDelete(target.closest('.delete-btn'));
                    }
                }

                if (target.closest('.comment-toggle')) {
                    e.preventDefault();
                    self.toggleComments(target.closest('.comment-toggle'));
                }

                if (target.closest('.load-more-btn')) {
                    e.preventDefault();
                    self.loadMore();
                }

                if (target.closest('.tab-item')) {
                    e.preventDefault();
                    self.switchTab(target.closest('.tab-item'));
                }

                if (target.closest('.mark-all-read')) {
                    e.preventDefault();
                    self.markAllRead();
                }

                if (target.closest('.notification-item')) {
                    self.handleNotificationClick(target.closest('.notification-item'));
                }

                if (target.closest('.image-preview-trigger')) {
                    e.preventDefault();
                    self.previewImage(target.closest('.image-preview-trigger'));
                }

                if (target.closest('.preview-overlay')) {
                    self.closeImagePreview();
                }
            });

            document.addEventListener('submit', function(e) {
                var form = e.target;
                
                if (form.classList.contains('comment-form')) {
                    e.preventDefault();
                    self.submitComment(form);
                }
                
                if (form.id === 'publishForm') {
                    e.preventDefault();
                    self.publishPost(form);
                }
                
                if (form.id === 'loginForm') {
                    e.preventDefault();
                    self.handleLogin(form);
                }
                
                if (form.id === 'registerForm') {
                    e.preventDefault();
                    self.handleRegister(form);
                }
            });

            var imageInput = document.getElementById('imageInput');
            if (imageInput) {
                imageInput.addEventListener('change', function() {
                    self.handleImageSelect(this);
                });
            }

            var contentTextarea = document.getElementById('publishContent');
            if (contentTextarea) {
                contentTextarea.addEventListener('input', function() {
                    self.updateCharCount(this);
                });
            }

            var scrollContainer = document.querySelector('.m-main');
            if (scrollContainer) {
                scrollContainer.addEventListener('scroll', function() {
                    self.handleScroll(this);
                });
            }
        },

        initPage: function() {
            var pageType = document.body.dataset.pageType;
            
            switch (pageType) {
                case 'home':
                    this.loadPosts();
                    break;
                case 'hot':
                    this.loadHotPosts();
                    break;
                case 'topic':
                    this.loadTopicPosts();
                    break;
                case 'detail':
                    this.loadPostDetail();
                    break;
                case 'profile':
                    this.loadProfile();
                    break;
                case 'user':
                    this.loadUserPage();
                    break;
                case 'notification':
                    this.loadNotifications();
                    break;
            }
        },

        loadPosts: function(append) {
            var self = this;
            if (this.loading) return;
            
            this.loading = true;
            this.showLoading();

            var page = append ? this.currentPage + 1 : 1;

            this.api('posts', { page: page, tab: this.currentTab }).then(function(res) {
                self.hideLoading();
                self.loading = false;

                if (res.code === 0) {
                    var posts = res.data.items;
                    var container = document.getElementById('postList');
                    
                    if (!append) {
                        container.innerHTML = '';
                        self.currentPage = 1;
                    }

                    posts.forEach(function(post) {
                        container.insertAdjacentHTML('beforeend', self.renderPostItem(post));
                    });

                    self.hasMore = res.data.pagination.has_more;
                    self.currentPage = page;
                    self.updateLoadMoreButton();
                } else {
                    self.showToast(res.message, 'error');
                }
            }).catch(function(err) {
                self.hideLoading();
                self.loading = false;
                self.showToast('加载失败: ' + err.message, 'error');
            });
        },

        loadHotPosts: function(append) {
            var self = this;
            if (this.loading) return;
            
            this.loading = true;
            this.showLoading();

            var page = append ? this.currentPage + 1 : 1;

            this.api('hotPosts', { page: page }).then(function(res) {
                self.hideLoading();
                self.loading = false;

                if (res.code === 0) {
                    var posts = res.data.items;
                    var container = document.getElementById('postList');
                    
                    if (!append) {
                        container.innerHTML = '';
                        self.currentPage = 1;
                    }

                    posts.forEach(function(post) {
                        container.insertAdjacentHTML('beforeend', self.renderPostItem(post));
                    });

                    self.hasMore = res.data.pagination.has_more;
                    self.currentPage = page;
                    self.updateLoadMoreButton();
                }
            }).catch(function(err) {
                self.hideLoading();
                self.loading = false;
                self.showToast('加载失败', 'error');
            });
        },

        loadTopicPosts: function() {
            var self = this;
            var keyword = document.body.dataset.topicKeyword;
            if (!keyword) return;

            this.loading = true;
            this.showLoading();

            this.api('topicPosts', { keyword: keyword, page: 1 }).then(function(res) {
                self.hideLoading();
                self.loading = false;

                if (res.code === 0) {
                    var container = document.getElementById('postList');
                    container.innerHTML = '';
                    
                    res.data.items.forEach(function(post) {
                        container.insertAdjacentHTML('beforeend', self.renderPostItem(post));
                    });

                    self.hasMore = res.data.pagination.has_more;
                    self.updateLoadMoreButton();
                }
            }).catch(function(err) {
                self.hideLoading();
                self.loading = false;
            });
        },

        loadPostDetail: function() {
            var self = this;
            var postId = document.body.dataset.postId;
            if (!postId) return;

            this.api('postDetail', { id: postId }).then(function(res) {
                if (res.code === 0) {
                    var container = document.getElementById('postDetail');
                    if (container) {
                        container.innerHTML = self.renderPostDetail(res.data.post);
                    }
                    self.loadComments(postId);
                } else {
                    self.showToast(res.message, 'error');
                }
            }).catch(function(err) {
                self.showToast('加载失败', 'error');
            });
        },

        loadComments: function(postId) {
            var self = this;
            this.api('comments', { post_id: postId }).then(function(res) {
                if (res.code === 0) {
                    var container = document.getElementById('commentList');
                    if (container) {
                        container.innerHTML = '';
                        res.data.items.forEach(function(comment) {
                            container.insertAdjacentHTML('beforeend', self.renderCommentItem(comment));
                        });
                    }
                }
            });
        },

        loadProfile: function() {
            var self = this;
            this.api('userInfo').then(function(res) {
                if (res.code === 0 && res.data.logged_in) {
                    self.currentUser = res.data.user;
                    self.updateUserUI(res.data);
                    self.loadUserPosts(res.data.user.id);
                } else {
                    location.href = self.baseUrl + '/?r=mobile/login';
                }
            });
        },

        loadUserPage: function() {
            var self = this;
            var userId = document.body.dataset.userId;
            var username = document.body.dataset.username;
            
            var params = userId ? { id: userId } : { username: username };

            this.api('userProfile', params).then(function(res) {
                if (res.code === 0) {
                    var container = document.getElementById('userProfile');
                    if (container) {
                        container.innerHTML = self.renderUserProfile(res.data);
                    }
                    self.loadUserPosts(res.data.user.id);
                } else {
                    self.showToast(res.message, 'error');
                }
            });
        },

        loadUserPosts: function(userId) {
            var self = this;
            this.api('userPosts', { user_id: userId, page: 1 }).then(function(res) {
                if (res.code === 0) {
                    var container = document.getElementById('postList');
                    if (container) {
                        container.innerHTML = '';
                        res.data.items.forEach(function(post) {
                            container.insertAdjacentHTML('beforeend', self.renderPostItem(post));
                        });
                    }
                }
            });
        },

        loadNotifications: function() {
            var self = this;
            this.api('notifications', { page: 1 }).then(function(res) {
                if (res.code === 0) {
                    var container = document.getElementById('notificationList');
                    if (container) {
                        container.innerHTML = '';
                        res.data.items.forEach(function(notification) {
                            container.insertAdjacentHTML('beforeend', self.renderNotificationItem(notification));
                        });
                    }
                    self.updateUnreadBadge(res.data.unread_count);
                } else if (res.code === 401) {
                    location.href = self.baseUrl + '/?r=mobile/login';
                }
            });
        },

        loadMore: function() {
            var pageType = document.body.dataset.pageType;
            
            switch (pageType) {
                case 'home':
                    this.loadPosts(true);
                    break;
                case 'hot':
                    this.loadHotPosts(true);
                    break;
            }
        },

        switchTab: function(tabEl) {
            var tab = tabEl.dataset.tab;
            if (tab === this.currentTab) return;
            
            this.currentTab = tab;
            
            document.querySelectorAll('.tab-item').forEach(function(el) {
                el.classList.remove('active');
            });
            tabEl.classList.add('active');
            
            this.loadPosts(false);
        },

        handleLike: function(btn) {
            var self = this;
            var postId = btn.dataset.postId;
            var isLiked = btn.classList.contains('liked');

            var action = isLiked ? 'unlike' : 'like';
            
            this.api(action, { id: postId }, 'POST').then(function(res) {
                if (res.code === 0) {
                    btn.classList.toggle('liked');
                    var countEl = btn.querySelector('.action-count');
                    if (countEl) {
                        countEl.textContent = res.data.likes;
                    }
                } else if (res.code === 401) {
                    location.href = self.baseUrl + '/?r=mobile/login';
                } else {
                    self.showToast(res.message, 'error');
                }
            }).catch(function(err) {
                self.showToast('操作失败', 'error');
            });
        },

        handleFavorite: function(btn) {
            var self = this;
            var postId = btn.dataset.postId;
            var isFavorited = btn.classList.contains('favorited');

            var action = isFavorited ? 'unfavorite' : 'favorite';
            
            this.api(action, { id: postId }, 'POST').then(function(res) {
                if (res.code === 0) {
                    btn.classList.toggle('favorited');
                    self.showToast(isFavorited ? '已取消收藏' : '收藏成功', 'success');
                } else if (res.code === 401) {
                    location.href = self.baseUrl + '/?r=mobile/login';
                } else {
                    self.showToast(res.message, 'error');
                }
            }).catch(function(err) {
                self.showToast('操作失败', 'error');
            });
        },

        handleFollow: function(btn) {
            var self = this;
            var userId = btn.dataset.userId;
            var isFollowing = btn.classList.contains('following');

            var action = isFollowing ? 'unfollow' : 'follow';
            
            this.api(action, { user_id: userId }, 'POST').then(function(res) {
                if (res.code === 0) {
                    btn.classList.toggle('following');
                    btn.textContent = isFollowing ? '关注' : '已关注';
                    self.showToast(isFollowing ? '已取消关注' : '关注成功', 'success');
                } else if (res.code === 401) {
                    location.href = self.baseUrl + '/?r=mobile/login';
                } else {
                    self.showToast(res.message, 'error');
                }
            }).catch(function(err) {
                self.showToast('操作失败', 'error');
            });
        },

        handleDelete: function(btn) {
            var self = this;
            var postId = btn.dataset.postId;

            this.api('deletePost', { id: postId }, 'POST').then(function(res) {
                if (res.code === 0) {
                    var postItem = btn.closest('.post-item');
                    if (postItem) {
                        postItem.remove();
                    }
                    self.showToast('删除成功', 'success');
                } else {
                    self.showToast(res.message, 'error');
                }
            }).catch(function(err) {
                self.showToast('删除失败', 'error');
            });
        },

        toggleComments: function(btn) {
            var postId = btn.dataset.postId;
            var commentBox = document.getElementById('commentBox-' + postId);
            
            if (commentBox) {
                if (commentBox.style.display === 'none') {
                    commentBox.style.display = 'block';
                    this.loadComments(postId);
                } else {
                    commentBox.style.display = 'none';
                }
            }
        },

        submitComment: function(form) {
            var self = this;
            var formData = new FormData(form);
            var postId = formData.get('post_id');
            var content = formData.get('content').trim();

            if (!content) {
                this.showToast('请输入评论内容', 'error');
                return;
            }

            var data = {
                post_id: postId,
                content: content,
                parent_id: formData.get('parent_id') || 0,
                reply_to_user_id: formData.get('reply_to_user_id') || 0
            };

            this.api('comment', data, 'POST').then(function(res) {
                if (res.code === 0) {
                    form.reset();
                    self.showToast('评论成功', 'success');
                    self.loadComments(postId);
                } else if (res.code === 401) {
                    location.href = self.baseUrl + '/?r=mobile/login';
                } else {
                    self.showToast(res.message, 'error');
                }
            }).catch(function(err) {
                self.showToast('评论失败', 'error');
            });
        },

        publishPost: function(form) {
            var self = this;
            
            var uploadingItems = document.querySelectorAll('.image-item[data-uploading="true"]');
            if (uploadingItems.length > 0) {
                this.showToast('图片正在上传中，请稍候', 'error');
                return;
            }

            var formData = new FormData(form);
            
            var images = [];
            document.querySelectorAll('.image-item[data-path]').forEach(function(item) {
                images.push(item.dataset.path);
            });
            
            formData.delete('images');
            images.forEach(function(path) {
                formData.append('images[]', path);
            });

            var submitBtn = form.querySelector('.submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = '发布中...';

            this.api('publish', formData, 'POST').then(function(res) {
                if (res.code === 0) {
                    self.showToast('发布成功', 'success');
                    setTimeout(function() {
                        location.href = self.baseUrl + '/?r=mobile';
                    }, 1000);
                } else if (res.code === 401) {
                    location.href = self.baseUrl + '/?r=mobile/login';
                } else {
                    self.showToast(res.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '发布';
                }
            }).catch(function(err) {
                self.showToast('发布失败: ' + err.message, 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = '发布';
            });
        },

        handleLogin: function(form) {
            var self = this;
            var formData = new FormData(form);

            var data = {
                username: formData.get('username'),
                password: formData.get('password')
            };

            this.api('login', data, 'POST').then(function(res) {
                if (res.code === 0) {
                    self.showToast('登录成功', 'success');
                    setTimeout(function() {
                        location.href = self.baseUrl + '/?r=mobile';
                    }, 500);
                } else {
                    self.showToast(res.message, 'error');
                }
            }).catch(function(err) {
                self.showToast('登录失败', 'error');
            });
        },

        handleRegister: function(form) {
            var self = this;
            var formData = new FormData(form);

            var data = {
                username: formData.get('username'),
                password: formData.get('password'),
                confirm_password: formData.get('confirm_password'),
                email: formData.get('email'),
                email_code: formData.get('email_code'),
                invite_code: formData.get('invite_code')
            };

            this.api('register', data, 'POST').then(function(res) {
                if (res.code === 0) {
                    self.showToast('注册成功', 'success');
                    setTimeout(function() {
                        location.href = self.baseUrl + '/?r=mobile';
                    }, 500);
                } else {
                    self.showToast(res.message, 'error');
                }
            }).catch(function(err) {
                self.showToast('注册失败', 'error');
            });
        },

        handleImageSelect: function(input) {
            var self = this;
            var files = input.files;
            if (!files.length) return;

            var container = document.querySelector('.image-list');
            if (!container) return;

            var maxCount = 9 - document.querySelectorAll('.image-item').length;

            for (var i = 0; i < Math.min(files.length, maxCount); i++) {
                (function(file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var tempId = 'upload_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                        var html = '<div class="image-item" id="' + tempId + '" data-uploading="true">' +
                            '<img src="' + e.target.result + '" alt="">' +
                            '<div class="upload-progress">上传中...</div>' +
                            '<button type="button" class="image-remove" onclick="App.removeImage(this)">×</button>' +
                            '</div>';
                        container.insertAdjacentHTML('beforeend', html);

                        var formData = new FormData();
                        formData.append('image', file);

                        self.api('uploadImage', formData, 'POST').then(function(res) {
                            var item = document.getElementById(tempId);
                            if (res.code === 0 && res.data.path) {
                                if (item) {
                                    item.dataset.path = res.data.path;
                                    item.dataset.uploading = 'false';
                                    var progressEl = item.querySelector('.upload-progress');
                                    if (progressEl) progressEl.remove();
                                }
                            } else {
                                self.showToast(res.message || '上传失败', 'error');
                                if (item) item.remove();
                            }
                        }).catch(function(err) {
                            self.showToast('上传失败', 'error');
                            var item = document.getElementById(tempId);
                            if (item) item.remove();
                        });
                    };
                    reader.readAsDataURL(file);
                })(files[i]);
            }

            input.value = '';
        },

        removeImage: function(btn) {
            var item = btn.closest('.image-item');
            if (item) {
                item.remove();
            }
        },

        markAllRead: function() {
            var self = this;
            this.api('markAllRead', {}, 'POST').then(function(res) {
                if (res.code === 0) {
                    self.showToast('全部已读', 'success');
                    self.updateUnreadBadge(0);
                    document.querySelectorAll('.notification-item').forEach(function(item) {
                        item.classList.remove('unread');
                    });
                }
            });
        },

        handleNotificationClick: function(item) {
            var id = item.dataset.id;
            this.api('markRead', { id: id }, 'POST');
            item.classList.remove('unread');
        },

        previewImage: function(trigger) {
            var src = trigger.dataset.src;
            var images = trigger.dataset.images ? trigger.dataset.images.split(',') : [src];
            var index = parseInt(trigger.dataset.index) || 0;

            var overlay = document.createElement('div');
            overlay.className = 'preview-overlay';
            overlay.innerHTML = '<div class="preview-container">' +
                '<img src="' + images[index] + '" class="preview-image">' +
                '<div class="preview-nav">' +
                '<span class="prev-btn" onclick="App.prevImage()">‹</span>' +
                '<span class="preview-counter">' + (index + 1) + '/' + images.length + '</span>' +
                '<span class="next-btn" onclick="App.nextImage()">›</span>' +
                '</div>' +
                '</div>';
            
            overlay.dataset.images = JSON.stringify(images);
            overlay.dataset.index = index;
            
            document.body.appendChild(overlay);
        },

        closeImagePreview: function() {
            var overlay = document.querySelector('.preview-overlay');
            if (overlay) {
                overlay.remove();
            }
        },

        prevImage: function() {
            var overlay = document.querySelector('.preview-overlay');
            if (!overlay) return;
            
            var images = JSON.parse(overlay.dataset.images);
            var index = parseInt(overlay.dataset.index);
            index = index > 0 ? index - 1 : images.length - 1;
            
            overlay.dataset.index = index;
            overlay.querySelector('.preview-image').src = images[index];
            overlay.querySelector('.preview-counter').textContent = (index + 1) + '/' + images.length;
        },

        nextImage: function() {
            var overlay = document.querySelector('.preview-overlay');
            if (!overlay) return;
            
            var images = JSON.parse(overlay.dataset.images);
            var index = parseInt(overlay.dataset.index);
            index = index < images.length - 1 ? index + 1 : 0;
            
            overlay.dataset.index = index;
            overlay.querySelector('.preview-image').src = images[index];
            overlay.querySelector('.preview-counter').textContent = (index + 1) + '/' + images.length;
        },

        updateCharCount: function(textarea) {
            var counter = document.getElementById('charCount');
            if (counter) {
                counter.textContent = textarea.value.length;
            }
        },

        handleScroll: function(container) {
            if (this.loading || !this.hasMore) return;
            
            var scrollTop = container.scrollTop;
            var scrollHeight = container.scrollHeight;
            var clientHeight = container.clientHeight;
            
            if (scrollTop + clientHeight >= scrollHeight - 200) {
                this.loadMore();
            }
        },

        updateLoadMoreButton: function() {
            var btn = document.querySelector('.load-more-btn');
            if (btn) {
                btn.style.display = this.hasMore ? 'block' : 'none';
            }
        },

        showLoading: function() {
            var loading = document.querySelector('.loading-indicator');
            if (loading) {
                loading.style.display = 'block';
            }
        },

        hideLoading: function() {
            var loading = document.querySelector('.loading-indicator');
            if (loading) {
                loading.style.display = 'none';
            }
        },

        showToast: function(message, type) {
            type = type || 'info';
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

        renderPostItem: function(post) {
            var html = '<div class="post-item" data-post-id="' + post.id + '">' +
                '<div class="post-header">' +
                '<a href="' + this.baseUrl + '/?r=mobile/user&id=' + post.user_id + '" class="post-avatar">' +
                post.avatar +
                '</a>' +
                '<div class="post-info">' +
                '<a href="' + this.baseUrl + '/?r=mobile/user&id=' + post.user_id + '" class="post-username">' +
                this.escapeHtml(post.username) +
                '</a>' +
                '<span class="post-time">' + post.time_ago + '</span>' +
                '</div>' +
                '</div>' +
                '<div class="post-content">' +
                '<a href="' + this.baseUrl + '/?r=mobile/detail&id=' + post.id + '" class="post-text">' +
                post.content +
                '</a>';

            if (post.images && post.images.length > 0) {
                html += '<div class="post-images post-images-' + Math.min(post.images.length, 3) + '">';
                var imageUrls = post.images.map(function(img) {
                    return this.baseUrl + '/uploads/' + img;
                }.bind(this));
                post.images.forEach(function(img, index) {
                    var imgUrl = this.baseUrl + '/uploads/' + img;
                    html += '<img src="' + imgUrl + '" alt="" class="image-preview-trigger" ' +
                        'data-src="' + imgUrl + '" data-images="' + imageUrls.join(',') + '" data-index="' + index + '">';
                }.bind(this));
                html += '</div>';
            }

            html += '</div>' +
                '<div class="post-actions">' +
                '<button class="action-btn like-btn ' + (post.is_liked ? 'liked' : '') + '" data-post-id="' + post.id + '">' +
                '<span class="action-icon">❤️</span>' +
                '<span class="action-count">' + post.likes + '</span>' +
                '</button>' +
                '<a href="' + this.baseUrl + '/?r=mobile/detail&id=' + post.id + '" class="action-btn comment-btn">' +
                '<span class="action-icon">💬</span>' +
                '<span class="action-count">' + post.comments + '</span>' +
                '</a>' +
                '<button class="action-btn favorite-btn ' + (post.is_favorited ? 'favorited' : '') + '" data-post-id="' + post.id + '">' +
                '<span class="action-icon">⭐</span>' +
                '</button>' +
                '</div>' +
                '</div>';

            return html;
        },

        renderPostDetail: function(post) {
            var html = '<div class="post-detail">' +
                '<div class="post-header">' +
                '<a href="' + this.baseUrl + '/?r=mobile/user&id=' + post.user_id + '" class="post-avatar">' +
                post.avatar +
                '</a>' +
                '<div class="post-info">' +
                '<a href="' + this.baseUrl + '/?r=mobile/user&id=' + post.user_id + '" class="post-username">' +
                this.escapeHtml(post.username) +
                '</a>' +
                '<span class="post-time">' + post.time_ago + '</span>' +
                '</div>' +
                '</div>' +
                '<div class="post-content">' +
                '<div class="post-text">' + post.content + '</div>';

            if (post.images && post.images.length > 0) {
                html += '<div class="post-images">';
                post.images.forEach(function(img, index) {
                    var imgUrl = this.baseUrl + '/uploads/' + img;
                    html += '<img src="' + imgUrl + '" alt="" class="image-preview-trigger" data-src="' + imgUrl + '">';
                }.bind(this));
                html += '</div>';
            }

            html += '</div>' +
                '<div class="post-actions">' +
                '<button class="action-btn like-btn ' + (post.is_liked ? 'liked' : '') + '" data-post-id="' + post.id + '">' +
                '<span class="action-icon">❤️</span>' +
                '<span class="action-count">' + post.likes + '</span>' +
                '</button>' +
                '<button class="action-btn favorite-btn ' + (post.is_favorited ? 'favorited' : '') + '" data-post-id="' + post.id + '">' +
                '<span class="action-icon">⭐</span>' +
                '</button>' +
                '</div>' +
                '</div>';

            return html;
        },

        renderCommentItem: function(comment) {
            var html = '<div class="comment-item" data-comment-id="' + comment.id + '">' +
                '<div class="comment-avatar">' + comment.avatar + '</div>' +
                '<div class="comment-body">' +
                '<div class="comment-header">' +
                '<a href="' + this.baseUrl + '/?r=mobile/user&id=' + comment.user_id + '" class="comment-username">' +
                this.escapeHtml(comment.username) +
                '</a>' +
                '<span class="comment-time">' + comment.time_ago + '</span>' +
                '</div>' +
                '<div class="comment-content">' + comment.content + '</div>' +
                '</div>' +
                '</div>';

            return html;
        },

        renderNotificationItem: function(notification) {
            var html = '<div class="notification-item ' + (notification.is_read ? '' : 'unread') + '" data-id="' + notification.id + '">' +
                '<div class="notification-avatar">' + notification.sender_avatar + '</div>' +
                '<div class="notification-body">' +
                '<div class="notification-title">' + this.escapeHtml(notification.title) + '</div>' +
                '<div class="notification-time">' + notification.time_ago + '</div>' +
                '</div>' +
                '</div>';

            return html;
        },

        renderUserProfile: function(data) {
            var html = '<div class="user-profile-header">' +
                '<div class="user-avatar">' + data.user.avatar + '</div>' +
                '<div class="user-info">' +
                '<div class="user-name">' + this.escapeHtml(data.user.username) + '</div>' +
                '<div class="user-bio">' + this.escapeHtml(data.user.bio || '') + '</div>' +
                '<div class="user-stats">' +
                '<span>微博 ' + data.stats.post_count + '</span>' +
                '<span>关注 ' + data.stats.following + '</span>' +
                '<span>粉丝 ' + data.stats.followers + '</span>' +
                '</div>' +
                '</div>' +
                '</div>';

            if (this.currentUser && this.currentUser.id !== data.user.id) {
                html += '<div class="user-actions">' +
                    '<button class="follow-btn ' + (data.is_following ? 'following' : '') + '" data-user-id="' + data.user.id + '">' +
                    (data.is_following ? '已关注' : '关注') +
                    '</button>' +
                    '</div>';
            }

            return html;
        },

        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    window.App = App;

    document.addEventListener('DOMContentLoaded', function() {
        var baseUrl = document.body.dataset.baseUrl || '';
        App.init(baseUrl);
    });

})();

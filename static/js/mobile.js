/**
 * HuSNS Mobile JavaScript
 */

(function() {
    'use strict';

    var M = {
        init: function() {
            this.bindEvents();
            this.initPullRefresh();
            this.hideToasts();
        },

        bindEvents: function() {
            document.addEventListener('click', this.handleClick.bind(this));
            document.addEventListener('submit', this.handleSubmit.bind(this));
        },

        handleClick: function(e) {
            var target = e.target;

            if (target.closest('.m-action-btn')) {
                var btn = target.closest('.m-action-btn');
                var action = btn.dataset.action;
                var id = btn.dataset.id;

                if (action === 'like') {
                    this.like(btn, id);
                } else if (action === 'favorite') {
                    this.favorite(btn, id);
                } else if (action === 'comment') {
                    e.preventDefault();
                    this.showCommentInput(id);
                }
            }

            if (target.closest('.m-publish-image-remove')) {
                var item = target.closest('.m-publish-image-item');
                item.remove();
                this.updateImageCount();
            }

            if (target.closest('.m-publish-add-image')) {
                e.preventDefault();
                document.getElementById('imageInput').click();
            }
        },

        handleSubmit: function(e) {
            var form = e.target;
            if (form.id === 'publishForm') {
                e.preventDefault();
                this.publishPost(form);
            } else if (form.id === 'commentForm') {
                e.preventDefault();
                this.submitComment(form);
            }
        },

        like: function(btn, id) {
            fetch(BASE_URL + '/?r=post/like', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id=' + id + '&csrf_token=' + CSRF_TOKEN
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.code === 0) {
                    btn.classList.toggle('liked');
                    var countEl = btn.querySelector('.m-action-count');
                    if (countEl) {
                        countEl.textContent = data.data.likes;
                    }
                } else if (data.code === 401) {
                    location.href = BASE_URL + '/?r=mobile/login';
                }
            });
        },

        favorite: function(btn, id) {
            fetch(BASE_URL + '/?r=post/favorite', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id=' + id + '&csrf_token=' + CSRF_TOKEN
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.code === 0) {
                    btn.classList.toggle('favorited');
                    M.showToast('收藏成功', 'success');
                } else if (data.code === 1) {
                    btn.classList.remove('favorited');
                    M.showToast('已取消收藏', 'info');
                } else if (data.code === 401) {
                    location.href = BASE_URL + '/?r=mobile/login';
                }
            });
        },

        publishPost: function(form) {
            var formData = new FormData(form);
            var images = document.querySelectorAll('.m-publish-image-item img');
            var imageFiles = [];

            images.forEach(function(img) {
                if (img.dataset.path) {
                    imageFiles.push(img.dataset.path);
                }
            });

            formData.delete('images');
            imageFiles.forEach(function(path, index) {
                formData.append('images[' + index + '][path]', path);
            });

            var submitBtn = form.querySelector('.m-publish-submit');
            submitBtn.disabled = true;
            submitBtn.textContent = '发布中...';

            fetch(BASE_URL + '/?r=post/publish', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.code === 0) {
                    M.showToast('发布成功', 'success');
                    setTimeout(function() {
                        location.href = BASE_URL + '/?r=mobile';
                    }, 1000);
                } else {
                    M.showToast(data.msg || '发布失败', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '发布';
                }
            })
            .catch(function() {
                M.showToast('网络错误', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = '发布';
            });
        },

        submitComment: function(form) {
            var formData = new FormData(form);

            fetch(BASE_URL + '/?r=post/comment', {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.code === 0) {
                    M.showToast('评论成功', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    M.showToast(data.msg || '评论失败', 'error');
                }
            });
        },

        showCommentInput: function(postId) {
            var existing = document.querySelector('.m-comment-input');
            if (existing) {
                existing.querySelector('input').focus();
                return;
            }

            var html = '<div class="m-comment-input">' +
                '<form id="commentForm">' +
                '<input type="hidden" name="csrf_token" value="' + CSRF_TOKEN + '">' +
                '<input type="hidden" name="post_id" value="' + postId + '">' +
                '<input type="text" name="content" placeholder="发表评论..." autocomplete="off">' +
                '<button type="submit">发送</button>' +
                '</form>' +
                '</div>';

            document.body.insertAdjacentHTML('beforeend', html);
            document.querySelector('.m-comment-input input').focus();
        },

        showToast: function(msg, type) {
            type = type || 'info';
            var toast = document.createElement('div');
            toast.className = 'm-toast m-toast-' + type;
            toast.textContent = msg;
            document.body.appendChild(toast);

            setTimeout(function() {
                toast.remove();
            }, 2000);
        },

        hideToasts: function() {
            setTimeout(function() {
                var toasts = document.querySelectorAll('.m-toast');
                toasts.forEach(function(toast) {
                    toast.style.opacity = '0';
                    setTimeout(function() { toast.remove(); }, 300);
                });
            }, 3000);
        },

        initPullRefresh: function() {
            var startY = 0;
            var pulling = false;
            var main = document.querySelector('.m-main');

            if (!main) return;

            main.addEventListener('touchstart', function(e) {
                if (main.scrollTop === 0) {
                    startY = e.touches[0].pageY;
                    pulling = true;
                }
            });

            main.addEventListener('touchmove', function(e) {
                if (!pulling) return;
            });

            main.addEventListener('touchend', function() {
                pulling = false;
            });
        },

        updateImageCount: function() {
            var count = document.querySelectorAll('.m-publish-image-item').length;
            var addBtn = document.querySelector('.m-publish-add-image');
            if (addBtn && count >= 9) {
                addBtn.style.display = 'none';
            } else if (addBtn) {
                addBtn.style.display = 'flex';
            }
        },

        previewImage: function(input) {
            var files = input.files;
            if (!files.length) return;

            var container = document.querySelector('.m-publish-images');
            if (!container) return;

            var maxCount = 9 - document.querySelectorAll('.m-publish-image-item').length;

            for (var i = 0; i < Math.min(files.length, maxCount); i++) {
                (function(file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var html = '<div class="m-publish-image-item">' +
                            '<img src="' + e.target.result + '" alt="">' +
                            '<button type="button" class="m-publish-image-remove">×</button>' +
                            '</div>';
                        container.insertAdjacentHTML('beforeend', html);
                        M.updateImageCount();
                    };
                    reader.readAsDataURL(file);
                })(files[i]);
            }

            input.value = '';
        }
    };

    window.M = M;

    document.addEventListener('DOMContentLoaded', function() {
        M.init();
    });

    window.previewImages = function(input) {
        M.previewImage(input);
    };
})();

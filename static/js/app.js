document.addEventListener('DOMContentLoaded', function() {
    var publishForm = document.getElementById('publishForm');
    var imageUpload = document.getElementById('imageUpload');
    var imageCount = document.getElementById('imageCount');
    var previewImages = document.getElementById('previewImages');
    var publishTextarea = publishForm ? publishForm.querySelector('textarea[name="content"]') : null;
    
    window.selectedImages = window.selectedImages || [];
    window.selectedAttachments = window.selectedAttachments || [];
    window.selectedVideos = window.selectedVideos || [];

    if (publishTextarea) {
        publishTextarea.focus();
        
        var text = publishTextarea.value;
        var topicMatch = text.match(/^(#[^#]+#)\s*/);
        if (topicMatch) {
            var cursorPos = topicMatch[0].length;
            publishTextarea.setSelectionRange(cursorPos, cursorPos);
        }
    }

    function updateCharCount(textarea, counterEl, maxLength) {
        var length = textarea.value.length;
        var remaining = maxLength - length;
        
        if (counterEl) {
            counterEl.textContent = length;
            
            var parent = counterEl.closest('.char-counter');
            if (parent) {
                parent.classList.remove('warning', 'danger');
                if (remaining <= 0) {
                    parent.classList.add('danger');
                } else if (remaining <= Math.floor(maxLength * 0.1)) {
                    parent.classList.add('warning');
                }
            }
        }
    }

    function initCharCount(textarea) {
        if (!textarea) return;
        
        var maxLength = parseInt(textarea.dataset.maxLength) || 500;
        var form = textarea.closest('form');
        var counterEl = form ? form.querySelector('.char-count') : null;
        if (!counterEl) {
            counterEl = document.getElementById('postCharCount');
        }
        
        updateCharCount(textarea, counterEl, maxLength);
        
        textarea.addEventListener('input', function() {
            updateCharCount(this, counterEl, maxLength);
        });
    }

    if (publishTextarea) {
        initCharCount(publishTextarea);
    }

    var mentionDropdown = null;
    var mentionStartPos = -1;
    var mentionKeyword = '';
    var mentionTextarea = null;

    function createMentionDropdown() {
        if (mentionDropdown) return;
        
        mentionDropdown = document.createElement('div');
        mentionDropdown.className = 'mention-dropdown';
        mentionDropdown.style.display = 'none';
        document.body.appendChild(mentionDropdown);
    }

    function showMentionDropdown(textarea, keyword, startPos) {
        if (!mentionDropdown) createMentionDropdown();
        
        mentionTextarea = textarea;
        mentionStartPos = startPos;
        mentionKeyword = keyword;
        
        if (keyword.length === 0) {
            hideMentionDropdown();
            return;
        }
        
        fetch(BASE_URL + '/?r=user/suggest&keyword=' + encodeURIComponent(keyword), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code === 0 && data.data.users.length > 0) {
                renderMentionDropdown(data.data.users, textarea);
            } else {
                hideMentionDropdown();
            }
        })
        .catch(function() {
            hideMentionDropdown();
        });
    }

    function renderMentionDropdown(users, textarea) {
        if (!mentionDropdown) createMentionDropdown();
        
        mentionDropdown.innerHTML = '';
        
        users.forEach(function(user) {
            var item = document.createElement('div');
            item.className = 'mention-item';
            var avatarContainer = document.createElement('span');
            avatarContainer.className = 'mention-avatar';
            avatarContainer.innerHTML = user.avatar;
            item.appendChild(avatarContainer);
            var usernameSpan = document.createElement('span');
            usernameSpan.className = 'mention-username';
            usernameSpan.textContent = user.username;
            item.appendChild(usernameSpan);
            item.addEventListener('click', function() {
                insertMention(user.username);
            });
            mentionDropdown.appendChild(item);
        });
        
        var rect = textarea.getBoundingClientRect();
        var lineHeight = parseInt(getComputedStyle(textarea).lineHeight) || 20;
        
        mentionDropdown.style.position = 'absolute';
        mentionDropdown.style.left = rect.left + window.scrollX + 'px';
        mentionDropdown.style.top = rect.top + window.scrollY + lineHeight + 5 + 'px';
        mentionDropdown.style.display = 'block';
        mentionDropdown.style.minWidth = Math.max(150, rect.width / 3) + 'px';
    }

    function hideMentionDropdown() {
        if (mentionDropdown) {
            mentionDropdown.style.display = 'none';
        }
        mentionStartPos = -1;
        mentionKeyword = '';
    }

    function insertMention(username) {
        if (!mentionTextarea || mentionStartPos < 0) return;
        
        var text = mentionTextarea.value;
        var cursorPos = mentionTextarea.selectionStart;
        
        var atPos = text.lastIndexOf('@', cursorPos);
        if (atPos < 0) {
            hideMentionDropdown();
            return;
        }
        
        var beforeAt = text.substring(0, atPos);
        var afterCursor = text.substring(cursorPos);
        
        mentionTextarea.value = beforeAt + '@' + username + ' ' + afterCursor;
        
        var newPos = beforeAt.length + username.length + 2;
        mentionTextarea.setSelectionRange(newPos, newPos);
        mentionTextarea.focus();
        
        var event = new Event('input', { bubbles: true });
        mentionTextarea.dispatchEvent(event);
        
        hideMentionDropdown();
    }

    function handleMentionInput(textarea, e) {
        var text = textarea.value;
        var cursorPos = textarea.selectionStart;
        
        var atPos = text.lastIndexOf('@', cursorPos);
        
        if (atPos < 0 || atPos >= cursorPos) {
            hideMentionDropdown();
            return;
        }
        
        var betweenText = text.substring(atPos + 1, cursorPos);
        
        if (betweenText.indexOf(' ') !== -1 || betweenText.indexOf('\n') !== -1) {
            hideMentionDropdown();
            return;
        }
        
        showMentionDropdown(textarea, betweenText, atPos);
    }

    if (publishTextarea) {
        publishTextarea.addEventListener('input', function(e) {
            handleMentionInput(this, e);
        });
        
        publishTextarea.addEventListener('keydown', function(e) {
            if (!mentionDropdown || mentionDropdown.style.display === 'none') return;
            
            if (e.key === 'Escape') {
                hideMentionDropdown();
                e.preventDefault();
            } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                var items = mentionDropdown.querySelectorAll('.mention-item');
                var activeItem = mentionDropdown.querySelector('.mention-item.active');
                var activeIndex = -1;
                
                items.forEach(function(item, index) {
                    if (item === activeItem) activeIndex = index;
                });
                
                if (activeItem) activeItem.classList.remove('active');
                
                var newIndex;
                if (e.key === 'ArrowDown') {
                    newIndex = activeIndex < items.length - 1 ? activeIndex + 1 : 0;
                } else {
                    newIndex = activeIndex > 0 ? activeIndex - 1 : items.length - 1;
                }
                
                items[newIndex].classList.add('active');
                e.preventDefault();
            } else if (e.key === 'Enter' || e.key === 'Tab') {
                var activeItem = mentionDropdown.querySelector('.mention-item.active');
                if (activeItem) {
                    activeItem.click();
                    e.preventDefault();
                }
            }
        });
        
        publishTextarea.addEventListener('blur', function() {
            setTimeout(function() {
                hideMentionDropdown();
            }, 200);
        });
    }

    document.addEventListener('click', function(e) {
        if (mentionDropdown && !mentionDropdown.contains(e.target)) {
            hideMentionDropdown();
        }
    });

    var insertHideBtn = document.getElementById('insertHideBtn');
    if (insertHideBtn && publishTextarea) {
        insertHideBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            publishTextarea.focus();
            
            var start = publishTextarea.selectionStart;
            var end = publishTextarea.selectionEnd;
            var text = publishTextarea.value;
            var selectedText = text.substring(start, end);
            var hideTag = '[hide]' + (selectedText || '在此输入隐藏内容') + '[/hide]';
            
            publishTextarea.value = text.substring(0, start) + hideTag + text.substring(end);
            
            var cursorPos = start + 6;
            if (!selectedText) {
                publishTextarea.setSelectionRange(cursorPos, cursorPos + 8);
            } else {
                publishTextarea.setSelectionRange(start + 6, start + 6 + selectedText.length);
            }
            
            var event = new Event('input', { bubbles: true });
            publishTextarea.dispatchEvent(event);
        });
    }

    function submitPublishForm(form) {
        var textarea = form.querySelector('textarea[name="content"]');
        var content = textarea ? textarea.value.trim() : '';
        
        if (!content) {
            alert('请输入内容');
            return;
        }

        var formData = new FormData(form);
        
        formData.delete('attachments[]');
        formData.delete('videos[]');
        
        window.selectedImages.forEach(function(file) {
            formData.append('images[]', file);
        });
        
        window.selectedAttachments.forEach(function(file) {
            formData.append('attachments[]', file);
        });
        
        if (window.selectedVideos && window.selectedVideos.length > 0) {
            window.selectedVideos.forEach(function(item) {
                formData.append('videos[]', item.file);
            });
        }

        var submitBtn = form.querySelector('button[type="submit"]');
        var originalText = submitBtn.textContent;
        submitBtn.textContent = '发布中...';
        submitBtn.disabled = true;

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.code === 0) {
                var textarea = form.querySelector('textarea[name="content"]');
                var originalContent = textarea.value;
                var topicMatch = originalContent.match(/#[^#]+#/);
                
                textarea.value = topicMatch ? topicMatch[0] + ' ' : '';
                textarea.focus();
                
                if (topicMatch) {
                    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
                }
                
                var postList = document.querySelector('.post-list');
                if (postList && data.data.html) {
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.data.html;
                    var newPost = tempDiv.firstElementChild;
                    
                    var emptyDiv = postList.querySelector('.empty');
                    if (emptyDiv) {
                        emptyDiv.parentNode.removeChild(emptyDiv);
                    }
                    
                    postList.insertBefore(newPost, postList.firstChild);
                    
                    initPostEvents(newPost);
                    initPostTextExpand();
                }
                
                window.selectedImages = [];
                window.selectedAttachments = [];
                window.selectedVideos = [];
                
                if (attachmentList) {
                    attachmentList.innerHTML = '';
                    attachmentList.style.display = 'none';
                }
                if (attachmentCount) {
                    attachmentCount.textContent = '';
                }
                if (attachmentUpload) {
                    attachmentUpload.value = '';
                }
                
                var videoList = document.getElementById('videoList');
                var videoCount = document.getElementById('videoCount');
                var videoUpload = document.getElementById('videoUpload');
                if (videoList) {
                    videoList.innerHTML = '';
                    videoList.style.display = 'none';
                }
                if (videoCount) {
                    videoCount.textContent = '';
                }
                if (videoUpload) {
                    videoUpload.value = '';
                }
                
                if (previewImages) {
                    previewImages.innerHTML = '';
                }
                if (imageCount) {
                    imageCount.textContent = '';
                }
                if (imageUpload) {
                    imageUpload.value = '';
                }
            } else {
                alert(data.message);
            }
        })
        .catch(function() {
            alert('发布失败，请重试');
        })
        .finally(function() {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    }

    if (publishForm) {
        publishForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitPublishForm(this);
        });
    }

    if (publishTextarea) {
        publishTextarea.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                submitPublishForm(publishForm);
            }
        });
    }

    if (imageUpload) {
        imageUpload.addEventListener('change', function() {
            var files = this.files;
            var maxImageCount = parseInt(this.dataset.maxCount) || 9;
            imageCount.textContent = files.length > 0 ? '(' + files.length + '张)' : '';
            
            previewImages.innerHTML = '';
            for (var i = 0; i < files.length && i < maxImageCount; i++) {
                (function(file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var img = document.createElement('img');
                        img.src = e.target.result;
                        previewImages.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                })(files[i]);
            }
        });
    }

    var attachmentUpload = document.getElementById('attachmentUpload');
    var attachmentCount = document.getElementById('attachmentCount');
    var attachmentList = document.getElementById('attachmentList');

    if (attachmentUpload) {
        attachmentUpload.addEventListener('change', function() {
            var files = this.files;
            var maxSize = parseInt(this.dataset.maxSize) * 1024 * 1024;
            var extensions = this.dataset.extensions.split(',');
            var maxCount = parseInt(this.dataset.maxCount);
            
            var validFiles = [];
            var errors = [];
            
            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                var ext = file.name.split('.').pop().toLowerCase();
                
                if (file.size > maxSize) {
                    errors.push(file.name + ' 超过大小限制(' + this.dataset.maxSize + 'MB)');
                    continue;
                }
                
                if (extensions.indexOf(ext) === -1) {
                    errors.push(file.name + ' 不允许的文件类型');
                    continue;
                }
                
                validFiles.push(file);
            }
            
            if (errors.length > 0) {
                alert(errors.join('\n'));
            }
            
            if (window.selectedAttachments.length + validFiles.length > maxCount) {
                alert('最多只能上传' + maxCount + '个附件');
                validFiles = validFiles.slice(0, maxCount - window.selectedAttachments.length);
            }
            
            for (var i = 0; i < validFiles.length; i++) {
                window.selectedAttachments.push(validFiles[i]);
            }
            
            updateAttachmentList();
            
            var newFiles = new DataTransfer();
            for (var i = 0; i < window.selectedAttachments.length; i++) {
                newFiles.items.add(window.selectedAttachments[i]);
            }
            this.files = newFiles.files;
        });
    }

    function updateAttachmentList() {
        if (!attachmentList) return;
        
        attachmentList.innerHTML = '';
        
        if (window.selectedAttachments.length === 0) {
            attachmentList.style.display = 'none';
            if (attachmentCount) {
                attachmentCount.textContent = '';
            }
            return;
        }
        
        attachmentList.style.display = 'block';
        
        window.selectedAttachments.forEach(function(file, index) {
            var item = document.createElement('div');
            item.className = 'attachment-item';
            
            var ext = file.name.split('.').pop().toLowerCase();
            var isImage = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].indexOf(ext) !== -1;
            
            var previewDiv = document.createElement('div');
            previewDiv.className = 'attachment-preview';
            
            if (isImage) {
                var previewImg = document.createElement('img');
                previewImg.className = 'attachment-preview-img';
                previewImg.alt = '预览';
                
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                };
                reader.readAsDataURL(file);
                
                previewDiv.appendChild(previewImg);
            } else {
                var icon = getFileIcon(file.name);
                var iconSpan = document.createElement('span');
                iconSpan.className = 'attachment-icon';
                iconSpan.textContent = icon;
                previewDiv.appendChild(iconSpan);
            }
            
            var infoDiv = document.createElement('div');
            infoDiv.className = 'attachment-info';
            
            var nameSpan = document.createElement('span');
            nameSpan.className = 'attachment-name';
            nameSpan.textContent = file.name;
            
            var sizeSpan = document.createElement('span');
            sizeSpan.className = 'attachment-size';
            sizeSpan.textContent = formatFileSize(file.size);
            
            infoDiv.appendChild(nameSpan);
            infoDiv.appendChild(sizeSpan);
            
            var removeSpan = document.createElement('span');
            removeSpan.className = 'attachment-remove';
            removeSpan.setAttribute('data-index', index);
            removeSpan.setAttribute('title', '移除');
            removeSpan.textContent = '×';
            
            item.appendChild(previewDiv);
            item.appendChild(infoDiv);
            item.appendChild(removeSpan);
            
            attachmentList.appendChild(item);
        });
        
        if (attachmentCount) {
            attachmentCount.textContent = window.selectedAttachments.length > 0 ? '(' + window.selectedAttachments.length + '个)' : '';
        }
    }

    function getFileIcon(filename) {
        var ext = filename.split('.').pop().toLowerCase();
        var icons = {
            'pdf': '📕',
            'doc': '📘', 'docx': '📘',
            'xls': '📗', 'xlsx': '📗',
            'ppt': '📙', 'pptx': '📙',
            'txt': '📄',
            'zip': '📦', 'rar': '📦', '7z': '📦',
            'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️',
            'mp3': '🎵', 'wav': '🎵',
            'mp4': '🎬', 'avi': '🎬', 'mov': '🎬'
        };
        return icons[ext] || '📄';
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('attachment-remove')) {
            var index = parseInt(e.target.dataset.index);
            window.selectedAttachments.splice(index, 1);
            updateAttachmentList();
            
            if (attachmentUpload) {
                var newFiles = new DataTransfer();
                for (var i = 0; i < window.selectedAttachments.length; i++) {
                    newFiles.items.add(window.selectedAttachments[i]);
                }
                attachmentUpload.files = newFiles.files;
            }
        }
    });

    window.loadComments = function(postId, page) {
        var commentList = document.getElementById('comment-list-' + postId);
        var commentMore = document.getElementById('comment-more-' + postId);
        
        fetch(BASE_URL + '/?r=post/getComments&post_id=' + postId + '&page=' + page, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.code === 0) {
                if (page === 1) {
                    commentList.innerHTML = data.data.html;
                } else {
                    commentList.innerHTML += data.data.html;
                }
                
                var btn = document.querySelector('.comment-toggle[data-id="' + postId + '"]');
                if (btn) {
                    var totalComments = parseInt(btn.dataset.comments) || 0;
                    var loadedCount = commentList.querySelectorAll('.comment-item').length;
                    
                    if (loadedCount < totalComments) {
                        commentMore.style.display = 'block';
                        commentMore.querySelector('.load-more-comments').dataset.page = page + 1;
                    } else {
                        commentMore.style.display = 'none';
                    }
                }
            }
        });
    };

    document.querySelectorAll('.load-more-comments').forEach(function(link) {
        link.addEventListener('click', function() {
            var postId = this.dataset.postId;
            var page = parseInt(this.dataset.page) || 2;
            window.loadComments(postId, page);
        });
    });

    initCommentForms();

    var commentForm = document.getElementById('commentForm');
    if (commentForm) {
        var commentTextarea = commentForm.querySelector('textarea');
        
        function submitDetailComment() {
            var formData = new FormData(commentForm);
            var content = formData.get('content').trim();
            
            if (!content) {
                alert('请输入评论内容');
                return;
            }

            fetch(BASE_URL + '/?r=post/comment', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.code === 0) {
                    commentForm.reset();
                    if (commentTextarea) {
                        commentTextarea.style.height = '28px';
                    }
                    
                    var charCount = commentForm.querySelector('.char-count');
                    if (charCount) {
                        charCount.textContent = '0';
                    }
                    
                    var commentList = document.querySelector('.comment-section .comment-list');
                    if (commentList && data.data.html) {
                        var tempDiv = document.createElement('div');
                        tempDiv.innerHTML = data.data.html;
                        var newComment = tempDiv.firstElementChild;
                        
                        commentList.insertBefore(newComment, commentList.firstChild);
                    }
                    
                    var commentCountEl = document.querySelector('.comment-section h3');
                    if (commentCountEl) {
                        var match = commentCountEl.textContent.match(/评论 \((\d+)\)/);
                        if (match) {
                            var count = parseInt(match[1]) + 1;
                            commentCountEl.textContent = '评论 (' + count + ')';
                        }
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(function() {
                alert('评论失败，请重试');
            });
        }

        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitDetailComment();
        });

        if (commentTextarea) {
            commentTextarea.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    submitDetailComment();
                }
            });
            
            commentTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.max(28, this.scrollHeight) + 'px';
            });
        }
    }
    
    initPostTextExpand();
});

window.previewImage = function(img) {
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center;z-index:9999;cursor:pointer;';
    
    var largeImg = document.createElement('img');
    largeImg.src = img.src;
    largeImg.style.cssText = 'max-width:90%;max-height:90%;';
    
    overlay.appendChild(largeImg);
    overlay.onclick = function() { 
        document.body.removeChild(overlay); 
    };
    
    document.body.appendChild(overlay);
}

window.showReplyForm = function(parentId, replyToUserId, replyToUsername) {
    var existingReplyForm = document.querySelector('.reply-form-container');
    if (existingReplyForm) {
        existingReplyForm.parentNode.removeChild(existingReplyForm);
    }

    var commentItem = document.querySelector('.comment-item[data-comment-id="' + parentId + '"]');
    if (!commentItem) return;

    var replySection = document.getElementById('reply-section-' + parentId);
    var targetElement = replySection ? replySection : commentItem.querySelector('.comment-body');
    
    var commentBox = commentItem.closest('.comment-box');
    var postId = commentBox ? commentBox.id.replace('comment-box-', '') : '';
    
    var csrfInput = document.querySelector('input[name="csrf_token"]');
    var csrfToken = csrfInput ? csrfInput.value : '';
    
    var formContainer = document.createElement('div');
    formContainer.className = 'reply-form-container';
    
    var formHtml = '<form class="comment-form reply-form" data-post-id="' + postId + '" data-parent-id="' + parentId + '" data-reply-to-user-id="' + replyToUserId + '">' +
        '<input type="hidden" name="csrf_token" value="' + csrfToken + '">' +
        '<input type="hidden" name="post_id" value="' + postId + '">' +
        '<input type="hidden" name="parent_id" value="' + parentId + '">' +
        '<input type="hidden" name="reply_to_user_id" value="' + replyToUserId + '">' +
        '<textarea name="content" placeholder="回复 @' + replyToUsername + '..." rows="2"></textarea>' +
        '<div class="comment-form-actions">' +
        '<button type="button" class="btn btn-cancel-reply btn-sm">取消</button>' +
        '<button type="submit" class="btn btn-primary btn-sm">回复</button>' +
        '</div></form>';
    
    formContainer.innerHTML = formHtml;
    
    targetElement.appendChild(formContainer);
    formContainer.querySelector('textarea').focus();
    
    window.initCommentForm(formContainer.querySelector('form'));
};

window.hideReplyForm = function() {
    var existingReplyForm = document.querySelector('.reply-form-container');
    if (existingReplyForm) {
        existingReplyForm.parentNode.removeChild(existingReplyForm);
    }
};

window.loadMoreReplies = function(parentId) {
    var replyList = document.getElementById('reply-list-' + parentId);
    var replyMore = document.getElementById('reply-more-' + parentId);
    
    fetch(BASE_URL + '/?r=post/getReplies&parent_id=' + parentId + '&limit=50', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.code === 0) {
            replyList.innerHTML = data.data.html;
            if (replyMore) {
                replyMore.style.display = 'none';
            }
        }
    });
};

function initCommentForms() {
    document.querySelectorAll('.comment-form:not(.reply-form)').forEach(function(form) {
        if (form.dataset.eventsInitialized !== 'true') {
            window.initCommentForm(form);
        }
    });
}

window.initCommentForm = function(form) {
    if (form.dataset.eventsInitialized === 'true') {
        return;
    }
    form.dataset.eventsInitialized = 'true';
    
    var textarea = form.querySelector('textarea');
    var postId = form.dataset.postId;
    var parentId = form.dataset.parentId || '0';
    
    function autoResize() {
        if (textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.max(28, textarea.scrollHeight) + 'px';
        }
    }
    
    if (textarea) {
        textarea.addEventListener('input', autoResize);
        
        var maxLength = parseInt(textarea.dataset.maxLength) || 500;
        var counterEl = form.querySelector('.char-count');
        
        function updateCount() {
            var length = textarea.value.length;
            var remaining = maxLength - length;
            
            if (counterEl) {
                counterEl.textContent = length;
                
                var parent = counterEl.closest('.char-counter');
                if (parent) {
                    parent.classList.remove('warning', 'danger');
                    if (remaining <= 0) {
                        parent.classList.add('danger');
                    } else if (remaining <= Math.floor(maxLength * 0.1)) {
                        parent.classList.add('warning');
                    }
                }
            }
        }
        
        updateCount();
        textarea.addEventListener('input', updateCount);
    }
    
    function submitComment() {
        var formData = new FormData(form);
        var content = formData.get('content').trim();
        
        if (!content) {
            alert('请输入评论内容');
            return;
        }

        fetch(BASE_URL + '/?r=post/comment', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.code === 0) {
                form.reset();
                if (textarea) {
                    textarea.style.height = '28px';
                }
                
                hideReplyForm();
                
                if (data.data.post_content) {
                    var postText = document.querySelector('.post-item[data-id="' + postId + '"] .post-text');
                    if (postText) {
                        postText.innerHTML = data.data.post_content;
                    }
                }
                
                if (parseInt(parentId) > 0) {
                    loadRepliesAfterComment(parentId, postId);
                } else {
                    if (typeof window.loadComments === 'function') {
                        window.loadComments(postId, 1);
                    } else {
                        location.reload();
                    }
                }
                
                var btn = document.querySelector('.comment-toggle[data-id="' + postId + '"]');
                if (btn) {
                    var count = parseInt(btn.dataset.comments) + 1;
                    btn.dataset.comments = count;
                    btn.textContent = '评论(' + count + ')';
                }
            } else {
                alert(data.message);
            }
        })
        .catch(function() {
            alert('评论失败，请重试');
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitComment();
    });

    if (textarea) {
        textarea.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                submitComment();
            }
        });
    }
};

function loadRepliesAfterComment(parentId, postId) {
    var replySection = document.getElementById('reply-section-' + parentId);
    
    if (!replySection) {
        window.loadComments(postId, 1);
        return;
    }
    
    fetch(BASE_URL + '/?r=post/getReplies&parent_id=' + parentId + '&limit=50', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.code === 0) {
            var replyList = document.getElementById('reply-list-' + parentId);
            if (replyList) {
                replyList.innerHTML = data.data.html;
            }
            
            var replyMore = document.getElementById('reply-more-' + parentId);
            if (replyMore) {
                replyMore.style.display = 'none';
            }
        }
    });
}

function initPostEvents(postElement) {
    var repostBtn = postElement.querySelector('.repost-btn');
    var commentToggle = postElement.querySelector('.comment-toggle');
    var likeBtn = postElement.querySelector('.like-btn');
    var favoriteBtn = postElement.querySelector('.favorite-btn');
    var deleteBtn = postElement.querySelector('.dropdown-item.delete-btn');
    var commentForm = postElement.querySelector('.comment-form:not(.reply-form)');
    var dropdownToggle = postElement.querySelector('.dropdown-toggle');
    var editBtn = postElement.querySelector('.dropdown-item.edit-btn');
    
    if (repostBtn && !repostBtn.dataset.eventsInitialized) {
        repostBtn.dataset.eventsInitialized = 'true';
        repostBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var postId = this.dataset.id;
            window.openRepostModal(postId);
        });
    }
    
    if (commentToggle && !commentToggle.dataset.eventsInitialized) {
        commentToggle.dataset.eventsInitialized = 'true';
        commentToggle.addEventListener('click', function() {
            var id = this.dataset.id;
            var totalComments = parseInt(this.dataset.comments) || 0;
            var commentBox = document.getElementById('comment-box-' + id);
            var commentList = document.getElementById('comment-list-' + id);
            
            if (commentBox) {
                if (commentBox.style.display === 'none') {
                    commentBox.style.display = 'block';
                    if (totalComments > 0 && commentList.innerHTML === '') {
                        window.loadComments(id, 1);
                    }
                    var formTextarea = commentBox.querySelector('form textarea');
                    if (formTextarea) {
                        formTextarea.focus();
                    }
                } else {
                    commentBox.style.display = 'none';
                }
            }
        });
    }
    
    if (likeBtn && !likeBtn.dataset.eventsInitialized) {
        likeBtn.dataset.eventsInitialized = 'true';
        likeBtn.addEventListener('click', function() {
            var id = this.dataset.id;
            var isLiked = this.classList.contains('liked');
            var url = isLiked ? BASE_URL + '/?r=post/unlike' : BASE_URL + '/?r=post/like';
            var btn = this;
            
            var formData = new FormData();
            formData.append('id', id);

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.code === 0) {
                    if (isLiked) {
                        btn.classList.remove('liked');
                    } else {
                        btn.classList.add('liked');
                    }
                    btn.textContent = '点赞(' + data.data.likes + ')';
                }
            });
        });
    }
    
    if (favoriteBtn && !favoriteBtn.dataset.eventsInitialized) {
        favoriteBtn.dataset.eventsInitialized = 'true';
        favoriteBtn.addEventListener('click', function() {
            var id = this.dataset.id;
            var isFavorited = this.dataset.favorited === '1';
            var url = isFavorited ? BASE_URL + '/?r=post/unfavorite' : BASE_URL + '/?r=post/favorite';
            var btn = this;
            
            var formData = new FormData();
            formData.append('id', id);

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.code === 0) {
                    if (isFavorited) {
                        btn.classList.remove('favorited');
                        btn.dataset.favorited = '0';
                        btn.textContent = '收藏';
                    } else {
                        btn.classList.add('favorited');
                        btn.dataset.favorited = '1';
                        btn.textContent = '已收藏';
                    }
                } else {
                    alert(data.message);
                }
            });
        });
    }
    
    if (deleteBtn && !deleteBtn.dataset.eventsInitialized) {
        deleteBtn.dataset.eventsInitialized = 'true';
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('确定要删除这条动态吗？')) return;
            
            var id = this.dataset.id;
            var formData = new FormData();
            formData.append('id', id);

            fetch(BASE_URL + '/?r=post/delete', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.code === 0) {
                    postElement.remove();
                } else {
                    alert(data.message);
                }
            });
        });
    }
    
    if (dropdownToggle && !dropdownToggle.dataset.eventsInitialized) {
        dropdownToggle.dataset.eventsInitialized = 'true';
        dropdownToggle.addEventListener('click', function() {
            var dropdown = this.parentElement.querySelector('.dropdown-menu');
            var allDropdowns = document.querySelectorAll('.dropdown-menu.show');
            
            allDropdowns.forEach(function(menu) {
                if (menu !== dropdown) {
                    menu.classList.remove('show');
                }
            });
            
            dropdown.classList.toggle('show');
        });
    }
    
    if (editBtn && !editBtn.dataset.eventsInitialized) {
        editBtn.dataset.eventsInitialized = 'true';
        editBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var postId = this.dataset.id;
            window.openEditModal(postId);
        });
    }
    
    var pinBtn = postElement.querySelector('.pin-btn');
    if (pinBtn && !pinBtn.dataset.eventsInitialized) {
        pinBtn.dataset.eventsInitialized = 'true';
        pinBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.dataset.id;
            var btn = this;
            var isPinned = this.dataset.pinned === '1';
            
            var formData = new FormData();
            formData.append('id', id);
            
            fetch(BASE_URL + '/?r=post/pin', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.code === 0) {
                    btn.dataset.pinned = data.data.pinned;
                    btn.textContent = data.data.pinned ? '📌 取消置顶' : '📌 置顶';
                } else {
                    alert(data.message || '操作失败');
                }
            })
            .catch(function() {
                alert('操作失败');
            });
        });
    }
    
    var featureBtn = postElement.querySelector('.feature-btn');
    if (featureBtn && !featureBtn.dataset.eventsInitialized) {
        featureBtn.dataset.eventsInitialized = 'true';
        featureBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.dataset.id;
            var btn = this;
            var isFeatured = this.dataset.featured === '1';
            
            var formData = new FormData();
            formData.append('id', id);
            
            fetch(BASE_URL + '/?r=post/feature', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.code === 0) {
                    btn.dataset.featured = data.data.featured;
                    btn.textContent = data.data.featured ? '⭐ 取消加精' : '⭐ 加精';
                } else {
                    alert(data.message || '操作失败');
                }
            })
            .catch(function() {
                alert('操作失败');
            });
        });
    }
    
    if (commentForm) {
        window.initCommentForm(commentForm);
    }
}

window.openEditModal = function(postId) {
    fetch(BASE_URL + '/?r=post/getEditData&id=' + postId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            var modal = document.getElementById('editModal');
            if (!modal) {
                createEditModal();
                modal = document.getElementById('editModal');
            }
            document.getElementById('editPostId').value = data.data.id;
            document.getElementById('editContent').value = data.data.content;
            modal.style.display = 'flex';
        } else {
            alert(data.message || '获取数据失败');
        }
    })
    .catch(function() {
        alert('获取数据失败');
    });
};

function createEditModal() {
    var modal = document.createElement('div');
    modal.id = 'editModal';
    modal.className = 'modal';
    modal.style.display = 'none';
    modal.innerHTML = '<div class="modal-content" style="width:600px;">' +
        '<div class="modal-header">' +
        '<h3>编辑微博</h3>' +
        '<span class="modal-close" onclick="closeEditModal()">&times;</span>' +
        '</div>' +
        '<form id="editForm">' +
        '<input type="hidden" name="id" id="editPostId">' +
        '<div class="form-group">' +
        '<textarea name="content" id="editContent" rows="5" style="width:100%;resize:vertical;padding:10px;border:1px solid #ddd;border-radius:4px;"></textarea>' +
        '</div>' +
        '<div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px;padding-top:10px;border-top:1px solid #eee;margin-top:10px;">' +
        '<button type="button" class="btn" onclick="closeEditModal()">取消</button>' +
        '<button type="submit" class="btn btn-primary">保存</button>' +
        '</div>' +
        '</form>' +
        '</div>';
    
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:1000;';
    modal.querySelector('.modal-content').style.cssText = 'background:#fff;border-radius:8px;max-width:90%;';
    modal.querySelector('.modal-header').style.cssText = 'display:flex;justify-content:space-between;align-items:center;padding:15px 20px;border-bottom:1px solid #eee;';
    modal.querySelector('.modal-header h3').style.cssText = 'margin:0;';
    modal.querySelector('.modal-close').style.cssText = 'font-size:24px;cursor:pointer;color:#999;';
    modal.querySelector('form').style.cssText = 'padding:20px;';
    modal.querySelector('.form-group').style.cssText = 'margin-bottom:15px;';
    
    document.body.appendChild(modal);
    
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitEdit();
    });
}

window.closeEditModal = function() {
    var modal = document.getElementById('editModal');
    if (modal) {
        modal.style.display = 'none';
    }
};

 function submitEdit() {
    var formData = new FormData(document.getElementById('editForm'));
    
    fetch(BASE_URL + '/?r=post/edit', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            var postId = formData.get('id');
            var postItem = document.querySelector('.post-item[data-id="' + postId + '"]');
            if (postItem) {
                var contentEl = postItem.querySelector('.post-text');
                if (contentEl) {
                    contentEl.innerHTML = data.data.content.replace(/\n/g, '<br>');
                }
            }
            window.closeEditModal();
        } else {
            alert(data.message || '编辑失败');
        }
    })
    .catch(function() {
        alert('编辑失败');
    });
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.action-dropdown')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
            menu.classList.remove('show');
        });
    }
    
    if (e.target.classList.contains('load-more-replies')) {
        e.preventDefault();
        var parentId = e.target.dataset.parentId;
        loadMoreReplies(parentId);
    }
    
    if (e.target.classList.contains('btn-cancel-reply')) {
        e.preventDefault();
        hideReplyForm();
    }
    
    if (e.target.classList.contains('post-text-expand')) {
        e.preventDefault();
        var postText = e.target.previousElementSibling;
        if (postText) {
            if (postText.classList.contains('collapsed')) {
                postText.classList.remove('collapsed');
                e.target.textContent = '收起';
            } else {
                postText.classList.add('collapsed');
                e.target.textContent = '展开全文';
            }
        }
    }
});

function initPostTextExpand() {
    var postTexts = document.querySelectorAll('.post-text');
    for (var i = 0; i < postTexts.length; i++) {
        var el = postTexts[i];
        var nextSibling = el.nextElementSibling;
        var hasExpandBtn = nextSibling && nextSibling.classList.contains('post-text-expand');
        
        if (hasExpandBtn) {
            continue;
        }
        
        var lineHeight = parseFloat(window.getComputedStyle(el).lineHeight) || 22;
        var maxHeight = lineHeight * 4;
        
        if (el.scrollHeight > maxHeight + 10) {
            el.classList.add('collapsed');
            var expandBtn = document.createElement('span');
            expandBtn.className = 'post-text-expand';
            expandBtn.textContent = '展开全文';
            el.parentNode.insertBefore(expandBtn, el.nextSibling);
        }
    }
}

function initThemeToggle() {
    var themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;
    
    var savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        themeToggle.textContent = '☀️';
    } else {
        document.body.classList.remove('dark-mode');
        themeToggle.textContent = '🌙';
    }
    
    themeToggle.addEventListener('click', function() {
        var isDark = document.body.classList.toggle('dark-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        themeToggle.textContent = isDark ? '☀️' : '🌙';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initThemeToggle();
    initRepostButtons();
    window.initRepostModal();
    window.initAllPostEvents();
});

window.initAllPostEvents = function() {
    document.querySelectorAll('.post-item').forEach(function(postItem) {
        initPostEvents(postItem);
    });
};

function initRepostButtons() {
    var buttons = document.querySelectorAll('.repost-btn');
    console.log('initRepostButtons found:', buttons.length, 'buttons');
    buttons.forEach(function(btn) {
        if (btn.dataset.eventsInitialized === 'true') return;
        btn.dataset.eventsInitialized = 'true';
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var postId = this.dataset.id;
            console.log('repost-btn clicked, postId:', postId);
            window.openRepostModal(postId);
        });
    });
}

window.initRepostModal = function() {
    var modal = document.getElementById('repostModal');
    if (!modal) return;
    
    if (modal.dataset.initialized === 'true') return;
    modal.dataset.initialized = 'true';
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            window.closeRepostModal();
        }
    });
    
    var form = document.getElementById('repostForm');
    if (form) {
        var textarea = form.querySelector('textarea');
        var charCount = form.querySelector('.char-count');
        var maxLength = 500;
        
        if (textarea && textarea.dataset.maxLength) {
            maxLength = parseInt(textarea.dataset.maxLength) || 500;
        }
        
        if (textarea && charCount) {
            textarea.addEventListener('input', function() {
                var length = this.value.length;
                charCount.textContent = length;
                
                var counter = charCount.closest('.char-counter');
                if (counter) {
                    counter.classList.remove('warning', 'danger');
                    if (length > maxLength) {
                        counter.classList.add('danger');
                    } else if (length > maxLength * 0.9) {
                        counter.classList.add('warning');
                    }
                }
            });
            
            textarea.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    submitRepost();
                }
            });
        }
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitRepost();
        });
    }
};

window.openRepostModal = function(postId) {
    var modal = document.getElementById('repostModal');
    if (!modal) {
        console.log('repostModal not found');
        return;
    }
    
    var postIdInput = document.getElementById('repostPostId');
    if (postIdInput) {
        postIdInput.value = postId;
    }
    modal.style.display = 'flex';
    
    var form = document.getElementById('repostForm');
    if (form) {
        var textarea = form.querySelector('textarea');
        var charCount = form.querySelector('.char-count');
        var checkbox = form.querySelector('input[name="also_comment"]');
        
        if (textarea) {
            textarea.value = '';
            textarea.focus();
        }
        if (charCount) charCount.textContent = '0';
        if (checkbox) checkbox.checked = false;
    }
};

window.closeRepostModal = function() {
    var modal = document.getElementById('repostModal');
    if (modal) {
        modal.style.display = 'none';
    }
};

function submitRepost() {
    var form = document.getElementById('repostForm');
    if (!form) return;
    
    var formData = new FormData(form);
    var content = formData.get('content').trim();
    var postId = formData.get('post_id');
    
    var submitBtn = form.querySelector('button[type="submit"]');
    var originalText = submitBtn.textContent;
    submitBtn.textContent = '转发中...';
    submitBtn.disabled = true;
    
    fetch(BASE_URL + '/?r=post/repost', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.code === 0) {
            window.closeRepostModal();
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(function() {
        alert('转发失败，请重试');
    })
    .finally(function() {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function initCommentAlsoRepost() {
    var checkboxes = document.querySelectorAll('.also-repost-checkbox input');
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var form = this.closest('form');
            var hiddenInput = form.querySelector('input[name="also_repost"]');
            if (hiddenInput) {
                hiddenInput.value = this.checked ? '1' : '0';
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initCommentAlsoRepost();
});

window.playVideo = function(container) {
    var video = container.querySelector('video');
    var overlay = container.querySelector('.video-overlay');
    
    if (!video) return;
    
    var allVideoItems = document.querySelectorAll('.post-video-item.playing');
    allVideoItems.forEach(function(item) {
        if (item !== container) {
            var itemVideo = item.querySelector('video');
            if (itemVideo && !itemVideo.paused) {
                itemVideo.pause();
            }
        }
    });
    
    if (container.classList.contains('playing')) {
        if (video.paused) {
            video.play();
        } else {
            video.pause();
        }
    } else {
        container.classList.add('playing');
        if (overlay) {
            overlay.style.display = 'none';
        }
        video.controls = true;
        video.currentTime = 0;
        video.play();
    }
};

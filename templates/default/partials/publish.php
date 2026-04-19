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
<?php if (isset($_SESSION['user_id'])): ?>
<?php 
$maxPostLength = Setting::getMaxPostLength(); 
$maxAttachmentSize = Setting::getMaxAttachmentSize();
$maxImageSize = Setting::getMaxImageSize();
$maxImageCount = Setting::getMaxImageCount();
$allowedExtensions = Setting::getAllowedAttachmentExtensions();
$maxAttachmentCount = Setting::getMaxAttachmentCount();
$maxVideoSize = Setting::getMaxVideoSize();
$maxVideoCount = Setting::getMaxVideoCount();
$publishPlaceholder = Setting::getPublishPlaceholder();
$hideTagAdminOnly = Setting::isHideTagAdminOnly();
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$showHideBtn = !$hideTagAdminOnly || $isAdmin;
if (empty($publishPlaceholder)) {
    $publishPlaceholder = $this->t('post.placeholder');
}

$phpPostMaxSize = ini_get('post_max_size');
$phpUploadMaxSize = ini_get('upload_max_filesize');
$phpMaxSize = min(
    (int)preg_replace('/[^0-9]/', '', $phpPostMaxSize),
    (int)preg_replace('/[^0-9]/', '', $phpUploadMaxSize)
);
$effectiveMaxVideoSize = min($maxVideoSize, $phpMaxSize);
?>
<div class="publish-box">
    <form id="publishForm" action="<?php echo $this->url('post/publish'); ?>" method="post" enctype="multipart/form-data">
        <?php echo $this->csrf(); ?>
        <textarea name="content" placeholder="<?php echo $this->escape($publishPlaceholder); ?>" rows="3" data-max-length="<?php echo $maxPostLength; ?>" id="publishContent"><?php echo isset($topic) ? '#' . $this->escape($topic) . '# ' : ''; ?></textarea>
        <div class="publish-actions">
            <div class="upload-btn emoji-btn">
                <button type="button" id="emojiBtn" title="表情">😊</button>
            </div>
            <div class="upload-btn">
                <input type="file" name="images[]" multiple accept="image/*" id="imageUpload" data-max-count="<?php echo $maxImageCount; ?>">
                <label for="imageUpload" title="图片">📷</label>
                <span id="imageCount"></span>
            </div>
            <div class="upload-btn">
                <input type="file" name="videos[]" multiple accept="video/*" id="videoUpload" data-max-size="<?php echo $effectiveMaxVideoSize; ?>" data-max-count="<?php echo $maxVideoCount; ?>">
                <label for="videoUpload" title="视频">🎬</label>
                <span id="videoCount"></span>
            </div>
            <div class="upload-btn">
                <input type="file" name="attachments[]" multiple id="attachmentUpload" data-max-size="<?php echo $maxAttachmentSize; ?>" data-extensions="<?php echo implode(',', $allowedExtensions); ?>" data-max-count="<?php echo $maxAttachmentCount; ?>">
                <label for="attachmentUpload" title="附件">📎</label>
                <span id="attachmentCount"></span>
            </div>
            <?php if ($showHideBtn): ?>
            <div class="upload-btn hide-btn">
                <button type="button" id="insertHideBtn" title="隐藏内容">🔒</button>
            </div>
            <?php endif; ?>
            <div class="char-counter">
                <span id="postCharCount">0</span>/<span id="postMaxChars"><?php echo $maxPostLength; ?></span>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $this->t('common.publish'); ?></button>
        </div>
        <div id="previewImages" class="preview-images"></div>
        <div id="videoList" class="video-list" style="display:none;"></div>
        <div id="attachmentList" class="attachment-list" style="display:none;"></div>
        
        <div id="emojiPanel" class="emoji-panel" style="display:none;">
            <div class="emoji-panel-header">
                <span class="emoji-tab active" data-category="default"><?php echo $this->t('emoji.default'); ?></span>
                <span class="emoji-tab" data-category="hot"><?php echo $this->t('emoji.hot'); ?></span>
                <span class="emoji-tab" data-category="emoji">Emoji</span>
            </div>
            <div class="emoji-panel-content" id="emojiContent"></div>
        </div>
    </form>
</div>
<script>
(function() {
    var maxImageSize = <?php echo $maxImageSize; ?> * 1024 * 1024;
    var maxImageCount = <?php echo $maxImageCount; ?>;
    var maxAttachmentSize = <?php echo $maxAttachmentSize; ?> * 1024 * 1024;
    var maxVideoSize = <?php echo $effectiveMaxVideoSize; ?> * 1024 * 1024;
    var allowedExtensions = <?php echo json_encode($allowedExtensions); ?>;
    var maxAttachmentCount = <?php echo $maxAttachmentCount; ?>;
    var maxVideoCount = <?php echo $maxVideoCount; ?>;
    
    var imageInput = document.getElementById('imageUpload');
    var videoInput = document.getElementById('videoUpload');
    var attachmentInput = document.getElementById('attachmentUpload');
    var previewContainer = document.getElementById('previewImages');
    var videoList = document.getElementById('videoList');
    var attachmentList = document.getElementById('attachmentList');
    var imageCount = document.getElementById('imageCount');
    var videoCount = document.getElementById('videoCount');
    var attachmentCount = document.getElementById('attachmentCount');
    
    window.selectedImages = window.selectedImages || [];
    window.selectedVideos = window.selectedVideos || [];
    window.selectedAttachments = window.selectedAttachments || [];
    
    function updateImageCount() {
        imageCount.textContent = window.selectedImages.length > 0 ? '(' + window.selectedImages.length + '/' + maxImageCount + ')' : '';
    }
    
    function updateVideoCount() {
        videoCount.textContent = window.selectedVideos.length > 0 ? '(' + window.selectedVideos.length + '/' + maxVideoCount + ')' : '';
    }
    
    function updateAttachmentCount() {
        attachmentCount.textContent = window.selectedAttachments.length > 0 ? '(' + window.selectedAttachments.length + '/' + maxAttachmentCount + ')' : '';
    }
    
    function addImagePreview(file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = '<img src="' + e.target.result + '" alt="预览"><span class="remove-btn" data-type="image">&times;</span>';
            previewContainer.appendChild(div);
        };
        reader.readAsDataURL(file);
    }
    
    function addVideoItem(file, progress) {
        videoList.style.display = 'flex';
        var div = document.createElement('div');
        div.className = 'video-upload-item';
        var progressText = progress !== undefined ? progress + '%' : '准备中...';
        div.innerHTML = '<div class="video-icon">🎬</div><div class="video-info"><span class="video-name">' + file.name + '</span><span class="video-size">' + formatSize(file.size) + '</span></div><div class="video-progress"><div class="progress-bar"><div class="progress-fill" style="width:' + (progress || 0) + '%"></div></div><span class="progress-text">' + progressText + '</span></div><span class="remove-btn" data-type="video">&times;</span>';
        videoList.appendChild(div);
        return div;
    }
    
    function addAttachmentItem(file) {
        attachmentList.style.display = 'flex';
        var div = document.createElement('div');
        div.className = 'attachment-item';
        div.innerHTML = '<span class="attachment-name">' + file.name + '</span><span class="attachment-size">' + formatSize(file.size) + '</span><span class="remove-btn" data-type="attachment">&times;</span>';
        attachmentList.appendChild(div);
    }
    
    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
    
    function handleImageFile(file) {
        if (!file.type.startsWith('image/')) return false;
        if (file.size > maxImageSize) {
            alert('图片 "' + file.name + '" 超过大小限制（最大<?php echo $maxImageSize; ?>MB）');
            return false;
        }
        if (window.selectedImages.length >= maxImageCount) {
            alert('最多上传' + maxImageCount + '张图片');
            return false;
        }
        
        var ext = 'png';
        if (file.type === 'image/jpeg' || file.type === 'image/jpg') {
            ext = 'jpg';
        } else if (file.type === 'image/gif') {
            ext = 'gif';
        } else if (file.type === 'image/webp') {
            ext = 'webp';
        }
        
        if (!file.name || file.name === 'image.png' || file.name.indexOf('.') === -1) {
            file = new File([file], 'paste_' + Date.now() + '.' + ext, { type: file.type });
        }
        
        window.selectedImages.push(file);
        addImagePreview(file);
        updateImageCount();
        return true;
    }
    
    function handleVideoFile(file) {
        if (!file.type.startsWith('video/')) {
            alert('请选择视频文件');
            return false;
        }
        if (file.size > maxVideoSize) {
            alert('视频 "' + file.name + '" 超过大小限制（最大<?php echo $effectiveMaxVideoSize; ?>MB，服务器限制）');
            return false;
        }
        if (window.selectedVideos.length >= maxVideoCount) {
            alert('最多上传' + maxVideoCount + '个视频');
            return false;
        }
        
        var item = addVideoItem(file, 0);
        window.selectedVideos.push({ file: file, element: item });
        updateVideoCount();
        
        simulateVideoProgress(file, item);
        return true;
    }
    
    function simulateVideoProgress(file, element) {
        var progress = 0;
        var interval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
            }
            var progressFill = element.querySelector('.progress-fill');
            var progressText = element.querySelector('.progress-text');
            if (progressFill) progressFill.style.width = progress + '%';
            if (progressText) progressText.textContent = Math.round(progress) + '%';
        }, 200);
    }
    
    function handleAttachmentFile(file) {
        var ext = file.name.split('.').pop().toLowerCase();
        if (allowedExtensions.length > 0 && allowedExtensions.indexOf(ext) === -1) {
            alert('不支持的文件类型：' + ext);
            return false;
        }
        if (file.size > maxAttachmentSize) {
            alert('附件 "' + file.name + '" 超过大小限制（最大<?php echo $maxAttachmentSize; ?>MB）');
            return false;
        }
        if (window.selectedAttachments.length >= maxAttachmentCount) {
            alert('最多上传' + maxAttachmentCount + '个附件');
            return false;
        }
        window.selectedAttachments.push(file);
        addAttachmentItem(file);
        updateAttachmentCount();
        return true;
    }
    
    document.getElementById('publishContent').addEventListener('paste', function(e) {
        var items = e.clipboardData.items;
        if (!items) return;
        
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            if (item.kind === 'file') {
                var file = item.getAsFile();
                if (file.type.startsWith('image/')) {
                    e.preventDefault();
                    handleImageFile(file);
                } else if (file.type.startsWith('video/')) {
                    e.preventDefault();
                    handleVideoFile(file);
                } else {
                    e.preventDefault();
                    handleAttachmentFile(file);
                }
            }
        }
    });
    
    imageInput.addEventListener('change', function(e) {
        var files = Array.from(e.target.files);
        files.forEach(function(file) {
            handleImageFile(file);
        });
        imageInput.value = '';
    });
    
    videoInput.addEventListener('change', function(e) {
        var files = Array.from(e.target.files);
        files.forEach(function(file) {
            handleVideoFile(file);
        });
        videoInput.value = '';
    });
    
    attachmentInput.addEventListener('change', function(e) {
        var files = Array.from(e.target.files);
        files.forEach(function(file) {
            handleAttachmentFile(file);
        });
        attachmentInput.value = '';
    });
    
    previewContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-btn')) {
            var index = Array.from(previewContainer.children).indexOf(e.target.parentElement);
            window.selectedImages.splice(index, 1);
            e.target.parentElement.remove();
            updateImageCount();
        }
    });
    
    videoList.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-btn')) {
            var index = Array.from(videoList.children).indexOf(e.target.parentElement);
            window.selectedVideos.splice(index, 1);
            e.target.parentElement.remove();
            updateVideoCount();
            if (window.selectedVideos.length === 0) {
                videoList.style.display = 'none';
            }
        }
    });
    
    attachmentList.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-btn')) {
            var index = Array.from(attachmentList.children).indexOf(e.target.parentElement);
            window.selectedAttachments.splice(index, 1);
            e.target.parentElement.remove();
            updateAttachmentCount();
            if (window.selectedAttachments.length === 0) {
                attachmentList.style.display = 'none';
            }
        }
    });
    
    var emojiData = {
        default: [],
        hot: [],
        emoji: [
            {name: '😀', code: 'grinning', url: ''},
            {name: '😃', code: 'smiley', url: ''},
            {name: '😄', code: 'smile', url: ''},
            {name: '😁', code: 'grin', url: ''},
            {name: '😆', code: 'laughing', url: ''},
            {name: '😅', code: 'sweat_smile', url: ''},
            {name: '🤣', code: 'rofl', url: ''},
            {name: '😂', code: 'joy', url: ''},
            {name: '🙂', code: 'slightly_smiling', url: ''},
            {name: '🙃', code: 'upside_down', url: ''},
            {name: '😉', code: 'wink', url: ''},
            {name: '😊', code: 'blush', url: ''},
            {name: '😇', code: 'innocent', url: ''},
            {name: '🥰', code: 'smiling_face_with_3_hearts', url: ''},
            {name: '😍', code: 'heart_eyes', url: ''},
            {name: '🤩', code: 'star-struck', url: ''},
            {name: '😘', code: 'kissing_heart', url: ''},
            {name: '😗', code: 'kissing', url: ''},
            {name: '😚', code: 'kissing_closed_eyes', url: ''},
            {name: '😙', code: 'kissing_smiling_eyes', url: ''},
            {name: '🥲', code: 'smiling_face_with_tear', url: ''},
            {name: '😋', code: 'yum', url: ''},
            {name: '😛', code: 'stuck_out_tongue', url: ''},
            {name: '😜', code: 'stuck_out_tongue_winking_eye', url: ''},
            {name: '🤪', code: 'zany_face', url: ''},
            {name: '😝', code: 'stuck_out_tongue_closed_eyes', url: ''},
            {name: '🤑', code: 'money_mouth_face', url: ''},
            {name: '🤗', code: 'hugging_face', url: ''},
            {name: '🤭', code: 'face_with_hand_over_mouth', url: ''},
            {name: '🤫', code: 'shushing_face', url: ''},
            {name: '🤔', code: 'thinking_face', url: ''},
            {name: '🤐', code: 'zipper_mouth_face', url: ''},
            {name: '🤨', code: 'face_with_raised_eyebrow', url: ''},
            {name: '😐', code: 'neutral_face', url: ''},
            {name: '😑', code: 'expressionless', url: ''},
            {name: '😶', code: 'no_mouth', url: ''},
            {name: '😏', code: 'smirk', url: ''},
            {name: '😒', code: 'unamused', url: ''},
            {name: '🙄', code: 'roll_eyes', url: ''},
            {name: '😬', code: 'grimacing', url: ''},
            {name: '😮', code: 'open_mouth', url: ''},
            {name: '🤯', code: 'exploding_head', url: ''},
            {name: '😱', code: 'scream', url: ''},
            {name: '😨', code: 'fearful', url: ''},
            {name: '😰', code: 'anxious', url: ''},
            {name: '😥', code: 'sad_relieved', url: ''},
            {name: '😢', code: 'cry', url: ''},
            {name: '😭', code: 'sob', url: ''},
            {name: '😤', code: 'angry', url: ''},
            {name: '😡', code: 'rage', url: ''},
            {name: '🤬', code: 'cursing', url: ''},
            {name: '👍', code: 'thumbsup', url: ''},
            {name: '👎', code: 'thumbsdown', url: ''},
            {name: '👏', code: 'clap', url: ''},
            {name: '🙌', code: 'raised_hands', url: ''},
            {name: '🤝', code: 'handshake', url: ''},
            {name: '❤️', code: 'heart', url: ''},
            {name: '🧡', code: 'orange_heart', url: ''},
            {name: '💛', code: 'yellow_heart', url: ''},
            {name: '💚', code: 'green_heart', url: ''},
            {name: '💙', code: 'blue_heart', url: ''},
            {name: '💜', code: 'purple_heart', url: ''},
            {name: '🖤', code: 'black_heart', url: ''},
            {name: '🤍', code: 'white_heart', url: ''},
            {name: '💔', code: 'broken_heart', url: ''},
            {name: '💕', code: 'two_hearts', url: ''},
            {name: '💖', code: 'sparkling_heart', url: ''},
            {name: '💗', code: 'heartpulse', url: ''},
            {name: '💘', code: 'cupid', url: ''},
            {name: '💝', code: 'gift_heart', url: ''}
        ]
    };
    
    var localEmojiMap = <?php echo json_encode(Helper::getEmojiList()); ?>;
    var emojiBaseUrl = '<?php echo Helper::asset("emoji/"); ?>';
    
    var defaultEmojis = ['微笑', '嘻嘻', '哈哈', '可爱', '可怜', '挤眼', '害羞', '闭嘴', '鄙视', '爱你', '泪', '偷笑', '亲亲', '生病', '太开心', '白眼', '右哼哼', '左哼哼', '嘘', '衰', '吐', '哈欠', '抱抱', '怒', '疑问', '馋嘴', '拜拜', '思考', '汗', '打脸', '惊恐', '失望', '酷', '厉害', '弱', '握手', '胜利', '抱拳', '鼓励', '抠鼻'];
    var hotEmojis = ['doge', '二哈', '喵喵', '笑cry', '并不简单', '摊手', '跪了', '费解', '憧憬', '悲伤', '允悲', '赞', 'no', 'ok', 'haha', '哼', '666', 'awsl', '吃瓜', '打call'];
    
    defaultEmojis.forEach(function(name) {
        var code = '[' + name + ']';
        if (localEmojiMap[code]) {
            emojiData.default.push({
                name: name,
                code: code,
                url: emojiBaseUrl + localEmojiMap[code]
            });
        }
    });
    
    hotEmojis.forEach(function(name) {
        var code = '[' + name + ']';
        if (localEmojiMap[code]) {
            emojiData.hot.push({
                name: name,
                code: code,
                url: emojiBaseUrl + localEmojiMap[code]
            });
        }
    });
    
    var emojiPanel = document.getElementById('emojiPanel');
    var emojiBtn = document.getElementById('emojiBtn');
    var emojiContent = document.getElementById('emojiContent');
    var currentCategory = 'default';
    
    function renderEmojiPanel(category) {
        currentCategory = category;
        var emojis = emojiData[category] || [];
        var html = '';
        
        emojis.forEach(function(emoji) {
            if (category === 'emoji') {
                html += '<span class="emoji-item emoji-text" data-code="' + emoji.name + '" title="' + emoji.name + '">' + emoji.name + '</span>';
            } else {
                html += '<img class="emoji-item emoji-img" src="' + emoji.url + '" data-code="[' + emoji.name + ']" title="' + emoji.name + '" alt="' + emoji.name + '">';
            }
        });
        
        emojiContent.innerHTML = html;
    }
    
    emojiBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (emojiPanel.style.display === 'none') {
            emojiPanel.style.display = 'block';
            renderEmojiPanel(currentCategory);
        } else {
            emojiPanel.style.display = 'none';
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!emojiPanel.contains(e.target) && e.target !== emojiBtn) {
            emojiPanel.style.display = 'none';
        }
    });
    
    document.querySelectorAll('.emoji-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.emoji-tab').forEach(function(t) {
                t.classList.remove('active');
            });
            this.classList.add('active');
            renderEmojiPanel(this.dataset.category);
        });
    });
    
    emojiContent.addEventListener('click', function(e) {
        var target = e.target;
        if (target.classList.contains('emoji-item')) {
            var textarea = document.getElementById('publishContent');
            var code = target.dataset.code;
            var start = textarea.selectionStart;
            var end = textarea.selectionEnd;
            var value = textarea.value;
            
            textarea.value = value.substring(0, start) + code + value.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + code.length;
            textarea.focus();
            
            var event = new Event('input', { bubbles: true });
            textarea.dispatchEvent(event);
        }
    });
    
    renderEmojiPanel('default');
})();
</script>
<?php endif; ?>

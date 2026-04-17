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
    $publishPlaceholder = '有什么新鲜事想分享给大家？';
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
            <button type="submit" class="btn btn-primary">发布</button>
        </div>
        <div id="previewImages" class="preview-images"></div>
        <div id="videoList" class="video-list" style="display:none;"></div>
        <div id="attachmentList" class="attachment-list" style="display:none;"></div>
        
        <div id="emojiPanel" class="emoji-panel" style="display:none;">
            <div class="emoji-panel-header">
                <span class="emoji-tab active" data-category="default">默认</span>
                <span class="emoji-tab" data-category="hot">热门</span>
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
        default: [
            {name: '微笑', code: 'smile', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/e3/2018new_smile_org.png'},
            {name: '嘻嘻', code: 'tooth', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/e3/2018new_tooth_org.png'},
            {name: '哈哈', code: 'laugh', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/19/2018new_laugh_org.png'},
            {name: '可爱', code: 'lovely', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/9c/2018new_lovely_org.png'},
            {name: '可怜', code: 'pity', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/a1/2018new_pity_org.png'},
            {name: '挤眼', code: 'wink', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/43/2018new_wink_org.png'},
            {name: '害羞', code: 'shy', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/c5/2018new_shy_org.png'},
            {name: '闭嘴', code: 'shut', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/f6/2018new_shut_org.png'},
            {name: '鄙视', code: 'despise', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/96/2018new_despise_org.png'},
            {name: '爱你', code: 'love', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/f6/2018new_love_org.png'},
            {name: '泪', code: 'cry', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/6e/2018new_cry_org.png'},
            {name: '偷笑', code: 'titter', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/11/2018new_titter_org.png'},
            {name: '亲亲', code: 'kiss', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/f2/2018new_kiss_org.png'},
            {name: '生病', code: 'sick', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/1e/2018new_sick_org.png'},
            {name: '太开心', code: 'happy', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/e7/2018new_happy_org.png'},
            {name: '白眼', code: 'roll', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/47/2018new_roll_org.png'},
            {name: '右哼哼', code: 'hum', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/1c/2018new_hum_org.png'},
            {name: '左哼哼', code: 'humn', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/0c/2018new_humn_org.png'},
            {name: '嘘', code: 'shui', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/14/2018new_shui_org.png'},
            {name: '衰', code: 'sad', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/a3/2018new_sad_org.png'},
            {name: '吐', code: 'vomit', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/08/2018new_vomit_org.png'},
            {name: '哈欠', code: 'yawn', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/7c/2018new_yawn_org.png'},
            {name: '抱抱', code: 'hug', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/1a/2018new_hug_org.png'},
            {name: '怒', code: 'angry', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/77/2018new_angry_org.png'},
            {name: '疑问', code: 'doubt', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/c8/2018new_doubt_org.png'},
            {name: '馋嘴', code: 'greed', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/c3/2018new_greed_org.png'},
            {name: '拜拜', code: 'bye', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/f7/2018new_bye_org.png'},
            {name: '思考', code: 'think', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/30/2018new_think_org.png'},
            {name: '汗', code: 'sweat', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/e4/2018new_sweat_org.png'},
            {name: '打脸', code: 'slap', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/30/2018new_slap_org.png'},
            {name: '惊恐', code: 'scare', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/e0/2018new_scare_org.png'},
            {name: '失望', code: 'disap', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/18/2018new_disap_org.png'},
            {name: '酷', code: 'cool', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/36/2018new_cool_org.png'},
            {name: '厉害', code: 'good', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/40/2018new_good_org.png'},
            {name: '弱', code: 'weak', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/8b/2018new_weak_org.png'},
            {name: '握手', code: 'shake', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/89/2018new_shake_org.png'},
            {name: '胜利', code: 'victory', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/8e/2018new_victory_org.png'},
            {name: '抱拳', code: 'salute', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/aa/2018new_salute_org.png'},
            {name: '鼓励', code: 'encour', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/5a/2018new_encour_org.png'},
            {name: '抠鼻', code: 'nose', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/9a/2018new_nose_org.png'}
        ],
        hot: [
            {name: 'doge', code: 'doge', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/a1/2018new_doge_org.png'},
            {name: '二哈', code: 'erha', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/22/2018new_erha_org.png'},
            {name: '喵喵', code: 'cat', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/a8/2018new_cat_org.png'},
            {name: '笑cry', code: 'xiaoku', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/34/2018new_xiaoku_org.png'},
            {name: '并不简单', code: 'bsj', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/28/2018new_bsj_org.png'},
            {name: '摊手', code: 'tanshou', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/62/2018new_tanshou_org.png'},
            {name: '跪了', code: 'gui', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/75/2018new_gui_org.png'},
            {name: '费解', code: 'feijie', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/1e/2018new_feijie_org.png'},
            {name: '憧憬', code: 'chongjing', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/ce/2018new_chongjing_org.png'},
            {name: '失望', code: 'shiwang', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/ee/2018new_shiwang_org.png'},
            {name: '悲伤', code: 'beishang', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/35/2018new_beishang_org.png'},
            {name: '笑哭', code: 'xiaocry', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/2c/2018new_xiaocry_org.png'},
            {name: '怒骂', code: 'numa', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/df/2018new_numa_org.png'},
            {name: '允悲', code: 'yunbei', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/8c/2018new_yunbei_org.png'},
            {name: '赞', code: 'zan', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/e6/2018new_zan_org.png'},
            {name: 'no', code: 'no', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/3a/2018new_no_org.png'},
            {name: 'ok', code: 'ok', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/b6/2018new_ok_org.png'},
            {name: 'haha', code: 'haha', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/9a/2018new_haha_org.png'},
            {name: 'what', code: 'what', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/f5/2018new_what_org.png'},
            {name: '哼', code: 'heng', url: 'https://face.t.sinajs.cn/t4/appstyle/expression/ext/normal/31/2018new_heng_org.png'}
        ],
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

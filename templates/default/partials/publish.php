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
})();
</script>
<?php endif; ?>

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
<div class="page-settings">
    <h2>个人设置</h2>
    
    <?php if ($this->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $this->flash('success'); ?></div>
    <?php endif; ?>
    
    <?php if ($this->hasFlash('error')): ?>
    <div class="alert alert-error"><?php echo $this->flash('error'); ?></div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data">
        <?php echo $this->csrf(); ?>
        
        <div class="form-group avatar-upload">
            <label>头像</label>
            <?php echo $this->avatar($user['avatar'] ?? null, $user['username'], 'large'); ?>
            <input type="file" name="avatar" accept="image/*" id="avatarInput" data-max-size="<?php echo Setting::getMaxAvatarSize(); ?>">
            <small>建议上传正方形图片。图片大于300x300像素将自动压缩，最大<?php echo Setting::getMaxAvatarSize(); ?>MB</small>
            <div id="avatarPreview" style="margin-top:10px;display:none;">
                <img id="avatarPreviewImg" src="" alt="预览" style="max-width:150px;max-height:150px;border-radius:50%;">
                <span id="avatarCompressTip" style="color:#666;margin-left:10px;"></span>
            </div>
        </div>
        
        <div class="form-group">
            <label>用户名</label>
            <input type="text" name="username" value="<?php echo $this->escape($user['username']); ?>" maxlength="<?php echo Setting::getUsernameMaxLength(); ?>" minlength="<?php echo Setting::getUsernameMinLength(); ?>">
            <small>用户名将用于@提及和登录，长度<?php echo Setting::getUsernameMinLength(); ?>-<?php echo Setting::getUsernameMaxLength(); ?>个字符</small>
        </div>
        
        <div class="form-group">
            <label>个人简介</label>
            <textarea name="bio" rows="3"><?php echo $this->escape($user['bio']); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">保存</button>
    </form>
    
    <div class="settings-section">
        <h3>安全设置</h3>
        <a href="<?php echo $this->url('user/password'); ?>" class="btn">修改密码</a>
    </div>
</div>

<script>
(function() {
    var avatarInput = document.getElementById('avatarInput');
    var avatarPreview = document.getElementById('avatarPreview');
    var avatarPreviewImg = document.getElementById('avatarPreviewImg');
    var avatarCompressTip = document.getElementById('avatarCompressTip');
    var maxSize = parseInt(avatarInput.dataset.maxSize) * 1024 * 1024;
    var maxDimension = 300;
    var compressedBlob = null;
    
    avatarInput.addEventListener('change', function(e) {
        var file = this.files[0];
        if (!file) return;
        
        if (file.size > maxSize) {
            alert('图片大小超过限制（最大' + avatarInput.dataset.maxSize + 'MB）');
            this.value = '';
            avatarPreview.style.display = 'none';
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(event) {
            var img = new Image();
            img.onload = function() {
                var width = img.width;
                var height = img.height;
                var needsCompress = width > maxDimension || height > maxDimension;
                
                avatarPreviewImg.src = event.target.result;
                avatarPreview.style.display = 'block';
                
                if (needsCompress) {
                    var canvas = document.createElement('canvas');
                    var ctx = canvas.getContext('2d');
                    
                    var newWidth, newHeight;
                    if (width > height) {
                        newWidth = maxDimension;
                        newHeight = Math.round(height * (maxDimension / width));
                    } else {
                        newHeight = maxDimension;
                        newWidth = Math.round(width * (maxDimension / height));
                    }
                    
                    canvas.width = newWidth;
                    canvas.height = newHeight;
                    ctx.drawImage(img, 0, 0, newWidth, newHeight);
                    
                    canvas.toBlob(function(blob) {
                        compressedBlob = blob;
                        avatarCompressTip.textContent = '已自动压缩至 ' + newWidth + 'x' + newHeight + ' 像素';
                        avatarCompressTip.style.color = '#16a34a';
                        
                        var dataTransfer = new DataTransfer();
                        var compressedFile = new File([blob], file.name, { type: 'image/jpeg' });
                        dataTransfer.items.add(compressedFile);
                        avatarInput.files = dataTransfer.files;
                    }, 'image/jpeg', 0.9);
                } else {
                    compressedBlob = null;
                    avatarCompressTip.textContent = '图片尺寸 ' + width + 'x' + height + '，无需压缩';
                    avatarCompressTip.style.color = '#666';
                }
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });
})();
</script>

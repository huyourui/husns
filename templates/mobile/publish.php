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
<div class="m-publish-page">
    <form id="publishForm">
        <?php echo $this->csrf(); ?>
        <textarea name="content" class="m-publish-textarea" placeholder="有什么新鲜事想分享给大家？" maxlength="<?php echo Setting::getMaxPostLength(); ?>"></textarea>
        
        <div class="m-publish-images" id="imageContainer">
            <label class="m-publish-add-image">
                <span class="m-publish-add-icon">📷</span>
                <span class="m-publish-add-text">添加图片</span>
                <input type="file" id="imageInput" name="images[]" accept="image/*" multiple style="display:none" onchange="previewImages(this)">
            </label>
        </div>
        
        <div class="m-publish-toolbar">
            <div class="m-publish-tools">
                <label class="m-publish-tool" style="cursor:pointer;">
                    📷
                    <input type="file" accept="image/*" multiple style="display:none" onchange="previewImages(this)">
                </label>
            </div>
            <button type="submit" class="m-btn m-btn-primary m-publish-submit">发布</button>
        </div>
    </form>
</div>

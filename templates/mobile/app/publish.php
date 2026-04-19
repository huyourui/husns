<?php
$currentPage = 'publish';
$pageType = 'publish';
$bodyClass = 'publish-page';
$hideTabBar = true;
$showBack = true;
$headerTitle = '发布微博';
$this->setLayout('app');
?>
<form id="publishForm" class="publish-form">
    <textarea name="content" id="publishContent" placeholder="分享你的想法..." maxlength="500"></textarea>
    <div class="publish-images image-list" id="imageList"></div>
    <div class="publish-footer">
        <div class="publish-tools">
            <label class="add-image-btn" for="imageInput">+</label>
            <input type="file" id="imageInput" accept="image/*" multiple style="display:none;">
        </div>
        <div class="publish-info">
            <span class="char-count"><span id="charCount">0</span>/500</span>
            <button type="submit" class="submit-btn">发布</button>
        </div>
    </div>
</form>

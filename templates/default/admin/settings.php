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
<div class="admin-page">
    <h2>系统设置</h2>
    
    <?php if ($this->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $this->flash('success'); ?></div>
    <?php endif; ?>
    
    <div class="settings-tabs">
        <div class="tab-nav">
            <button type="button" class="tab-btn active" data-tab="basic">基本设置</button>
            <button type="button" class="tab-btn" data-tab="register">注册设置</button>
            <button type="button" class="tab-btn" data-tab="content">内容设置</button>
            <button type="button" class="tab-btn" data-tab="attachment">附件设置</button>
            <button type="button" class="tab-btn" data-tab="mail">邮件设置</button>
            <button type="button" class="tab-btn" data-tab="security">安全设置</button>
        </div>
        
        <form method="post" action="<?php echo $this->url('admin/settings'); ?>">
            <?php echo $this->csrf(); ?>
            
            <div class="tab-content active" id="tab-basic">
                <div class="form-group">
                    <label>网站标题</label>
                    <input type="text" name="site_name" value="<?php echo $this->escape($settings['site_name'] ?? 'HuSNS'); ?>" placeholder="HuSNS">
                    <small>显示在浏览器标签页和页面顶部</small>
                </div>
                
                <div class="form-group">
                    <label>网站副标题</label>
                    <input type="text" name="site_subtitle" value="<?php echo $this->escape($settings['site_subtitle'] ?? '一款免费开源的社交平台'); ?>" placeholder="一个简洁的社交平台">
                    <small>显示在标题下方，简短描述网站定位</small>
                </div>
                
                <div class="form-group">
                    <label>SEO关键词</label>
                    <input type="text" name="site_keywords" value="<?php echo $this->escape($settings['site_keywords'] ?? ''); ?>" placeholder="社交,微博,社区,分享">
                    <small>多个关键词用英文逗号分隔，用于搜索引擎优化</small>
                </div>
                
                <div class="form-group">
                    <label>SEO描述</label>
                    <textarea name="site_description" rows="3" placeholder="这是一个简洁的社交平台，用户可以分享动态、图片和互动交流。"><?php echo $this->escape($settings['site_description'] ?? ''); ?></textarea>
                    <small>网站简介，显示在搜索引擎结果中，建议80-200字</small>
                </div>
                
                <div class="form-group">
                    <label>每页显示动态数</label>
                    <input type="number" name="posts_per_page" value="<?php echo $settings['posts_per_page'] ?? 20; ?>" min="5" max="50" placeholder="20">
                    <small>首页和话题页每页显示的动态数量，范围5-50</small>
                </div>
                
                <div class="form-group">
                    <label>默认显示全站信息用户数阈值</label>
                    <input type="number" name="default_all_posts_threshold" value="<?php echo $settings['default_all_posts_threshold'] ?? 100; ?>" min="0" max="10000" placeholder="100">
                    <small>当用户总数小于此值时，首页默认显示全站信息tab；大于等于此值时默认显示我的关注tab。设为0则始终默认显示我的关注</small>
                </div>
                
                <div class="form-group">
                    <label>积分名称</label>
                    <input type="text" name="point_name" value="<?php echo $this->escape($settings['point_name'] ?? '积分'); ?>" placeholder="积分">
                    <small>自定义积分的显示名称，如：金币、积分、贡献值等</small>
                </div>
                
                <div class="form-group">
                    <label>备案号</label>
                    <input type="text" name="icp_number" value="<?php echo $this->escape($settings['icp_number'] ?? ''); ?>" placeholder="京ICP备XXXXXXXX号">
                    <small>网站ICP备案号，留空则不显示</small>
                </div>
                
                <div class="form-group">
                    <label>备案查询链接</label>
                    <input type="url" name="icp_url" value="<?php echo $this->escape($settings['icp_url'] ?? 'https://beian.miit.gov.cn/'); ?>" placeholder="https://beian.miit.gov.cn/">
                    <small>点击备案号跳转的链接地址，默认为工信部备案查询网站</small>
                </div>
            </div>
            
            <div class="tab-content" id="tab-register">
                <div class="form-group">
                    <label>允许游客访问</label>
                    <select name="guest_access_allowed">
                        <option value="1" <?php echo ($settings['guest_access_allowed'] ?? '0') === '1' ? 'selected' : ''; ?>>允许</option>
                        <option value="0" <?php echo ($settings['guest_access_allowed'] ?? '0') === '0' ? 'selected' : ''; ?>>禁止</option>
                    </select>
                    <small>关闭后游客无法查看微博内容，首页将显示登录界面</small>
                </div>
                
                <div class="form-group">
                    <label>开放注册</label>
                    <select name="registration_open">
                        <option value="1" <?php echo ($settings['registration_open'] ?? '1') === '1' ? 'selected' : ''; ?>>开放</option>
                        <option value="0" <?php echo ($settings['registration_open'] ?? '1') === '0' ? 'selected' : ''; ?>>关闭</option>
                    </select>
                    <small>关闭后用户将无法注册新账号</small>
                </div>
                
                <div class="form-group">
                    <label>注册邮箱验证</label>
                    <select name="registration_email_verify">
                        <option value="0" <?php echo ($settings['registration_email_verify'] ?? '0') === '0' ? 'selected' : ''; ?>>关闭</option>
                        <option value="1" <?php echo ($settings['registration_email_verify'] ?? '0') === '1' ? 'selected' : ''; ?>>开启</option>
                    </select>
                    <small>开启后用户注册时需要验证邮箱验证码，需先配置邮件服务</small>
                </div>
                
                <div class="form-group">
                    <label>注册需要邀请码</label>
                    <select name="require_invite_code">
                        <option value="0" <?php echo ($settings['require_invite_code'] ?? '0') === '0' ? 'selected' : ''; ?>>关闭</option>
                        <option value="1" <?php echo ($settings['require_invite_code'] ?? '0') === '1' ? 'selected' : ''; ?>>开启</option>
                    </select>
                    <small>开启后用户注册时需要填写有效的邀请码，邀请码可在"邀请码管理"中生成</small>
                </div>
                
                <div class="form-group">
                    <label></label>允许注册的邮箱后缀</label>
                    <input type="text" name="allowed_email_suffixes" value="<?php echo $this->escape($settings['allowed_email_suffixes'] ?? ''); ?>" placeholder="qq.com, 163.com, gmail.com">
                    <small>留空表示不限制，多个后缀用英文逗号分隔</small>
                </div>
                
                <div class="form-group">
                    <label>禁用用户名</label>
                    <input type="text" name="banned_usernames" value="<?php echo $this->escape($settings['banned_usernames'] ?? ''); ?>" placeholder="admin,root,system">
                    <small>禁止注册的用户名关键词，多个用英文逗号分隔，包含这些词的用户名都不能注册（不区分大小写）</small>
                </div>
                
                <div class="form-group">
                    <label>用户名最短字符数</label>
                    <input type="number" name="username_min_length" value="<?php echo $settings['username_min_length'] ?? 2; ?>" min="1" max="20" placeholder="2">
                    <small>用户名最少需要的字符数，范围1-20</small>
                </div>
                
                <div class="form-group">
                    <label>用户名最长字符数</label>
                    <input type="number" name="username_max_length" value="<?php echo $settings['username_max_length'] ?? 20; ?>" min="1" max="50" placeholder="20">
                    <small>用户名最多允许的字符数，范围1-50，需大于等于最短字符数</small>
                </div>
            </div>
            
            <div class="tab-content" id="tab-content">
                <div class="form-group">
                    <label>微博最大字数</label>
                    <input type="number" name="max_post_length" value="<?php echo $settings['max_post_length'] ?? 500; ?>" min="50" max="5000" placeholder="500">
                    <small>用户发布微博允许的最大字数，范围50-5000</small>
                </div>
                
                <div class="form-group">
                    <label>发布框默认提示内容</label>
                    <input type="text" name="publish_placeholder" value="<?php echo $this->escape($settings['publish_placeholder'] ?? ''); ?>" placeholder="分享你此刻的想法...">
                    <small>发布框中显示的默认提示文字，留空则不显示</small>
                </div>
                
                <div class="form-group">
                    <label>评论最大字数</label>
                    <input type="number" name="max_comment_length" value="<?php echo $settings['max_comment_length'] ?? 500; ?>" min="50" max="2000" placeholder="500">
                    <small>用户发布评论允许的最大字数，范围50-2000</small>
                </div>
                
                <div class="form-group">
                    <label>隐藏标签权限</label>
                    <select name="hide_tag_admin_only">
                        <option value="0" <?php echo ($settings['hide_tag_admin_only'] ?? '0') === '0' ? 'selected' : ''; ?>>所有用户可用</option>
                        <option value="1" <?php echo ($settings['hide_tag_admin_only'] ?? '0') === '1' ? 'selected' : ''; ?>>仅管理员可用</option>
                    </select>
                    <small>设置[hide]隐藏内容标签的使用权限。仅管理员可用时，普通用户发布的微博中的[hide]标签将不被解析，内容直接显示</small>
                </div>
                
                <div class="form-group">
                    <label>显示热门话题</label>
                    <select name="hot_topics_enabled">
                        <option value="1" <?php echo ($settings['hot_topics_enabled'] ?? '1') === '1' ? 'selected' : ''; ?>>开启</option>
                        <option value="0" <?php echo ($settings['hot_topics_enabled'] ?? '1') === '0' ? 'selected' : ''; ?>>关闭</option>
                    </select>
                    <small>是否在首页显示热门话题模块。关闭后首页将不显示热门话题</small>
                </div>
            </div>
            
            <div class="tab-content" id="tab-attachment">
                <div class="form-group">
                    <label>头像最大大小（MB）</label>
                    <input type="number" name="max_avatar_size" value="<?php echo $settings['max_avatar_size'] ?? 5; ?>" min="1" max="20" placeholder="5">
                    <small>用户上传头像的最大文件大小，范围1-20MB。建议用户上传正方形图片，大于300x300像素的图片会自动压缩</small>
                </div>
                
                <div class="form-group">
                    <label>最大图片个数</label>
                    <input type="number" name="max_image_count" value="<?php echo $settings['max_image_count'] ?? 9; ?>" min="1" max="18" placeholder="9">
                    <small>每条动态最多上传的图片数量，范围1-18</small>
                </div>
                
                <div class="form-group">
                    <label>单个图片最大大小（MB）</label>
                    <input type="number" name="max_image_size" value="<?php echo $settings['max_image_size'] ?? 5; ?>" min="1" max="20" placeholder="5">
                    <small>单个图片文件的最大大小，范围1-20MB</small>
                </div>
                
                <div class="form-group">
                    <label>最大附件个数</label>
                    <input type="number" name="max_attachment_count" value="<?php echo $settings['max_attachment_count'] ?? 5; ?>" min="1" max="10" placeholder="5">
                    <small>每条动态最多上传的附件数量，范围1-10</small>
                </div>
                
                <div class="form-group">
                    <label>单个附件最大大小（MB）</label>
                    <input type="number" name="max_attachment_size" value="<?php echo $settings['max_attachment_size'] ?? 10; ?>" min="1" max="100" placeholder="10">
                    <small>单个附件文件的最大大小，范围1-100MB</small>
                </div>
                
                <div class="form-group">
                    <label>允许的附件后缀名</label>
                    <input type="text" name="allowed_attachment_extensions" value="<?php echo $this->escape($settings['allowed_attachment_extensions'] ?? 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar'); ?>" placeholder="pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar">
                    <small>多个后缀用英文逗号分隔，留空使用默认值</small>
                </div>
                
                <div class="form-group">
                    <label>允许游客下载附件</label>
                    <select name="guest_download_allowed">
                        <option value="1" <?php echo ($settings['guest_download_allowed'] ?? '1') === '1' ? 'selected' : ''; ?>>允许</option>
                        <option value="0" <?php echo ($settings['guest_download_allowed'] ?? '1') === '0' ? 'selected' : ''; ?>>禁止</option>
                    </select>
                    <small>是否允许未登录用户下载附件，关闭后需要登录才能下载</small>
                </div>
                
                <div class="form-group">
                    <label>@用户联想范围</label>
                    <select name="mention_suggest_scope">
                        <option value="all" <?php echo ($settings['mention_suggest_scope'] ?? 'all') === 'all' ? 'selected' : ''; ?>>全站用户</option>
                        <option value="following" <?php echo ($settings['mention_suggest_scope'] ?? 'all') === 'following' ? 'selected' : ''; ?>>仅关注用户</option>
                    </select>
                    <small>发布时输入@符号联想的用户范围，选择"仅关注用户"则只显示已关注的用户</small>
                </div>
                
                <?php 
                $phpPostMaxSize = ini_get('post_max_size');
                $phpUploadMaxSize = ini_get('upload_max_filesize');
                $phpMaxSize = min(
                    (int)preg_replace('/[^0-9]/', '', $phpPostMaxSize),
                    (int)preg_replace('/[^0-9]/', '', $phpUploadMaxSize)
                );
                ?>
                <div class="form-group">
                    <label>单个视频最大大小（MB）</label>
                    <input type="number" name="max_video_size" value="<?php echo $settings['max_video_size'] ?? 100; ?>" min="1" max="500" placeholder="100">
                    <small>单个视频文件的最大大小，范围1-500MB。服务器限制：post_max_size=<?php echo $phpPostMaxSize; ?>，upload_max_filesize=<?php echo $phpUploadMaxSize; ?>，实际最大<?php echo $phpMaxSize; ?>MB</small>
                </div>
                
                <div class="form-group">
                    <label>最大视频个数</label>
                    <input type="number" name="max_video_count" value="<?php echo $settings['max_video_count'] ?? 1; ?>" min="1" max="5" placeholder="1">
                    <small>每条动态最多上传的视频数量，范围1-5个</small>
                </div>
            </div>
            
            <div class="tab-content" id="tab-security">
                <div class="form-group">
                    <label>操作间隔时间（秒）</label>
                    <input type="number" name="action_interval" value="<?php echo $settings['action_interval'] ?? 0; ?>" min="0" max="60" placeholder="0">
                    <small>用户连续操作的最小间隔时间，0表示不限制，范围0-60秒</small>
                </div>
            </div>
            
            <div class="tab-content" id="tab-mail">
                <div class="form-group">
                    <label>启用邮件服务</label>
                    <select name="mail_enabled">
                        <option value="0" <?php echo ($settings['mail_enabled'] ?? '0') === '0' ? 'selected' : ''; ?>>关闭</option>
                        <option value="1" <?php echo ($settings['mail_enabled'] ?? '0') === '1' ? 'selected' : ''; ?>>开启</option>
                    </select>
                    <small>开启后可发送邮件通知、验证码等</small>
                </div>
                
                <div class="form-group">
                    <label>SMTP服务器地址</label>
                    <input type="text" name="mail_host" value="<?php echo $this->escape($settings['mail_host'] ?? ''); ?>" placeholder="smtp.qq.com">
                    <small>如：smtp.qq.com、smtp.163.com、smtp.gmail.com</small>
                </div>
                
                <div class="form-group">
                    <label>SMTP端口</label>
                    <input type="number" name="mail_port" value="<?php echo $settings['mail_port'] ?? 465; ?>" min="1" max="65535" placeholder="465">
                    <small>常用端口：25（无加密）、465（SSL）、587（TLS）</small>
                </div>
                
                <div class="form-group">
                    <label>加密方式</label>
                    <select name="mail_encryption">
                        <option value="ssl" <?php echo ($settings['mail_encryption'] ?? 'ssl') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        <option value="tls" <?php echo ($settings['mail_encryption'] ?? 'ssl') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                        <option value="none" <?php echo ($settings['mail_encryption'] ?? 'ssl') === 'none' ? 'selected' : ''; ?>>无加密</option>
                    </select>
                    <small>建议使用SSL加密</small>
                </div>
                
                <div class="form-group">
                    <label>邮箱账号</label>
                    <input type="text" name="mail_username" value="<?php echo $this->escape($settings['mail_username'] ?? ''); ?>" placeholder="your@email.com">
                    <small>发件邮箱账号</small>
                </div>
                
                <div class="form-group">
                    <label>邮箱密码/授权码</label>
                    <input type="password" name="mail_password" value="<?php echo $this->escape($settings['mail_password'] ?? ''); ?>" placeholder="密码或授权码">
                    <small>部分邮箱需要使用授权码而非登录密码</small>
                </div>
                
                <div class="form-group">
                    <label>发件人名称</label>
                    <input type="text" name="mail_from_name" value="<?php echo $this->escape($settings['mail_from_name'] ?? ''); ?>" placeholder="<?php echo Setting::getSiteName(); ?>">
                    <small>收件人看到的发件人名称，留空使用网站名称</small>
                </div>
                
                <div class="form-group">
                    <label>发件人地址</label>
                    <input type="email" name="mail_from_address" value="<?php echo $this->escape($settings['mail_from_address'] ?? ''); ?>" placeholder="noreply@example.com">
                    <small>留空则使用邮箱账号作为发件地址</small>
                </div>
                
                <div class="form-group">
                    <label>发送测试邮件</label>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <input type="email" id="test_email" class="form-input" placeholder="请输入测试邮箱地址" style="flex:1;">
                        <button type="button" class="btn btn-default" onclick="testMail()">发送测试邮件</button>
                    </div>
                    <span id="mailTestResult" style="margin-top:8px;display:block;"></span>
                </div>
            </div>
            
            <div class="form-actions">
                <input type="hidden" name="current_tab" id="current_tab" value="<?php echo isset($_GET['tab']) ? $this->escape($_GET['tab']) : 'basic'; ?>">
                <button type="submit" class="btn btn-primary">保存设置</button>
            </div>
        </form>
    </div>
</div>

<style>
.settings-tabs {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.tab-nav {
    display: flex;
    border-bottom: 1px solid #eee;
    padding: 0 20px;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.tab-btn {
    padding: 15px 20px;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 14px;
    color: #666;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
}

.tab-btn:hover {
    color: #333;
}

.tab-btn.active {
    color: #667eea;
    border-bottom-color: #667eea;
    font-weight: 500;
}

.tab-content {
    display: none;
    padding: 20px;
}

.tab-content.active {
    display: block;
}

.form-actions {
    padding: 20px;
    border-top: 1px solid #eee;
    text-align: right;
}

body.dark-mode .settings-tabs {
    background: #1a1a2e;
}

body.dark-mode .tab-nav {
    background: #16213e;
    border-bottom-color: #333;
}

body.dark-mode .tab-btn {
    color: #aaa;
}

body.dark-mode .tab-btn:hover {
    color: #ddd;
}

body.dark-mode .tab-btn.active {
    color: #7c83fd;
    border-bottom-color: #7c83fd;
}

body.dark-mode .form-actions {
    border-top-color: #333;
}
</style>

<script>
document.querySelectorAll('.tab-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var tabId = this.dataset.tab;
        
        document.querySelectorAll('.tab-btn').forEach(function(b) {
            b.classList.remove('active');
        });
        document.querySelectorAll('.tab-content').forEach(function(c) {
            c.classList.remove('active');
        });
        
        this.classList.add('active');
        document.getElementById('tab-' + tabId).classList.add('active');
        
        document.getElementById('current_tab').value = tabId;
    });
});

(function() {
    var urlParams = new URLSearchParams(window.location.search);
    var tabParam = urlParams.get('tab');
    
    if (tabParam) {
        var targetBtn = document.querySelector('.tab-btn[data-tab="' + tabParam + '"]');
        if (targetBtn) {
            targetBtn.click();
        }
    }
})();
</script>

<script>
function testMail() {
    var testEmail = document.getElementById('test_email').value;
    if (!testEmail) {
        alert('请输入测试邮箱地址');
        return;
    }
    
    var resultSpan = document.getElementById('mailTestResult');
    resultSpan.textContent = '发送中...';
    resultSpan.style.color = '#666';
    
    var form = document.querySelector('.settings-tabs form');
    var formData = new FormData(form);
    formData.append('email', testEmail);
    
    fetch('<?php echo $this->url("admin/testMail"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.code === 0) {
            resultSpan.style.color = '#16a34a';
            resultSpan.textContent = data.message;
        } else {
            resultSpan.style.color = '#dc2626';
            resultSpan.textContent = data.message;
        }
    })
    .catch(function() {
        resultSpan.style.color = '#dc2626';
        resultSpan.textContent = '发送失败';
    });
}
</script>

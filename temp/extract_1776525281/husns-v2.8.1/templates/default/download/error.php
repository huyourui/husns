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
<div class="error-page">
    <div class="error-icon">
        <svg viewBox="0 0 24 24" width="80" height="80" fill="currentColor">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
        </svg>
    </div>
    <h2 class="error-title"><?php echo $this->escape($title); ?></h2>
    <p class="error-message"><?php echo $this->escape($message); ?></p>
    <div class="error-actions">
        <?php if ($redirectUrl): ?>
        <a href="<?php echo $redirectUrl; ?>" class="btn btn-primary">立即前往</a>
        <?php endif; ?>
        <a href="javascript:history.back();" class="btn">返回上一页</a>
        <a href="<?php echo $this->url(); ?>" class="btn">返回首页</a>
    </div>
</div>

<style>
.error-page {
    text-align: center;
    padding: 60px 20px;
    max-width: 500px;
    margin: 0 auto;
}

.error-icon {
    color: #e74c3c;
    margin-bottom: 20px;
}

.error-title {
    font-size: 24px;
    color: #333;
    margin-bottom: 15px;
}

.error-message {
    font-size: 16px;
    color: #666;
    margin-bottom: 30px;
    line-height: 1.6;
}

.error-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}

.error-actions .btn {
    padding: 10px 25px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s;
}

.error-actions .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.error-actions .btn-primary:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

.error-actions .btn:not(.btn-primary) {
    background: #f5f5f5;
    color: #666;
    border: 1px solid #ddd;
}

.error-actions .btn:not(.btn-primary):hover {
    background: #eee;
    color: #333;
}

body.dark-mode .error-icon {
    color: #e74c3c;
}

body.dark-mode .error-title {
    color: #e0e0e0;
}

body.dark-mode .error-message {
    color: #aaa;
}

body.dark-mode .error-actions .btn:not(.btn-primary) {
    background: #2d3748;
    color: #aaa;
    border-color: #444;
}

body.dark-mode .error-actions .btn:not(.btn-primary):hover {
    background: #3d4758;
    color: #e0e0e0;
}
</style>

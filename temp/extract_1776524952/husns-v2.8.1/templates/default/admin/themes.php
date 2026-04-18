<?php 
/**
 * 主题管理页面
 * 
 * @var array $themes 主题列表
 * @var string $currentTheme 当前主题
 */
?>
<div class="themes-page">
    <div class="page-header">
        <h2>主题管理</h2>
        <p class="page-desc">管理和切换网站主题模板，支持自定义主题开发</p>
    </div>
    
    <div class="themes-info">
        <div class="info-card">
            <div class="info-icon">🎨</div>
            <div class="info-content">
                <h4>当前主题</h4>
                <p id="currentThemeName"><?php echo htmlspecialchars($currentTheme); ?></p>
            </div>
        </div>
        <div class="info-card">
            <div class="info-icon">📦</div>
            <div class="info-content">
                <h4>已安装主题</h4>
                <p><?php echo count($themes); ?> 个</p>
            </div>
        </div>
        <div class="info-card">
            <div class="info-icon">📁</div>
            <div class="info-content">
                <h4>主题目录</h4>
                <p>/templates/</p>
            </div>
        </div>
    </div>
    
    <div class="themes-grid">
        <?php foreach ($themes as $theme): ?>
        <div class="theme-card <?php echo $theme['is_current'] ? 'active' : ''; ?>" data-theme="<?php echo htmlspecialchars($theme['id']); ?>">
            <div class="theme-preview">
                <?php if ($theme['screenshot']): ?>
                <img src="<?php echo htmlspecialchars($theme['screenshot']); ?>" alt="<?php echo htmlspecialchars($theme['name']); ?>">
                <?php else: ?>
                <div class="theme-preview-placeholder">
                    <span>🎨</span>
                    <p>无预览图</p>
                </div>
                <?php endif; ?>
                
                <?php if ($theme['is_current']): ?>
                <div class="theme-badge">当前使用</div>
                <?php endif; ?>
            </div>
            
            <div class="theme-info">
                <h3 class="theme-name"><?php echo htmlspecialchars($theme['name']); ?></h3>
                <p class="theme-id"><?php echo htmlspecialchars($theme['id']); ?></p>
                <p class="theme-description"><?php echo htmlspecialchars($theme['description'] ?: '暂无描述'); ?></p>
                
                <div class="theme-meta">
                    <span class="theme-version">v<?php echo htmlspecialchars($theme['version']); ?></span>
                    <span class="theme-author"><?php echo htmlspecialchars($theme['author']); ?></span>
                </div>
            </div>
            
            <div class="theme-actions">
                <?php if (!$theme['is_current']): ?>
                <button class="btn btn-primary btn-activate" onclick="activateTheme('<?php echo htmlspecialchars($theme['id']); ?>')">
                    启用主题
                </button>
                <?php else: ?>
                <button class="btn btn-disabled" disabled>
                    使用中
                </button>
                <?php endif; ?>
                
                <button class="btn btn-outline btn-detail" onclick="showThemeDetail('<?php echo htmlspecialchars($theme['id']); ?>')">
                    详情
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($themes)): ?>
    <div class="empty-state">
        <div class="empty-icon">🎨</div>
        <h3>暂无主题</h3>
        <p>请在 templates 目录下添加主题</p>
    </div>
    <?php endif; ?>
    
    <div class="theme-developer-section">
        <h3>开发自定义主题</h3>
        <div class="developer-guide">
            <div class="guide-step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h4>创建主题目录</h4>
                    <p>在 <code>templates/</code> 目录下创建新文件夹，如 <code>my-theme</code></p>
                </div>
            </div>
            <div class="guide-step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h4>创建配置文件</h4>
                    <p>在主题目录下创建 <code>theme.json</code> 配置文件</p>
                    <pre class="code-block">{
    "name": "我的主题",
    "version": "1.0.0",
    "author": "作者名称",
    "description": "主题描述",
    "homepage": "https://example.com",
    "requires": {
        "php": ">=7.4",
        "husns": ">=2.7.0"
    }
}</pre>
                </div>
            </div>
            <div class="guide-step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h4>创建模板文件</h4>
                    <p>参考 <code>templates/default</code> 目录结构创建模板文件</p>
                    <ul>
                        <li><code>layouts/main.php</code> - 主布局文件</li>
                        <li><code>layouts/admin.php</code> - 后台布局文件</li>
                        <li><code>post/index.php</code> - 首页模板</li>
                        <li>其他页面模板...</li>
                    </ul>
                </div>
            </div>
            <div class="guide-step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h4>添加预览图（可选）</h4>
                    <p>在主题目录下添加 <code>screenshot.png</code> 预览图（推荐尺寸 800x600）</p>
                </div>
            </div>
        </div>
        
        <div class="developer-tips">
            <h4>💡 开发提示</h4>
            <ul>
                <li>主题可以只包含需要自定义的模板文件，系统会自动回退到默认主题</li>
                <li>使用 <code>$this->themeAsset('css/style.css')</code> 引用主题资源</li>
                <li>使用 <code>$this->themeUrl()</code> 获取主题目录URL</li>
                <li>建议继承默认主题的CSS样式，保持一致性</li>
            </ul>
        </div>
    </div>
</div>

<!-- 主题详情弹窗 -->
<div id="themeDetailModal" class="modal" style="display:none;">
    <div class="modal-content theme-detail-content">
        <div class="modal-header">
            <h3 id="detailThemeName">主题详情</h3>
            <span class="modal-close" onclick="closeThemeDetail()">&times;</span>
        </div>
        <div class="modal-body" id="themeDetailBody">
            <div class="detail-loading">加载中...</div>
        </div>
    </div>
</div>

<style>
.themes-page {
    padding: 20px;
}

.page-header {
    margin-bottom: 24px;
}

.page-header h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
    color: #1f2937;
}

.page-desc {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.themes-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.info-card {
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.info-icon {
    font-size: 32px;
}

.info-content h4 {
    margin: 0 0 4px 0;
    font-size: 12px;
    color: #6b7280;
    font-weight: normal;
}

.info-content p {
    margin: 0;
    font-size: 16px;
    color: #1f2937;
    font-weight: 600;
}

.themes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.theme-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    border: 2px solid transparent;
}

.theme-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.theme-card.active {
    border-color: #3b82f6;
}

.theme-preview {
    position: relative;
    height: 180px;
    background: #f3f4f6;
    overflow: hidden;
}

.theme-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.theme-preview-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
}

.theme-preview-placeholder span {
    font-size: 48px;
}

.theme-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #3b82f6;
    color: #fff;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.theme-info {
    padding: 16px;
}

.theme-name {
    margin: 0 0 4px 0;
    font-size: 16px;
    color: #1f2937;
}

.theme-id {
    margin: 0 0 8px 0;
    font-size: 12px;
    color: #9ca3af;
    font-family: monospace;
}

.theme-description {
    margin: 0 0 12px 0;
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
    height: 40px;
    overflow: hidden;
}

.theme-meta {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: #9ca3af;
}

.theme-actions {
    padding: 12px 16px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 8px;
}

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn-primary {
    background: #3b82f6;
    color: #fff;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-outline {
    background: transparent;
    border: 1px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    background: #f3f4f6;
}

.btn-disabled {
    background: #e5e7eb;
    color: #9ca3af;
    cursor: not-allowed;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 12px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.empty-state h3 {
    margin: 0 0 8px 0;
    color: #374151;
}

.empty-state p {
    margin: 0;
    color: #6b7280;
}

.theme-developer-section {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    margin-top: 32px;
}

.theme-developer-section h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
    color: #1f2937;
}

.developer-guide {
    display: grid;
    gap: 20px;
}

.guide-step {
    display: flex;
    gap: 16px;
}

.step-number {
    width: 32px;
    height: 32px;
    background: #3b82f6;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
}

.step-content h4 {
    margin: 0 0 8px 0;
    font-size: 15px;
    color: #374151;
}

.step-content p {
    margin: 0 0 8px 0;
    color: #6b7280;
    font-size: 14px;
}

.step-content ul {
    margin: 8px 0 0 0;
    padding-left: 20px;
    color: #6b7280;
    font-size: 14px;
}

.step-content li {
    margin-bottom: 4px;
}

.code-block {
    background: #1f2937;
    color: #e5e7eb;
    padding: 12px 16px;
    border-radius: 6px;
    font-size: 12px;
    font-family: monospace;
    overflow-x: auto;
    margin-top: 8px;
}

.developer-tips {
    margin-top: 24px;
    padding: 16px;
    background: #eff6ff;
    border-radius: 8px;
    border-left: 4px solid #3b82f6;
}

.developer-tips h4 {
    margin: 0 0 12px 0;
    color: #1f2937;
}

.developer-tips ul {
    margin: 0;
    padding-left: 20px;
    color: #374151;
    font-size: 14px;
}

.developer-tips li {
    margin-bottom: 6px;
}

/* 主题详情弹窗 */
.theme-detail-content {
    max-width: 600px;
    width: 90%;
}

#themeDetailBody {
    max-height: 60vh;
    overflow-y: auto;
}

.detail-loading {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}

.detail-section {
    margin-bottom: 20px;
}

.detail-section h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.detail-info-item {
    background: #f9fafb;
    padding: 12px;
    border-radius: 6px;
}

.detail-info-item label {
    display: block;
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 4px;
}

.detail-info-item span {
    font-size: 14px;
    color: #1f2937;
    font-weight: 500;
}

.detail-screenshot {
    width: 100%;
    border-radius: 8px;
    margin-bottom: 16px;
}

@media (max-width: 768px) {
    .themes-grid {
        grid-template-columns: 1fr;
    }
    
    .info-card {
        padding: 12px;
    }
    
    .info-icon {
        font-size: 24px;
    }
    
    .guide-step {
        flex-direction: column;
        gap: 8px;
    }
    
    .step-number {
        width: 28px;
        height: 28px;
        font-size: 14px;
    }
}
</style>

<script>
var csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';

function activateTheme(themeId) {
    if (!confirm('确定要启用此主题吗？')) {
        return;
    }
    
    var formData = new FormData();
    formData.append('theme', themeId);
    formData.append('csrf_token', csrfToken);
    
    fetch('<?php echo $this->url("theme/activate"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.code === 0) {
            alert('主题切换成功');
            location.reload();
        } else {
            alert(data.message || '切换失败');
        }
    })
    .catch(error => {
        alert('网络错误，请稍后重试');
    });
}

function showThemeDetail(themeId) {
    var modal = document.getElementById('themeDetailModal');
    var body = document.getElementById('themeDetailBody');
    var nameEl = document.getElementById('detailThemeName');
    
    modal.style.display = 'flex';
    body.innerHTML = '<div class="detail-loading">加载中...</div>';
    
    fetch('<?php echo $this->url("theme/detail"); ?>?theme=' + encodeURIComponent(themeId))
    .then(response => response.json())
    .then(data => {
        if (data.code === 0) {
            var theme = data.data;
            nameEl.textContent = theme.name;
            
            var html = '';
            
            if (theme.screenshot) {
                html += '<img src="' + theme.screenshot + '" class="detail-screenshot" alt="' + theme.name + '">';
            }
            
            html += '<div class="detail-section">';
            html += '<h4>基本信息</h4>';
            html += '<div class="detail-info-grid">';
            html += '<div class="detail-info-item"><label>主题ID</label><span>' + theme.id + '</span></div>';
            html += '<div class="detail-info-item"><label>版本</label><span>v' + theme.version + '</span></div>';
            html += '<div class="detail-info-item"><label>作者</label><span>' + theme.author + '</span></div>';
            html += '<div class="detail-info-item"><label>状态</label><span>' + (theme.is_current ? '使用中' : '未启用') + '</span></div>';
            html += '</div></div>';
            
            if (theme.description) {
                html += '<div class="detail-section">';
                html += '<h4>描述</h4>';
                html += '<p style="margin:0;color:#374151;">' + theme.description + '</p>';
                html += '</div>';
            }
            
            if (theme.homepage) {
                html += '<div class="detail-section">';
                html += '<h4>主页</h4>';
                html += '<a href="' + theme.homepage + '" target="_blank" style="color:#3b82f6;">' + theme.homepage + '</a>';
                html += '</div>';
            }
            
            if (theme.requires && Object.keys(theme.requires).length > 0) {
                html += '<div class="detail-section">';
                html += '<h4>环境要求</h4>';
                html += '<div class="detail-info-grid">';
                for (var key in theme.requires) {
                    html += '<div class="detail-info-item"><label>' + key + '</label><span>' + theme.requires[key] + '</span></div>';
                }
                html += '</div></div>';
            }
            
            body.innerHTML = html;
        } else {
            body.innerHTML = '<div class="detail-loading">' + (data.message || '加载失败') + '</div>';
        }
    })
    .catch(error => {
        body.innerHTML = '<div class="detail-loading">网络错误</div>';
    });
}

function closeThemeDetail() {
    document.getElementById('themeDetailModal').style.display = 'none';
}

document.getElementById('themeDetailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeThemeDetail();
    }
});
</script>

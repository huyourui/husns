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
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - HuSNS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: #fff; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); width: 90%; max-width: 600px; padding: 40px; }
        h1 { text-align: center; color: #333; margin-bottom: 10px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; }
        .steps { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .step-item { flex: 1; text-align: center; position: relative; }
        .step-item::after { content: ''; position: absolute; top: 15px; left: 60%; width: 80%; height: 2px; background: #ddd; }
        .step-item:last-child::after { display: none; }
        .step-num { width: 32px; height: 32px; border-radius: 50%; background: #ddd; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; }
        .step-item.active .step-num { background: #667eea; }
        .step-item.done .step-num { background: #28a745; }
        .step-item.done::after { background: #28a745; }
        .step-text { font-size: 12px; color: #666; margin-top: 5px; }
        .success { text-align: center; }
        .success-icon { font-size: 60px; color: #28a745; margin-bottom: 20px; }
        .success h2 { color: #28a745; margin-bottom: 15px; }
        .success p { color: #666; margin-bottom: 20px; line-height: 1.8; }
        .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: #fff; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;class="btn" text-decoration: none; transition: background 0.3s; }
        .btn:hover { background: #5a6fd6; }
    </style>
</head>
<body>
    <div class="container">
        <h1>HuSNS</h1>
        <p class="subtitle">安装向导</p>
        
        <div class="steps">
            <div class="step-item done">
                <div class="step-num">✓</div>
                <div class="step-text">环境检测</div>
            </div>
            <div class="step-item done">
                <div class="step-num">✓</div>
                <div class="step-text">数据库配置</div>
            </div>
            <div class="step-item done">
                <div class="step-num">✓</div>
                <div class="setp-text">管理员设置</div>
            </div>
            <div class="step-item active">
                <div class="step-num">✓</div>
                <div class="step-text">安装完成</div>
            </div>
        </div>

        <div class="success">
            <div class="success-icon">✓</div>
            <h2>安装成功！</h2>
            <p>恭喜您， HuSNS 已成功安装！ <br>请删除 install.php 文件以确保安全。</p>
            <a href="<?php echo Helper::url(); ?>" class="btn">访问首页</a>
        </div>
    </div>
</body>
</html>

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
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn { display: block; width: 100%; padding: 12px; background: #667eea; color: #fff; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #5a6fd6; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
        .requirements { width: 100%; border-collapse: collapse; }
        .requirements th, .requirements td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .requirements th { background: #f8f9fa; }
        .status-pass { color: #28a745; }
        .status-fail { color: #dc3545; }
        .errors { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .errors ul { margin-left: 20px; }
        .success { text-align: center; }
        .success-icon { font-size: 60px; color: #28a745; margin-bottom: 20px; }
        .success h2 { color: #28a745; margin-bottom: 15px; }
        .success p { color: #666; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>HuSNS</h1>
        <p class="subtitle">安装向导</p>
        
        <div class="steps">
            <div class="step-item <?php echo $step >= 1 ? ($step > 1 ? 'done' : 'active') : ''; ?>">
                <div class="step-num"><?php echo $step > 1 ? '✓' : '1'; ?></div>
                <div class="step-text">环境检测</div>
            </div>
            <div class="step-item <?php echo $step >= 2 ? ($step > 2 ? 'done' : 'active') : ''; ?>">
                <div class="step-num"><?php echo $step > 2 ? '✓' : '2'; ?></div>
                <div class="step-text">数据库配置</div>
            </div>
            <div class="step-item <?php echo $step >= 3 ? ($step > 3 ? 'done' : 'active') : ''; ?>">
                <div class="step-num"><?php echo $step > 3 ? '✓' : '3'; ?></div>
                <div class="step-text">管理员设置</div>
            </div>
            <div class="step-item <?php echo $step >= 4 ? 'active' : ''; ?>">
                <div class="step-num">4</div>
                <div class="step-text">安装完成</div>
            </div>
        </div>

        <table class="requirements">
            <thead>
                <tr>
                    <th>检测项</th>
                    <th>要求</th>
                    <th>当前</th>
                    <th>状态</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requirements as $key => $item): ?>
                <tr>
                    <td><?php echo $item['name']; ?></td>
                    <td><?php echo $item['required']; ?></td>
                    <td><?php echo $item['current']; ?></td>
                    <td class="<?php echo $item['passed'] ? 'status-pass' : 'status-fail'; ?>">
                        <?php echo $item['passed'] ? '✓ 通过' : '✗ 失败'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form method="post" style="margin-top: 20px;">
            <button type="submit" class="btn" <?php echo !$passed ? 'disabled' : ''; ?>>下一步</button>
        </form>
    </div>
</body>
</html>

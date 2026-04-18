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
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        .btn { display: block; width: 100%; padding: 12px; background: #667eea; color: #fff; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #5a6fd6; }
        .errors { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .errors ul { margin-left: 20px; }
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
            <div class="step-item active">
                <div class="step-num">2</div>
                <div class="step-text">数据库配置</div>
            </div>
            <div class="step-item">
                <div class="step-num">3</div>
                <div class="step-text">管理员设置</div>
            </div>
            <div class="step-item">
                <div class="step-num">4</div>
                <div class="step-text">安装完成</div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label>数据库主机</label>
                    <input type="text" name="db_host" value="<?php echo isset($_POST['db_host']) ? htmlspecialchars($_POST['db_host']) : 'localhost'; ?>" required>
                </div>
                <div class="form-group">
                    <label>端口</label>
                    <input type="number" name="db_port" value="<?php echo isset($_POST['db_port']) ? htmlspecialchars($_POST['db_port']) : '3306'; ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>数据库名</label>
                <input type="text" name="db_name" value="<?php echo isset($_POST['db_name']) ? htmlspecialchars($_POST['db_name']) : 'husns'; ?>" required placeholder="请输入数据库名">
            </div>
            <div class="form-group">
                <label>数据库用户名</label>
                <input type="text" name="db_user" value="<?php echo isset($_POST['db_user']) ? htmlspecialchars($_POST['db_user']) : 'root'; ?>" required>
            </div>
            <div class="form-group">
                <label>数据库密码</label>
                <input type="password" name="db_pass" value="<?php echo isset($_POST['db_pass']) ? htmlspecialchars($_POST['db_pass']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>表前缀</label>
                <input type="text" name="db_prefix" value="<?php echo isset($_POST['db_prefix']) ? htmlspecialchars($_POST['db_prefix']) : 'husns_'; ?>" required>
            </div>
            <button type="submit" class="btn">下一步</button>
        </form>
    </div>
</body>
</html>

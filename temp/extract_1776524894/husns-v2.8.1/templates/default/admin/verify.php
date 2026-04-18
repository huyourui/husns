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
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员验证 - <?php echo $this->escape(Setting::getSiteName()); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .verify-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .verify-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        
        .verify-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .verify-header p {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .verify-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #334155;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #1e293b;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 41, 59, 0.3);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            color: #1e293b;
        }
        
        .lock-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-header">
            <div class="lock-icon">🔐</div>
            <h1>管理员验证</h1>
            <p>请输入您的密码以继续访问后台</p>
        </div>
        
        <div class="verify-body">
            <?php if ($this->hasFlash('error')): ?>
            <div class="alert alert-error"><?php echo $this->flash('error'); ?></div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo $this->url('admin/verify'); ?>">
                <?php echo $this->csrf(); ?>
                <div class="form-group">
                    <label for="password">登录密码</label>
                    <input type="password" id="password" name="password" placeholder="请输入您的密码" required autofocus>
                </div>
                <button type="submit" class="btn">验证并进入后台</button>
            </form>
            
            <a href="<?php echo $this->url(); ?>" class="back-link">← 返回首页</a>
        </div>
    </div>
</body>
</html>

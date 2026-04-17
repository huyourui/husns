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
    <title>账号已被封禁 - <?php echo $this->escape(Setting::getSiteName()); ?></title>
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
        
        .banned-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            text-align: center;
        }
        
        .banned-header {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #fff;
            padding: 40px 30px;
        }
        
        .banned-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
        }
        
        .banned-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .banned-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .banned-body {
            padding: 40px 30px;
        }
        
        .banned-info {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .banned-info-item {
            display: flex;
            margin-bottom: 12px;
        }
        
        .banned-info-item:last-child {
            margin-bottom: 0;
        }
        
        .banned-info-label {
            color: #64748b;
            font-size: 14px;
            width: 80px;
            flex-shrink: 0;
        }
        
        .banned-info-value {
            color: #1e293b;
            font-size: 14px;
            font-weight: 500;
        }
        
        .banned-reason {
            background: #fff5f5;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 30px;
        }
        
        .banned-reason-label {
            color: #dc2626;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .banned-reason-text {
            color: #1e293b;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .btn-logout {
            display: inline-block;
            padding: 14px 40px;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        
        .banned-footer {
            padding: 20px 30px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        
        .banned-footer p {
            color: #64748b;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="banned-container">
        <div class="banned-header">
            <div class="banned-icon">🚫</div>
            <h1>账号已被封禁</h1>
            <p>您的账号已被管理员封禁，无法继续使用</p>
        </div>
        
        <div class="banned-body">
            <div class="banned-info">
                <div class="banned-info-item">
                    <span class="banned-info-label">封禁类型</span>
                    <span class="banned-info-value">
                        <?php if ($banInfo['type'] === 2): ?>
                        永久封禁
                        <?php else: ?>
                        临时封禁
                        <?php endif; ?>
                    </span>
                </div>
                <?php if ($banInfo['type'] === 1 && $banInfo['until'] > 0): ?>
                <div class="banned-info-item">
                    <span class="banned-info-label">解封时间</span>
                    <span class="banned-info-value"><?php echo date('Y-m-d H:i', $banInfo['until']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($banInfo['reason'])): ?>
            <div class="banned-reason">
                <div class="banned-reason-label">封禁原因</div>
                <div class="banned-reason-text"><?php echo $this->escape($banInfo['reason']); ?></div>
            </div>
            <?php endif; ?>
            
            <a href="<?php echo $this->url('user/logout'); ?>" class="btn-logout">退出账号</a>
        </div>
        
        <div class="banned-footer">
            <p>如有疑问，请联系管理员</p>
        </div>
    </div>
</body>
</html>

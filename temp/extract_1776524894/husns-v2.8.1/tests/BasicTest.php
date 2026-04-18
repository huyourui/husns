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

require_once __DIR__ . '/TestRunner.php';

/**
 * Helper类测试
 */
class HelperTest extends TestCase
{
    /**
     * 测试URL生成
     */
    public function testUrl()
    {
        $url = Helper::url('user/profile');
        $this->assertNotEmpty($url, 'URL不应为空');
        $this->assertStringContains('user/profile', $url, 'URL应包含路径');
    }

    /**
     * 测试时间格式化
     */
    public function testFormatTime()
    {
        $time = time();
        $result = Helper::formatTime($time);
        $this->assertNotEmpty($result, '时间格式化结果不应为空');
        
        $past = time() - 60;
        $result = Helper::formatTime($past);
        $this->assertStringContains('分钟', $result, '一分钟前应包含"分钟"');
    }

    /**
     * 测试字符串截断
     */
    public function testTruncate()
    {
        $longString = '这是一段很长的测试字符串，用于测试截断功能是否正常工作';
        $result = Helper::truncate($longString, 10);
        
        $this->assertTrue(mb_strlen($result, 'UTF-8') <= 13, '截断后长度应小于等于限制长度加后缀');
        $this->assertStringContains('...', $result, '截断后应包含省略号');
    }

    /**
     * 测试Token生成
     */
    public function testGenerateToken()
    {
        $token1 = Helper::generateToken();
        $token2 = Helper::generateToken();
        
        $this->assertNotEmpty($token1, 'Token不应为空');
        $this->assertTrue(strlen($token1) === 64, 'Token长度应为64');
        $this->assertFalse($token1 === $token2, '两次生成的Token应该不同');
    }

    /**
     * 测试IP获取
     */
    public function testGetIp()
    {
        $ip = Helper::getIp();
        $this->assertNotEmpty($ip, 'IP不应为空');
        
        $this->assertTrue(
            filter_var($ip, FILTER_VALIDATE_IP) !== false,
            '返回的应该是有效的IP地址'
        );
    }

    /**
     * 测试数字格式化
     */
    public function testFormatNumber()
    {
        $this->assertEquals('100', Helper::formatNumber(100));
        $this->assertEquals('1k', Helper::formatNumber(1000));
        $this->assertEquals('1w', Helper::formatNumber(10000));
        $this->assertEquals('1.5k', Helper::formatNumber(1500));
    }
}

/**
 * Security类测试
 */
class SecurityTest extends TestCase
{
    /**
     * 测试XSS清理
     */
    public function testXssClean()
    {
        $input = '<script>alert("xss")</script>';
        $result = Security::xssClean($input);
        
        $this->assertFalse(strpos($result, '<script>') !== false, '应移除script标签');
    }

    /**
     * 测试HTML转义
     */
    public function testEscape()
    {
        $input = '<div class="test">测试&符号</div>';
        $result = Security::escape($input);
        
        $this->assertStringContains('&lt;', $result, '应转义小于号');
        $this->assertStringContains('&gt;', $result, '应转义大于号');
        $this->assertStringContains('&quot;', $result, '应转义引号');
    }

    /**
     * 测试邮箱验证
     */
    public function testValidateEmail()
    {
        $this->assertTrue(Security::validateEmail('test@example.com'));
        $this->assertTrue(Security::validateEmail('user.name@domain.co.uk'));
        $this->assertFalse(Security::validateEmail('invalid-email'));
        $this->assertFalse(Security::validateEmail('test@'));
    }

    /**
     * 测试用户名验证
     */
    public function testValidateUsername()
    {
        $this->assertTrue(Security::validateUsername('testuser'));
        $this->assertTrue(Security::validateUsername('test_user'));
        $this->assertTrue(Security::validateUsername('测试用户'));
        $this->assertFalse(Security::validateUsername('ab'));
        $this->assertFalse(Security::validateUsername('test@user'));
    }

    /**
     * 测试密码哈希和验证
     */
    public function testPasswordHash()
    {
        $password = 'test123456';
        $hash = Security::hashPassword($password);
        
        $this->assertNotEmpty($hash, '密码哈希不应为空');
        $this->assertTrue(Security::verifyPassword($password, $hash), '密码验证应成功');
        $this->assertFalse(Security::verifyPassword('wrongpassword', $hash), '错误密码验证应失败');
    }

    /**
     * 测试随机字符串生成
     */
    public function testGenerateRandomString()
    {
        $str1 = Security::generateRandomString(16);
        $str2 = Security::generateRandomString(16);
        
        $this->assertEquals(16, strlen($str1), '字符串长度应为16');
        $this->assertFalse($str1 === $str2, '两次生成的字符串应该不同');
    }
}

/**
 * Container类测试
 */
class ContainerTest extends TestCase
{
    /**
     * 测试基本绑定
     */
    public function testBind()
    {
        Container::bind('test', function() {
            return new stdClass();
        });
        
        $this->assertTrue(Container::has('test'));
        
        $instance = Container::make('test');
        $this->assertInstanceOf('stdClass', $instance);
        
        Container::forget('test');
        $this->assertFalse(Container::has('test'));
    }

    /**
     * 测试单例绑定
     */
    public function testSingleton()
    {
        Container::singleton('singleton_test', function() {
            return new stdClass();
        });
        
        $instance1 = Container::make('singleton_test');
        $instance2 = Container::make('singleton_test');
        
        $this->assertTrue($instance1 === $instance2, '单例应返回相同实例');
        
        Container::forget('singleton_test');
    }

    /**
     * 测试实例注册
     */
    public function testInstance()
    {
        $obj = new stdClass();
        $obj->test = 'value';
        
        Container::instance('test_instance', $obj);
        
        $result = Container::make('test_instance');
        $this->assertTrue($result === $obj, '应返回注册的实例');
        
        Container::forget('test_instance');
    }

    /**
     * 测试别名
     */
    public function testAlias()
    {
        Container::bind('original', function() {
            return 'original_value';
        });
        
        Container::alias('original', 'alias_test');
        
        $this->assertTrue(Container::has('alias_test'));
        
        $result = Container::make('alias_test');
        $this->assertEquals('original_value', $result);
        
        Container::forget('original');
    }
}

/**
 * Logger类测试
 */
class LoggerTest extends TestCase
{
    /**
     * 测试日志记录
     */
    public function testLog()
    {
        $result = Logger::info('测试信息日志');
        $this->assertTrue($result, '日志记录应成功');
        
        $result = Logger::error('测试错误日志');
        $this->assertTrue($result, '错误日志记录应成功');
        
        $result = Logger::debug('测试调试日志', ['key' => 'value']);
        $this->assertTrue($result, '调试日志记录应成功');
    }

    /**
     * 测试日志级别
     */
    public function testLogLevels()
    {
        $logger = Logger::getInstance();
        
        $this->assertTrue($logger->setMinLevel(Logger::INFO) instanceof Logger);
        
        $result = Logger::debug('这条不应该被记录');
        
        $logger->setMinLevel(Logger::DEBUG);
    }

    /**
     * 测试日志获取
     */
    public function testGetLogs()
    {
        Logger::info('测试日志获取功能');
        
        $logs = Logger::getLogs(date('Y-m-d'), 10);
        $this->assertTrue(is_array($logs), '日志应返回数组');
    }
}

// 注册所有测试类
TestRunner::register(HelperTest::class);
TestRunner::register(SecurityTest::class);
TestRunner::register(ContainerTest::class);
TestRunner::register(LoggerTest::class);

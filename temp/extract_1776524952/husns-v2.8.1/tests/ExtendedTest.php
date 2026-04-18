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
 * Queue类测试
 */
class QueueTest extends TestCase
{
    /**
     * 测试队列推送
     */
    public function testPush()
    {
        $result = Queue::push('test_job', ['key' => 'value']);
        
        if ($result === false && !Queue::tableExists()) {
            $this->assertTrue(true, '队列表不存在时跳过测试');
            return;
        }
        
        $this->assertTrue($result !== false, '队列推送应成功');
    }
    
    /**
     * 测试延迟推送
     */
    public function testLater()
    {
        $result = Queue::later(60, 'delayed_job', ['delayed' => true]);
        
        if ($result === false && !Queue::tableExists()) {
            $this->assertTrue(true, '队列表不存在时跳过测试');
            return;
        }
        
        $this->assertTrue($result !== false, '延迟推送应成功');
    }
    
    /**
     * 测试队列统计
     */
    public function testStats()
    {
        if (!Queue::tableExists()) {
            $this->assertTrue(true, '队列表不存在时跳过测试');
            return;
        }
        
        $stats = Queue::stats();
        
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('processing', $stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('failed', $stats);
    }
    
    /**
     * 测试队列大小
     */
    public function testSize()
    {
        if (!Queue::tableExists()) {
            $this->assertTrue(true, '队列表不存在时跳过测试');
            return;
        }
        
        $size = Queue::size('default');
        $this->assertTrue($size >= 0, '队列大小应为非负数');
    }
}

/**
 * Helper扩展测试
 */
class HelperExtendedTest extends TestCase
{
    /**
     * 测试图片网格计算
     */
    public function testCalculateImageGrid()
    {
        $this->assertEquals([1], Helper::calculateImageGrid(1));
        $this->assertEquals([2], Helper::calculateImageGrid(2));
        $this->assertEquals([3], Helper::calculateImageGrid(3));
        $this->assertEquals([2, 2], Helper::calculateImageGrid(4));
        $this->assertEquals([3, 2], Helper::calculateImageGrid(5));
        $this->assertEquals([3, 3], Helper::calculateImageGrid(6));
    }
    
    /**
     * 测试头像颜色生成
     */
    public function testAvatarGeneration()
    {
        $avatar = Helper::avatar(null, 'testuser', 'default');
        
        $this->assertNotEmpty($avatar, '头像HTML不应为空');
        $this->assertStringContains('avatar-default', $avatar, '应包含默认头像类');
        $this->assertStringContains('testuser', $avatar, '应包含用户名');
    }
    
    /**
     * 测试表情解析
     */
    public function testParseEmojis()
    {
        $content = '测试表情[哈哈]';
        $result = Helper::parseEmojis($content);
        
        $this->assertNotEmpty($result, '解析结果不应为空');
    }
}

/**
 * Model基类测试
 */
class ModelTest extends TestCase
{
    /**
     * 测试分页方法
     */
    public function testPaginate()
    {
        $model = new TestModel();
        
        $result = $model->testPaginate(1, 10);
        
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('pageSize', $result);
        $this->assertArrayHasKey('totalPages', $result);
        
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['pageSize']);
    }
}

/**
 * 测试用Model
 */
class TestModel extends Model
{
    protected $table = 'users';
    
    public function testPaginate($page, $pageSize)
    {
        return $this->paginate($page, $pageSize);
    }
}

/**
 * View类测试
 */
class ViewTest extends TestCase
{
    private $view;
    
    protected function setUp()
    {
        $this->view = new View();
    }
    
    /**
     * 测试布局设置
     */
    public function testSetLayout()
    {
        $result = $this->view->setLayout('admin');
        
        $this->assertInstanceOf('View', $result, '应返回View实例');
    }
    
    /**
     * 测试数据分配
     */
    public function testAssign()
    {
        $result = $this->view->assign('key', 'value');
        
        $this->assertInstanceOf('View', $result, '应返回View实例');
        
        $result = $this->view->assign(['key1' => 'value1', 'key2' => 'value2']);
        
        $this->assertInstanceOf('View', $result, '应返回View实例');
    }
    
    /**
     * 测试HTML转义
     */
    public function testEscape()
    {
        $input = '<script>alert("xss")</script>';
        $result = $this->view->escape($input);
        
        $this->assertStringContains('&lt;', $result, '应转义HTML标签');
        $this->assertFalse(strpos($result, '<script>') !== false, '不应包含原始script标签');
    }
    
    /**
     * 测试URL生成
     */
    public function testUrl()
    {
        $url = $this->view->url('user/profile');
        
        $this->assertNotEmpty($url, 'URL不应为空');
    }
    
    /**
     * 测试资源URL生成
     */
    public function testAsset()
    {
        $url = $this->view->asset('css/style.css');
        
        $this->assertNotEmpty($url, '资源URL不应为空');
        $this->assertStringContains('css/style.css', $url, '应包含资源路径');
    }
    
    /**
     * 测试文件大小格式化
     */
    public function testFormatFileSize()
    {
        $this->assertEquals('100 B', $this->view->formatFileSize(100));
        $this->assertStringContains('KB', $this->view->formatFileSize(1024));
        $this->assertStringContains('MB', $this->view->formatFileSize(1024 * 1024));
    }
}

/**
 * Hook类测试
 */
class HookTest extends TestCase
{
    /**
     * 测试钩子注册
     */
    public function testRegister()
    {
        Hook::register('test_hook', function($data) {
            return $data;
        });
        
        $this->assertTrue(Hook::has('test_hook'), '钩子应注册成功');
        
        Hook::remove('test_hook');
        $this->assertFalse(Hook::has('test_hook'), '钩子应被移除');
    }
    
    /**
     * 测试钩子触发
     */
    public function testTrigger()
    {
        Hook::register('modify_test', function($data) {
            $data['modified'] = true;
            return $data;
        });
        
        $result = Hook::trigger('modify_test', ['original' => true]);
        
        $this->assertArrayHasKey('original', $result, '应保留原始数据');
        $this->assertArrayHasKey('modified', $result, '应添加修改数据');
        
        Hook::remove('modify_test');
    }
    
    /**
     * 测试优先级
     */
    public function testPriority()
    {
        $order = [];
        
        Hook::register('priority_test', function($data) use (&$order) {
            $order[] = 'second';
            return $data;
        }, 10);
        
        Hook::register('priority_test', function($data) use (&$order) {
            $order[] = 'first';
            return $data;
        }, 5);
        
        Hook::trigger('priority_test', []);
        
        $this->assertEquals('first', $order[0], '高优先级应先执行');
        $this->assertEquals('second', $order[1], '低优先级应后执行');
        
        Hook::remove('priority_test');
    }
}

/**
 * Security扩展测试
 */
class SecurityExtendedTest extends TestCase
{
    /**
     * 测试XSS清理 - 危险标签
     */
    public function testXssCleanDangerousTags()
    {
        $tests = [
            '<script>alert(1)</script>' => 'script',
            '<iframe src="evil.com"></iframe>' => 'iframe',
            '<object data="evil.swf"></object>' => 'object',
            '<embed src="evil.swf">' => 'embed',
        ];
        
        foreach ($tests as $input => $tag) {
            $result = Security::xssClean($input);
            $this->assertFalse(strpos($result, "<{$tag}") !== false, "应移除{$tag}标签");
        }
    }
    
    /**
     * 测试XSS清理 - 事件处理属性
     */
    public function testXssCleanEventHandlers()
    {
        $input = '<div onclick="alert(1)" onmouseover="evil()">test</div>';
        $result = Security::xssClean($input);
        
        $this->assertFalse(strpos($result, 'onclick') !== false, '应移除onclick属性');
        $this->assertFalse(strpos($result, 'onmouseover') !== false, '应移除onmouseover属性');
    }
    
    /**
     * 测试XSS清理 - 危险协议
     */
    public function testXssCleanProtocols()
    {
        $tests = [
            '<a href="javascript:alert(1)">link</a>',
            '<a href="vbscript:msgbox(1)">link</a>',
        ];
        
        foreach ($tests as $input) {
            $result = Security::xssClean($input);
            $this->assertFalse(strpos($result, 'javascript:') !== false, '应移除javascript协议');
            $this->assertFalse(strpos($result, 'vbscript:') !== false, '应移除vbscript协议');
        }
    }
    
    /**
     * 测试文件名清理
     */
    public function testSanitizeFilename()
    {
        $this->assertEquals('test.txt', Security::sanitizeFilename('test.txt'));
        $this->assertEquals('test_file.txt', Security::sanitizeFilename('test_file.txt'));
        $this->assertFalse(strpos(Security::sanitizeFilename('../../../etc/passwd'), '..') !== false, '应移除路径遍历');
    }
    
    /**
     * 测试手机号验证
     */
    public function testValidatePhone()
    {
        $this->assertTrue(Security::validatePhone('13800138000'));
        $this->assertTrue(Security::validatePhone('15912345678'));
        $this->assertFalse(Security::validatePhone('12800138000'));
        $this->assertFalse(Security::validatePhone('1380013800'));
    }
}

/**
 * Controller基类测试
 */
class ControllerTest extends TestCase
{
    /**
     * 测试JSON响应
     */
    public function testJsonResponse()
    {
        $controller = new TestController();
        
        ob_start();
        $controller->testJsonSuccess(['key' => 'value']);
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals(0, $data['code'], '成功响应code应为0');
    }
}

/**
 * 测试用Controller
 */
class TestController extends Controller
{
    public function testJsonSuccess($data)
    {
        $this->jsonSuccess($data);
    }
}

// 注册所有测试类
TestRunner::register(QueueTest::class);
TestRunner::register(HelperExtendedTest::class);
TestRunner::register(ModelTest::class);
TestRunner::register(ViewTest::class);
TestRunner::register(HookTest::class);
TestRunner::register(SecurityExtendedTest::class);
TestRunner::register(ControllerTest::class);

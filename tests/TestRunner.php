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

/**
 * 简易单元测试框架
 * 
 * 提供基本的测试功能，不依赖外部库，轻量级实现
 * 
 * 使用示例：
 *   // 运行所有测试
 *   TestRunner::runAll();
 *   
 *   // 运行单个测试类
 *   TestRunner::run('HelperTest');
 */
class TestRunner
{
    /**
     * 测试结果统计
     * @var array
     */
    private static $stats = array(
        'passed' => 0,
        'failed' => 0,
        'errors' => 0,
        'total' => 0,
    );

    /**
     * 已注册的测试类
     * @var array
     */
    private static $testClasses = array();

    /**
     * 注册测试类
     * 
     * @param string $className 测试类名
     * @return void
     */
    public static function register($className)
    {
        self::$testClasses[] = $className;
    }

    /**
     * 运行所有测试
     * 
     * @return array 测试结果
     */
    public static function runAll()
    {
        self::$stats = array(
            'passed' => 0,
            'failed' => 0,
            'errors' => 0,
            'total' => 0,
        );

        $results = array();

        foreach (self::$testClasses as $className) {
            $result = self::run($className);
            $results[$className] = $result;
        }

        self::printSummary();

        return array(
            'stats' => self::$stats,
            'results' => $results,
        );
    }

    /**
     * 运行单个测试类
     * 
     * @param string $className 测试类名
     * @return array 测试结果
     */
    public static function run($className)
    {
        $test = new $className();
        $methods = get_class_methods($test);
        $testMethods = array();

        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                $testMethods[] = $method;
            }
        }

        $results = array();

        foreach ($testMethods as $method) {
            self::$stats['total']++;

            try {
                $test->setUp();
                $test->$method();
                $test->tearDown();

                $results[$method] = array(
                    'status' => 'passed',
                    'message' => '',
                );
                self::$stats['passed']++;
            } catch (Exception $e) {
                $results[$method] = array(
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                );
                self::$stats['failed']++;
                self::$stats['errors']++;
            }
        }

        return array(
            'class' => $className,
            'methods' => $testMethods,
            'results' => $results,
        );
    }

    /**
     * 打印测试摘要
     * 
     * @return void
     */
    private static function printSummary()
    {
        echo "\n";
        echo "========================================\n";
        echo "           测试结果摘要\n";
        echo "========================================\n";
        echo "总计: " . self::$stats['total'] . " 个测试\n";
        echo "通过: " . self::$stats['passed'] . " 个\n";
        echo "失败: " . self::$stats['failed'] . " 个\n";
        echo "错误: " . self::$stats['errors'] . " 个\n";
        echo "========================================\n";

        if (self::$stats['failed'] > 0) {
            echo "\n⚠️ 存在失败的测试！\n";
        } else {
            echo "\n✅ 所有测试通过！\n";
        }
    }
}

/**
 * 测试基类
 * 
 * 所有测试类都应继承此类
 */
abstract class TestCase
{
    /**
     * 测试前准备
     * 
     * @return void
     */
    protected function setUp()
    {
    }

    /**
     * 测试后清理
     * 
     * @return void
     */
    protected function tearDown()
    {
    }

    /**
     * 断言两个值相等
     * 
     * @param mixed $expected 期望值
     * @param mixed $actual 实际值
     * @param string $message 错误消息
     * @return void
     * @throws Exception
     */
    protected function assertEquals($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            $msg = $message ?: "期望值: " . var_export($expected, true) . ", 实际值: " . var_export($actual, true);
            throw new Exception($msg);
        }
    }

    /**
     * 断言值为true
     * 
     * @param mixed $value 值
     * @param string $message 错误消息
     * @return void
     * @throws Exception
     */
    protected function assertTrue($value, $message = '')
    {
        if ($value !== true) {
            $msg = $message ?: "期望值: true, 实际值: " . var_export($value, true);
            throw new Exception($msg);
        }
    }

    /**
     * 断言值为false
     * 
     * @param mixed $value 值
     * @param string $message 错误消息
     * @return void
     * @throws Exception
     */
    protected function assertFalse($value, $message = '')
    {
        if ($value !== false) {
            $msg = $message ?: "期望值: false, 实际值: " . var_export($value, true);
            throw new Exception($msg);
        }
    }

    /**
     * 断言值不为空
     * 
     * @param mixed $value 值
     * @param string $message 错误消息
     * @return void
     * @throws Exception
     */
    protected function assertNotEmpty($value, $message = '')
    {
        if (empty($value)) {
            $msg = $message ?: "期望值不为空，实际值为空";
            throw new Exception($msg);
        }
    }

    /**
     * 断言值为空
     * 
     * @param mixed $value 值
     * @param string $message 错误消息
     * @return void
     * @throws Exception
     */
    protected function assertEmpty($value, $message = '')
    {
        if (!empty($value)) {
            $msg = $message ?: "期望值为空，实际值: " . var_export($value, true);
            throw new Exception($msg);
        }
    }

    /**
     * 断言数组包含指定元素
     * 
     * @param mixed $needle 要查找的值
     * @param array $haystack 数组
     * @param string $message 错误消息
     * @return void
     * @throws Exception
     */
    protected function assertContains($needle, $haystack, $message = '')
    {
        if (!in_array($needle, $haystack)) {
            $msg = $message ?: "数组中不包含指定元素";
            throw new Exception($msg);
        }
    }

    /**
     * 断言数组键存在
     * 
     * @param string $key 键名
     * @param array $array 数组
     * @param string $message 错误消息
     * @return void
     * @throws Exception
     */
    protected function assertArrayHasKey($key, $array, $message = '')
    {
        if (!array_key_exists($key, $array)) {
            $msg = $message ?: "数组中不存在键: {$key}";
            throw new Exception($msg);
        }
    }

    /**
     * 断言是某种类型的实例
     * 
     * @param mixed $expected 期望类型
     * @param mixed $actual 实际值
     * @param string $message 错误消息
     * @return void
     * @throws Exception
     */
    protected function assertInstanceOf($expected, $actual, $message = '')
    {
        if (!($actual instanceof $expected)) {
            $msg = $message ?: "期望类型: " . get_class($expected) . ", 实际类型: " . get_class($actual);
            throw new Exception($msg);
        }
    }

    /**
     * 断言字符串包含子串
     * 
     * @param string $needle 子串
     * @param string $haystack 字符串
     * @param string $message 错误消息
     * @return void
     * @throws Exception
     */
    protected function assertStringContains($needle, $haystack, $message = '')
    {
        if (strpos($haystack, $needle) === false) {
            $msg = $message ?: "字符串中不包含子串: {$needle}";
            throw new Exception($msg);
        }
    }

    /**
     * 断言正则匹配
     * 
     * @param string $pattern 正则模式
     * @param string $subject 要匹配的字符串
     * @param string $message 错误消息
     * @return void
     * @throws Exception
     */
    protected function assertMatches($pattern, $subject, $message = '')
    {
        if (!preg_match($pattern, $subject)) {
            $msg = $message ?: "字符串不匹配正则: {$pattern}";
            throw new Exception($msg);
        }
    }

    /**
     * 断言大于
     * 
     * @param mixed $expected 期望值
     * @param mixed $actual 实际值
     * @param string $message 错误消息
     * @return void
     * @throws Exception
     */
    protected function assertGreaterThan($expected, $actual, $message = '')
    {
        if (!($actual > $expected)) {
            $msg = $message ?: "期望值 > {$expected}, 实际值: {$actual}";
            throw new Exception($msg);
        }
    }

    /**
     * 断言小于
     * 
     * @param mixed $expected 期望值
     * @param mixed $actual 实际值
     * @param string $message 错误消息
     * @return void
     * @throws Exception
     */
    protected function assertLessThan($expected, $actual, $message = '')
    {
        if (!($actual < $expected)) {
            $msg = $message ?: "期望值 < {$expected}, 实际值: {$actual}";
            throw new Exception($msg);
        }
    }
}

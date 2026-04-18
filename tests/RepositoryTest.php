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
 * Repository接口测试
 */
class RepositoryInterfaceTest extends TestCase
{
    /**
     * 测试Repository接口方法存在
     */
    public function testInterfaceMethodsExist()
    {
        $interface = new ReflectionClass('Core\Contracts\RepositoryInterface');
        
        $this->assertTrue($interface->hasMethod('find'), 'Repository接口应有find方法');
        $this->assertTrue($interface->hasMethod('findAll'), 'Repository接口应有findAll方法');
        $this->assertTrue($interface->hasMethod('insert'), 'Repository接口应有insert方法');
        $this->assertTrue($interface->hasMethod('update'), 'Repository接口应有update方法');
        $this->assertTrue($interface->hasMethod('delete'), 'Repository接口应有delete方法');
        $this->assertTrue($interface->hasMethod('count'), 'Repository接口应有count方法');
        $this->assertTrue($interface->hasMethod('paginate'), 'Repository接口应有paginate方法');
    }
}

/**
 * BaseRepository类测试
 */
class BaseRepositoryTest extends TestCase
{
    /**
     * 测试BaseRepository类存在
     */
    public function testClassExists()
    {
        $this->assertTrue(
            class_exists('Core\BaseRepository'),
            'BaseRepository类应存在'
        );
    }
    
    /**
     * 测试BaseRepository实现接口
     */
    public function testImplementsInterface()
    {
        $reflection = new ReflectionClass('Core\BaseRepository');
        $interfaces = $reflection->getInterfaceNames();
        
        $this->assertTrue(
            in_array('Core\Contracts\RepositoryInterface', $interfaces),
            'BaseRepository应实现RepositoryInterface接口'
        );
    }
    
    /**
     * 测试BaseRepository是抽象类
     */
    public function testIsAbstract()
    {
        $reflection = new ReflectionClass('Core\BaseRepository');
        
        $this->assertTrue(
            $reflection->isAbstract(),
            'BaseRepository应为抽象类'
        );
    }
    
    /**
     * 测试钩子方法存在
     */
    public function testHookMethodsExist()
    {
        $reflection = new ReflectionClass('Core\BaseRepository');
        
        $this->assertTrue(
            $reflection->hasMethod('beforeInsert'),
            'BaseRepository应有beforeInsert钩子方法'
        );
        $this->assertTrue(
            $reflection->hasMethod('afterInsert'),
            'BaseRepository应有afterInsert钩子方法'
        );
        $this->assertTrue(
            $reflection->hasMethod('beforeUpdate'),
            'BaseRepository应有beforeUpdate钩子方法'
        );
        $this->assertTrue(
            $reflection->hasMethod('beforeDelete'),
            'BaseRepository应有beforeDelete钩子方法'
        );
    }
}

/**
 * PostRepository类测试
 */
class PostRepositoryTest extends TestCase
{
    /**
     * 测试PostRepository类存在
     */
    public function testClassExists()
    {
        $this->assertTrue(
            class_exists('Repository\PostRepository'),
            'PostRepository类应存在'
        );
    }
    
    /**
     * 测试PostRepository继承BaseRepository
     */
    public function testExtendsBaseRepository()
    {
        $reflection = new ReflectionClass('Repository\PostRepository');
        $parent = $reflection->getParentClass();
        
        $this->assertTrue(
            $parent && $parent->getName() === 'Core\BaseRepository',
            'PostRepository应继承BaseRepository'
        );
    }
    
    /**
     * 测试PostRepository方法存在
     */
    public function testMethodsExist()
    {
        $reflection = new ReflectionClass('Repository\PostRepository');
        
        $methods = [
            'getTimeline',
            'getTimelineCount',
            'publish',
            'like',
            'unlike',
            'isLiked',
            'getPost',
            'getHotTopics',
            'parseHideContent',
            'hasUserCommented'
        ];
        
        foreach ($methods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "PostRepository应有{$method}方法"
            );
        }
    }
}

/**
 * UserRepository类测试
 */
class UserRepositoryTest extends TestCase
{
    /**
     * 测试UserRepository类存在
     */
    public function testClassExists()
    {
        $this->assertTrue(
            class_exists('Repository\UserRepository'),
            'UserRepository类应存在'
        );
    }
    
    /**
     * 测试UserRepository继承BaseRepository
     */
    public function testExtendsBaseRepository()
    {
        $reflection = new ReflectionClass('Repository\UserRepository');
        $parent = $reflection->getParentClass();
        
        $this->assertTrue(
            $parent && $parent->getName() === 'Core\BaseRepository',
            'UserRepository应继承BaseRepository'
        );
    }
    
    /**
     * 测试UserRepository方法存在
     */
    public function testMethodsExist()
    {
        $reflection = new ReflectionClass('Repository\UserRepository');
        
        $methods = [
            'findByUsername',
            'findByEmail',
            'register',
            'login',
            'updateProfile',
            'updatePassword',
            'getFollowCount',
            'isFollowing',
            'follow',
            'unfollow',
            'getBanInfo',
            'isBanned',
            'searchUsers',
            'getFollowers',
            'getFollowing'
        ];
        
        foreach ($methods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "UserRepository应有{$method}方法"
            );
        }
    }
}

/**
 * PostPublishTrait测试
 */
class PostPublishTraitTest extends TestCase
{
    /**
     * 测试Trait存在
     */
    public function testTraitExists()
    {
        $this->assertTrue(
            trait_exists('Controller\Traits\PostPublishTrait'),
            'PostPublishTrait应存在'
        );
    }
    
    /**
     * 测试Trait方法存在
     */
    public function testMethodsExist()
    {
        $reflection = new ReflectionClass('Controller\Traits\PostPublishTrait');
        
        $methods = [
            'publish',
            'edit',
            'delete',
            'getEditData',
            'uploadImages',
            'uploadAttachments',
            'uploadVideos',
            'formatFileSize',
            'sendMentionNotifications'
        ];
        
        foreach ($methods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "PostPublishTrait应有{$method}方法"
            );
        }
    }
}

/**
 * PostInteractionTrait测试
 */
class PostInteractionTraitTest extends TestCase
{
    /**
     * 测试Trait存在
     */
    public function testTraitExists()
    {
        $this->assertTrue(
            trait_exists('Controller\Traits\PostInteractionTrait'),
            'PostInteractionTrait应存在'
        );
    }
    
    /**
     * 测试Trait方法存在
     */
    public function testMethodsExist()
    {
        $reflection = new ReflectionClass('Controller\Traits\PostInteractionTrait');
        
        $methods = [
            'like',
            'unlike',
            'favorite',
            'unfavorite',
            'comment',
            'repost',
            'getComments',
            'getReplies'
        ];
        
        foreach ($methods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "PostInteractionTrait应有{$method}方法"
            );
        }
    }
}

/**
 * PostListTrait测试
 */
class PostListTraitTest extends TestCase
{
    /**
     * 测试Trait存在
     */
    public function testTraitExists()
    {
        $this->assertTrue(
            trait_exists('Controller\Traits\PostListTrait'),
            'PostListTrait应存在'
        );
    }
    
    /**
     * 测试Trait方法存在
     */
    public function testMethodsExist()
    {
        $reflection = new ReflectionClass('Controller\Traits\PostListTrait');
        
        $methods = [
            'index',
            'detail',
            'hot',
            'topic',
            'featured',
            'pin',
            'feature',
            'getUserStats'
        ];
        
        foreach ($methods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "PostListTrait应有{$method}方法"
            );
        }
    }
}

/**
 * LoggerInterface测试
 */
class LoggerInterfaceTest extends TestCase
{
    /**
     * 测试LoggerInterface存在
     */
    public function testInterfaceExists()
    {
        $this->assertTrue(
            interface_exists('Core\Contracts\LoggerInterface'),
            'LoggerInterface应存在'
        );
    }
    
    /**
     * 测试LoggerInterface方法存在
     */
    public function testMethodsExist()
    {
        $reflection = new ReflectionClass('Core\Contracts\LoggerInterface');
        
        $methods = [
            'emergency',
            'alert',
            'critical',
            'error',
            'warning',
            'notice',
            'info',
            'debug',
            'log'
        ];
        
        foreach ($methods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "LoggerInterface应有{$method}方法"
            );
        }
    }
}

/**
 * 命名空间自动加载测试
 */
class NamespaceAutoloadTest extends TestCase
{
    /**
     * 测试Core命名空间自动加载
     */
    public function testCoreNamespaceAutoload()
    {
        $this->assertTrue(
            class_exists('Core\BaseRepository'),
            'Core\BaseRepository应能自动加载'
        );
    }
    
    /**
     * 测试Repository命名空间自动加载
     */
    public function testRepositoryNamespaceAutoload()
    {
        $this->assertTrue(
            class_exists('Repository\PostRepository'),
            'Repository\PostRepository应能自动加载'
        );
        
        $this->assertTrue(
            class_exists('Repository\UserRepository'),
            'Repository\UserRepository应能自动加载'
        );
    }
    
    /**
     * 测试Controller Traits命名空间自动加载
     */
    public function testControllerTraitsNamespaceAutoload()
    {
        $this->assertTrue(
            trait_exists('Controller\Traits\PostPublishTrait'),
            'Controller\Traits\PostPublishTrait应能自动加载'
        );
        
        $this->assertTrue(
            trait_exists('Controller\Traits\PostInteractionTrait'),
            'Controller\Traits\PostInteractionTrait应能自动加载'
        );
        
        $this->assertTrue(
            trait_exists('Controller\Traits\PostListTrait'),
            'Controller\Traits\PostListTrait应能自动加载'
        );
    }
    
    /**
     * 测试Contracts命名空间自动加载
     */
    public function testContractsNamespaceAutoload()
    {
        $this->assertTrue(
            interface_exists('Core\Contracts\RepositoryInterface'),
            'Core\Contracts\RepositoryInterface应能自动加载'
        );
        
        $this->assertTrue(
            interface_exists('Core\Contracts\LoggerInterface'),
            'Core\Contracts\LoggerInterface应能自动加载'
        );
    }
}

/**
 * 代码规范测试
 */
class CodeStandardTest extends TestCase
{
    /**
     * 测试所有新类都有命名空间
     */
    public function testNamespacesDefined()
    {
        $classes = [
            'Core\BaseRepository',
            'Repository\PostRepository',
            'Repository\UserRepository',
        ];
        
        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            $this->assertTrue(
                $reflection->inNamespace(),
                "{$class}应有命名空间"
            );
        }
    }
    
    /**
     * 测试所有Traits都有命名空间
     */
    public function testTraitsHaveNamespaces()
    {
        $traits = [
            'Controller\Traits\PostPublishTrait',
            'Controller\Traits\PostInteractionTrait',
            'Controller\Traits\PostListTrait',
        ];
        
        foreach ($traits as $trait) {
            $reflection = new ReflectionClass($trait);
            $this->assertTrue(
                $reflection->inNamespace(),
                "{$trait}应有命名空间"
            );
        }
    }
    
    /**
     * 测试所有接口都有命名空间
     */
    public function testInterfacesHaveNamespaces()
    {
        $interfaces = [
            'Core\Contracts\RepositoryInterface',
            'Core\Contracts\LoggerInterface',
        ];
        
        foreach ($interfaces as $interface) {
            $reflection = new ReflectionClass($interface);
            $this->assertTrue(
                $reflection->inNamespace(),
                "{$interface}应有命名空间"
            );
        }
    }
}

// 注册所有测试类
TestRunner::register(RepositoryInterfaceTest::class);
TestRunner::register(BaseRepositoryTest::class);
TestRunner::register(PostRepositoryTest::class);
TestRunner::register(UserRepositoryTest::class);
TestRunner::register(PostPublishTraitTest::class);
TestRunner::register(PostInteractionTraitTest::class);
TestRunner::register(PostListTraitTest::class);
TestRunner::register(LoggerInterfaceTest::class);
TestRunner::register(NamespaceAutoloadTest::class);
TestRunner::register(CodeStandardTest::class);

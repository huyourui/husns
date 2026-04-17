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
 * 依赖注入容器
 * 
 * 用于管理类的依赖关系和实例化，降低类之间的耦合度
 * 支持绑定、单例、自动解析等功能
 * 
 * 使用示例：
 *   // 绑定一个闭包
 *   Container::bind('db', function($container) {
 *       return new Database();
 *   });
 *   
 *   // 绑定单例
 *   Container::singleton('logger', function($container) {
 *       return new Logger();
 *   });
 *   
 *   // 获取实例
 *   $db = Container::make('db');
 *   $logger = Container::make('logger');
 */
class Container
{
    /**
     * 容器实例（单例模式）
     * @var Container|null
     */
    private static ?Container $instance = null;

    /**
     * 已绑定的服务绑定列表
     * @var array
     */
    private array $bindings = [];

    /**
     * 已解析的单例实例
     * @var array
     */
    private array $instances = [];

    /**
     * 别名映射
     * @var array
     */
    private array $aliases = [];

    /**
     * 私有构造函数，防止外部实例化
     */
    private function __construct()
    {
    }

    /**
     * 获取容器单例实例
     * 
     * @return Container
     */
    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 绑定一个服务到容器
     * 
     * @param string $abstract 服务标识（通常是类名或接口名）
     * @param callable|string|null $concrete 具体实现（闭包、类名或null表示自动解析）
     * @param bool $shared 是否共享（单例）
     * @return void
     */
    public static function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        $container = self::getInstance();

        // 如果没有提供具体实现，使用抽象名称作为具体类名
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $container->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];

        // 如果已有实例且重新绑定，清除旧实例
        unset($container->instances[$abstract]);
    }

    /**
     * 绑定一个单例服务到容器
     * 
     * @param string $abstract 服务标识
     * @param callable|string|null $concrete 具体实现
     * @return void
     */
    public static function singleton(string $abstract, $concrete = null): void
    {
        self::bind($abstract, $concrete, true);
    }

    /**
     * 注册一个已存在的实例为共享实例
     * 
     * @param string $abstract 服务标识
     * @param mixed $instance 实例对象
     * @return mixed
     */
    public static function instance(string $abstract, $instance)
    {
        $container = self::getInstance();
        $container->instances[$abstract] = $instance;
        return $instance;
    }

    /**
     * 设置别名
     * 
     * @param string $abstract 服务标识
     * @param string $alias 别名
     * @return void
     */
    public static function alias(string $abstract, string $alias): void
    {
        $container = self::getInstance();
        $container->aliases[$alias] = $abstract;
    }

    /**
     * 从容器中解析服务实例
     * 
     * @param string $abstract 服务标识
     * @param array $parameters 构造函数参数
     * @return mixed
     * @throws Exception 当服务无法解析时抛出异常
     */
    public static function make(string $abstract, array $parameters = [])
    {
        $container = self::getInstance();

        // 解析别名
        $abstract = $container->getAlias($abstract);

        // 如果是共享实例且已存在，直接返回
        if (isset($container->instances[$abstract])) {
            return $container->instances[$abstract];
        }

        // 获取具体实现
        $concrete = $container->getConcrete($abstract);

        // 构建实例
        $object = $container->build($concrete, $parameters);

        // 如果是共享的，保存实例
        if ($container->isShared($abstract)) {
            $container->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * 检查服务是否已绑定
     * 
     * @param string $abstract 服务标识
     * @return bool
     */
    public static function has(string $abstract): bool
    {
        $container = self::getInstance();
        $abstract = $container->getAlias($abstract);

        return isset($container->bindings[$abstract]) || 
               isset($container->instances[$abstract]);
    }

    /**
     * 移除服务绑定
     * 
     * @param string $abstract 服务标识
     * @return void
     */
    public static function forget(string $abstract): void
    {
        $container = self::getInstance();
        $abstract = $container->getAlias($abstract);

        unset($container->bindings[$abstract], $container->instances[$abstract]);
    }

    /**
     * 清空容器
     * 
     * @return void
     */
    public static function flush(): void
    {
        $container = self::getInstance();
        $container->bindings = [];
        $container->instances = [];
        $container->aliases = [];
    }

    /**
     * 获取别名对应的服务标识
     * 
     * @param string $abstract 服务标识或别名
     * @return string
     */
    private function getAlias(string $abstract): string
    {
        return $this->aliases[$abstract] ?? $abstract;
    }

    /**
     * 获取服务的具体实现
     * 
     * @param string $abstract 服务标识
     * @return mixed
     */
    private function getConcrete(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * 检查服务是否为共享（单例）
     * 
     * @param string $abstract 服务标识
     * @return bool
     */
    private function isShared(string $abstract): bool
    {
        return isset($this->bindings[$abstract]['shared']) && 
               $this->bindings[$abstract]['shared'] === true;
    }

    /**
     * 构建服务实例
     * 
     * @param callable|string $concrete 具体实现
     * @param array $parameters 构造函数参数
     * @return mixed
     * @throws Exception
     */
    private function build($concrete, array $parameters = [])
    {
        // 如果是闭包，直接执行
        if ($concrete instanceof Closure || is_callable($concrete)) {
            return $concrete($this, $parameters);
        }

        // 如果是类名字符串，尝试自动解析
        if (is_string($concrete) && class_exists($concrete)) {
            return $this->resolveClass($concrete, $parameters);
        }

        throw new Exception("无法解析服务: {$concrete}");
    }

    /**
     * 自动解析类并创建实例
     * 
     * @param string $className 类名
     * @param array $parameters 构造函数参数
     * @return object
     * @throws Exception
     */
    private function resolveClass(string $className, array $parameters = []): object
    {
        $reflector = new ReflectionClass($className);

        // 检查类是否可实例化
        if (!$reflector->isInstantiable()) {
            throw new Exception("类 {$className} 不可实例化");
        }

        // 获取构造函数
        $constructor = $reflector->getConstructor();

        // 如果没有构造函数，直接创建实例
        if ($constructor === null) {
            return new $className();
        }

        // 解析构造函数依赖
        $dependencies = $this->resolveDependencies(
            $constructor->getParameters(),
            $parameters
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * 解析方法依赖
     * 
     * @param ReflectionParameter[] $parameters 反射参数列表
     * @param array $primitives 原始参数
     * @return array
     * @throws Exception
     */
    private function resolveDependencies(array $parameters, array $primitives = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            // 如果参数在原始参数中提供，使用原始值
            if (array_key_exists($parameter->name, $primitives)) {
                $dependencies[] = $primitives[$parameter->name];
                continue;
            }

            // 尝试解析类型提示的类依赖
            $type = $parameter->getType();
            if ($type && !$type->isBuiltin()) {
                $className = $type->getName();
                $dependencies[] = self::make($className);
                continue;
            }

            // 如果有默认值，使用默认值
            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            // 无法解析依赖
            throw new Exception(
                "无法解析参数 {$parameter->name} 的依赖"
            );
        }

        return $dependencies;
    }

    /**
     * 调用回调函数并自动注入依赖
     * 
     * @param callable $callback 回调函数
     * @param array $parameters 额外参数
     * @return mixed
     */
    public static function call(callable $callback, array $parameters = [])
    {
        $container = self::getInstance();
        
        $reflector = new ReflectionFunction($callback);
        $dependencies = $container->resolveDependencies(
            $reflector->getParameters(),
            $parameters
        );

        return call_user_func_array($callback, $dependencies);
    }
}

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

namespace Core;

use Core\Contracts\RepositoryInterface;
use Database;

/**
 * 基础 Repository 类
 * 
 * 实现数据仓库模式，提供数据访问的抽象层
 * 遵循 Repository 模式，将业务逻辑与数据访问分离
 * 
 * @package Core
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * 数据库实例
     * @var Database
     */
    protected $db;

    /**
     * 表名（不含前缀）
     * @var string
     */
    protected $table;

    /**
     * 主键字段名
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 根据主键查找记录
     *
     * @param mixed $id 主键值
     * @return array|null
     */
    public function find($id): ?array
    {
        $table = $this->db->table($this->table);
        $result = $this->db->fetch(
            "SELECT * FROM `{$table}` WHERE `{$this->primaryKey}` = ?",
            [$id]
        );
        
        return $result ?: null;
    }

    /**
     * 查找所有记录
     *
     * @param string $where WHERE条件
     * @param array $params 参数
     * @param string $orderBy 排序
     * @param string $limit 限制
     * @return array
     */
    public function findAll(string $where = '1', array $params = [], string $orderBy = '', string $limit = ''): array
    {
        $table = $this->db->table($this->table);
        $sql = "SELECT * FROM `{$table}` WHERE {$where}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * 插入记录
     *
     * @param array $data 数据
     * @return int|string 返回插入的主键ID
     */
    public function insert(array $data)
    {
        $data = $this->beforeInsert($data);
        $result = $this->db->insert($this->table, $data);
        return $this->afterInsert($result);
    }

    /**
     * 更新记录
     *
     * @param mixed $id 主键值
     * @param array $data 数据
     * @return int 影响的行数
     */
    public function update($id, array $data): int
    {
        $data = $this->beforeUpdate($data);
        return $this->db->update($this->table, $data, "`{$this->primaryKey}` = ?", [$id]);
    }

    /**
     * 删除记录
     *
     * @param mixed $id 主键值
     * @return bool
     */
    public function delete($id): bool
    {
        $this->beforeDelete($id);
        return $this->db->delete($this->table, "`{$this->primaryKey}` = ?", [$id]) > 0;
    }

    /**
     * 统计记录数
     *
     * @param string $where WHERE条件
     * @param array $params 参数
     * @return int
     */
    public function count(string $where = '1', array $params = []): int
    {
        return $this->db->count($this->table, $where, $params);
    }

    /**
     * 分页查询
     *
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param string $where WHERE条件
     * @param array $params 参数
     * @param string $orderBy 排序
     * @return array
     */
    public function paginate(int $page = 1, int $pageSize = 10, string $where = '1', array $params = [], string $orderBy = ''): array
    {
        $total = $this->count($where, $params);
        $totalPages = ceil($total / $pageSize);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $pageSize;
        
        $limit = "{$offset}, {$pageSize}";
        $items = $this->findAll($where, $params, $orderBy, $limit);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages
        ];
    }

    /**
     * 插入前的钩子方法
     * 子类可重写此方法实现自定义逻辑
     *
     * @param array $data 数据
     * @return array
     */
    protected function beforeInsert(array $data): array
    {
        return $data;
    }

    /**
     * 插入后的钩子方法
     * 子类可重写此方法实现自定义逻辑
     *
     * @param int|string $id 插入的主键ID
     * @return int|string
     */
    protected function afterInsert($id)
    {
        return $id;
    }

    /**
     * 更新前的钩子方法
     * 子类可重写此方法实现自定义逻辑
     *
     * @param array $data 数据
     * @return array
     */
    protected function beforeUpdate(array $data): array
    {
        return $data;
    }

    /**
     * 删除前的钩子方法
     * 子类可重写此方法实现自定义逻辑
     *
     * @param mixed $id 主键值
     * @return void
     */
    protected function beforeDelete($id): void
    {
    }

    /**
     * 执行原生SQL查询
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return array
     */
    protected function query(string $sql, array $params = []): array
    {
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * 执行原生SQL并返回单行
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return array|null
     */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $result = $this->db->fetch($sql, $params);
        return $result ?: null;
    }

    /**
     * 执行原生SQL并返回单个值
     *
     * @param string $sql SQL语句
     * @param array $params 参数
     * @return mixed
     */
    protected function queryScalar(string $sql, array $params = [])
    {
        return $this->db->fetchColumn($sql, $params);
    }
}

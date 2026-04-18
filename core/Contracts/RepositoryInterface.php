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

namespace Core\Contracts;

/**
 * Repository 接口
 * 
 * 定义数据仓库的基本操作接口，用于抽象数据访问层
 * 
 * @package Core\Contracts
 */
interface RepositoryInterface
{
    /**
     * 根据主键查找记录
     *
     * @param mixed $id 主键值
     * @return array|null
     */
    public function find($id): ?array;

    /**
     * 查找所有记录
     *
     * @param string $where WHERE条件
     * @param array $params 参数
     * @param string $orderBy 排序
     * @param string $limit 限制
     * @return array
     */
    public function findAll(string $where = '1', array $params = [], string $orderBy = '', string $limit = ''): array;

    /**
     * 插入记录
     *
     * @param array $data 数据
     * @return int|string 返回插入的主键ID
     */
    public function insert(array $data);

    /**
     * 更新记录
     *
     * @param mixed $id 主键值
     * @param array $data 数据
     * @return int 影响的行数
     */
    public function update($id, array $data): int;

    /**
     * 删除记录
     *
     * @param mixed $id 主键值
     * @return bool
     */
    public function delete($id): bool;

    /**
     * 统计记录数
     *
     * @param string $where WHERE条件
     * @param array $params 参数
     * @return int
     */
    public function count(string $where = '1', array $params = []): int;

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
    public function paginate(int $page = 1, int $pageSize = 10, string $where = '1', array $params = [], string $orderBy = ''): array;
}

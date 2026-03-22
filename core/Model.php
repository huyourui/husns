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
class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find($id)
    {
        $table = $this->db->table($this->table);
        return $this->db->fetch("SELECT * FROM `{$table}` WHERE `{$this->primaryKey}` = ?", [$id]);
    }

    public function findAll($where = '1', $params = [], $orderBy = '', $limit = '')
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

    public function insert($data)
    {
        $data = $this->beforeInsert($data);
        $result = $this->db->insert($this->table, $data);
        return $this->afterInsert($result);
    }

    public function update($id, $data)
    {
        $data = $this->beforeUpdate($data);
        return $this->db->update($this->table, $data, "`{$this->primaryKey}` = ?", [$id]);
    }

    public function delete($id)
    {
        $this->beforeDelete($id);
        return $this->db->delete($this->table, "`{$this->primaryKey}` = ?", [$id]);
    }

    public function count($where = '1', $params = [])
    {
        return $this->db->count($this->table, $where, $params);
    }

    public function paginate($page = 1, $pageSize = 10, $where = '1', $params = [], $orderBy = '')
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

    protected function beforeInsert($data)
    {
        return $data;
    }

    protected function afterInsert($id)
    {
        return $id;
    }

    protected function beforeUpdate($data)
    {
        return $data;
    }

    protected function beforeDelete($id)
    {
        return true;
    }
}

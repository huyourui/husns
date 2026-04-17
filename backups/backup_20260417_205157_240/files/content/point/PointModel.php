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
class PointModel extends Model
{
    protected $table = 'point_rules';

    public function getActiveRules()
    {
        return $this->db->fetchAll(
            "SELECT * FROM __PREFIX__point_rules WHERE status = 1 ORDER BY id ASC"
        );
    }

    public function getAllRules()
    {
        return $this->db->fetchAll(
            "SELECT * FROM __PREFIX__point_rules ORDER BY id ASC"
        );
    }

    public function getRuleByAction($action)
    {
        return $this->db->fetch(
            "SELECT * FROM __PREFIX__point_rules WHERE action = ?",
            [$action]
        );
    }

    public function createRule($data)
    {
        $data['created_at'] = time();
        $data['updated_at'] = time();
        return $this->insert($data);
    }

    public function updateRule($id, $data)
    {
        $data['updated_at'] = time();
        return $this->update($id, $data);
    }

    public function deleteRule($id)
    {
        return $this->delete($id);
    }

    public function toggleStatus($id)
    {
        $rule = $this->find($id);
        if (!$rule) {
            return false;
        }
        
        $newStatus = $rule['status'] ? 0 : 1;
        return $this->update($id, ['status' => $newStatus, 'updated_at' => time()]);
    }

    public function getTodayCount($userId, $action)
    {
        $todayStart = strtotime(date('Y-m-d 00:00:00'));
        $todayEnd = strtotime(date('Y-m-d 23:59:59'));
        
        return (int)$this->db->fetch(
            "SELECT COUNT(*) as count FROM __PREFIX__point_logs WHERE user_id = ? AND action = ? AND created_at BETWEEN ? AND ?",
            [$userId, $action, $todayStart, $todayEnd]
        )['count'];
    }

    public function addLog($userId, $action, $points, $balance, $relatedType = null, $relatedId = null, $remark = '')
    {
        return $this->db->insert('point_logs', [
            'user_id' => $userId,
            'action' => $action,
            'points' => $points,
            'balance' => $balance,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'remark' => $remark,
            'created_at' => time()
        ]);
    }

    public function getUserLogs($userId, $page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;
        
        $logs = $this->db->fetchAll(
            "SELECT * FROM __PREFIX__point_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?, ?",
            [$userId, $offset, $pageSize]
        );
        
        $total = $this->db->count('point_logs', 'user_id = ?', [$userId]);
        
        return [
            'items' => $logs,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => ceil($total / $pageSize)
        ];
    }
}

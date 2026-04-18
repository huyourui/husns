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
class LinkModel extends Model
{
    protected $table = 'links';

    public function getActive()
    {
        return $this->db->fetchAll(
            "SELECT * FROM __PREFIX__links WHERE status = 1 ORDER BY sort_order ASC, id ASC"
        );
    }

    public function getAll()
    {
        return $this->db->fetchAll(
            "SELECT * FROM __PREFIX__links ORDER BY sort_order ASC, id ASC"
        );
    }

    public function createLink($data)
    {
        $data['created_at'] = time();
        $data['updated_at'] = time();
        return $this->insert($data);
    }

    public function updateLink($id, $data)
    {
        $data['updated_at'] = time();
        return $this->update($id, $data);
    }

    public function deleteLink($id)
    {
        return $this->delete($id);
    }

    public function toggleStatus($id)
    {
        $link = $this->find($id);
        if (!$link) {
            return false;
        }
        
        $newStatus = $link['status'] ? 0 : 1;
        return $this->update($id, ['status' => $newStatus, 'updated_at' => time()]);
    }

    public function getById($id)
    {
        return $this->find($id);
    }

    public function countAll()
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) FROM __PREFIX__links"
        );
        return $result;
    }

    public function countActive()
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) FROM __PREFIX__links WHERE status = 1"
        );
        return $result;
    }
}

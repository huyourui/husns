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
class AnnouncementModel extends Model
{
    protected $table = 'announcements';

    public function getActive()
    {
        return $this->db->fetchAll(
            "SELECT * FROM __PREFIX__announcements WHERE status = 1 ORDER BY sort_order ASC, id ASC"
        );
    }

    public function getAll()
    {
        return $this->db->fetchAll(
            "SELECT * FROM __PREFIX__announcements ORDER BY sort_order ASC, id DESC"
        );
    }

    public function create($data)
    {
        $data['created_at'] = time();
        $data['updated_at'] = time();
        return $this->insert($data);
    }

    public function updateAnnouncement($id, $data)
    {
        $data['updated_at'] = time();
        return $this->update($id, $data);
    }

    public function deleteAnnouncement($id)
    {
        return $this->delete($id);
    }

    public function toggleStatus($id)
    {
        $announcement = $this->find($id);
        if (!$announcement) {
            return false;
        }
        
        $newStatus = $announcement['status'] ? 0 : 1;
        return $this->update($id, ['status' => $newStatus, 'updated_at' => time()]);
    }
}

<?php
class InviteController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('admin');
    }
    
    public function index()
    {
        $this->checkAdmin();
        
        $page = (int)Helper::get('page', 1);
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        
        $db = Database::getInstance();
        
        $sql = "SELECT ic.*, u.username as used_by_username 
                FROM __PREFIX__invite_codes ic 
                LEFT JOIN __PREFIX__users u ON ic.used_by = u.id 
                WHERE ic.status = 0 
                ORDER BY ic.created_at DESC 
                LIMIT {$offset}, {$pageSize}";
        $codes = $db->fetchAll($sql);
        
        $total = $db->fetchColumn("SELECT COUNT(*) FROM __PREFIX__invite_codes WHERE status = 0");
        $totalPages = max(1, ceil($total / $pageSize));
        
        $this->render('admin/invite', [
            'codes' => $codes,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total
        ]);
    }
    
    public function generate()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        $count = (int)Helper::post('count', 1);
        $count = max(1, min(100, $count));
        
        $db = Database::getInstance();
        $generated = 0;
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $code = $this->generateCode();
            
            try {
                $db->insert('invite_codes', [
                    'code' => $code,
                    'status' => 0,
                    'used_by' => 0,
                    'used_at' => 0,
                    'created_at' => time()
                ]);
                $codes[] = $code;
                $generated++;
            } catch (Exception $e) {
            }
        }
        
        if ($generated > 0) {
            Helper::jsonSuccess(['count' => $generated, 'codes' => $codes], "成功生成 {$generated} 个邀请码");
        } else {
            Helper::jsonError('生成失败');
        }
    }
    
    public function delete()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        $id = (int)Helper::post('id');
        
        $db = Database::getInstance();
        $code = $db->fetch("SELECT * FROM __PREFIX__invite_codes WHERE id = ?", [$id]);
        
        if (!$code) {
            Helper::jsonError('邀请码不存在');
        }
        
        if ($code['status'] == 1) {
            Helper::jsonError('该邀请码已被使用，无法删除');
        }
        
        $db->delete('invite_codes', 'id = ?', [$id]);
        
        Helper::jsonSuccess(null, '删除成功');
    }
    
    public function clear()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        $db = Database::getInstance();
        $db->delete('invite_codes', 'status = 0');
        
        Helper::jsonSuccess(null, '清空成功');
    }
    
    private function generateCode()
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 16; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
    
    public static function verifyCode($code)
    {
        if (empty($code)) {
            return false;
        }
        
        $db = Database::getInstance();
        $inviteCode = $db->fetch(
            "SELECT * FROM __PREFIX__invite_codes WHERE code = ? AND status = 0",
            [$code]
        );
        
        return $inviteCode ? $inviteCode : false;
    }
    
    public static function useCode($code, $userId)
    {
        $db = Database::getInstance();
        return $db->update('invite_codes', [
            'status' => 1,
            'used_by' => $userId,
            'used_at' => time()
        ], 'code = ? AND status = 0', [$code]) > 0;
    }
}

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
class AnnouncementController extends Controller
{
    private $announcementModel;

    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('admin');
        $this->announcementModel = new AnnouncementModel();
    }

    public function index()
    {
        $this->checkAdmin();
        
        $announcements = $this->announcementModel->getAll();
        
        $this->render('admin/announcements', ['announcements' => $announcements]);
    }

    public function add()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $content = trim(Helper::post('content'));
        $color = trim(Helper::post('color', 'blue'));
        $sortOrder = (int)Helper::post('sort_order', 0);
        
        if (empty($content)) {
            $this->jsonError('公告内容不能为空');
        }
        
        $allowedColors = ['blue', 'green', 'yellow', 'red', 'purple', 'cyan'];
        if (!in_array($color, $allowedColors)) {
            $color = 'blue';
        }
        
        $id = $this->announcementModel->create([
            'content' => $content,
            'color' => $color,
            'sort_order' => $sortOrder,
            'status' => 1
        ]);
        
        if ($id) {
            $this->jsonSuccess(['id' => $id], '公告添加成功');
        }
        
        $this->jsonError('添加失败');
    }

    public function edit()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $id = (int)Helper::post('id');
        $content = trim(Helper::post('content'));
        $color = trim(Helper::post('color', 'blue'));
        $sortOrder = (int)Helper::post('sort_order', 0);
        
        if (empty($content)) {
            $this->jsonError('公告内容不能为空');
        }
        
        $announcement = $this->announcementModel->find($id);
        if (!$announcement) {
            $this->jsonError('公告不存在');
        }
        
        $allowedColors = ['blue', 'green', 'yellow', 'red', 'purple', 'cyan'];
        if (!in_array($color, $allowedColors)) {
            $color = 'blue';
        }
        
        $this->announcementModel->updateAnnouncement($id, [
            'content' => $content,
            'color' => $color,
            'sort_order' => $sortOrder
        ]);
        
        $this->jsonSuccess(null, '公告更新成功');
    }

    public function delete()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $id = (int)Helper::post('id');
        
        $announcement = $this->announcementModel->find($id);
        if (!$announcement) {
            $this->jsonError('公告不存在');
        }
        
        $this->announcementModel->deleteAnnouncement($id);
        
        $this->jsonSuccess(null, '公告已删除');
    }

    public function toggle()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $id = (int)Helper::post('id');
        
        $announcement = $this->announcementModel->find($id);
        if (!$announcement) {
            $this->jsonError('公告不存在');
        }
        
        $this->announcementModel->toggleStatus($id);
        
        $newStatus = $announcement['status'] ? 0 : 1;
        $this->jsonSuccess(['status' => $newStatus], $newStatus ? '公告已启用' : '公告已禁用');
    }
}

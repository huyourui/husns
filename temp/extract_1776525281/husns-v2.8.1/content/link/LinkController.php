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
class LinkController extends Controller
{
    private $linkModel;

    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('admin');
        $this->linkModel = new LinkModel();
    }

    public function index()
    {
        $this->checkAdmin();
        
        $links = $this->linkModel->getAll();
        
        $this->render('admin/links', ['links' => $links]);
    }

    public function add()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $name = trim(Helper::post('name'));
        $url = trim(Helper::post('url'));
        $description = trim(Helper::post('description', ''));
        $sortOrder = (int)Helper::post('sort_order', 0);
        
        if (empty($name)) {
            $this->jsonError('网站名称不能为空');
        }
        
        if (empty($url)) {
            $this->jsonError('网站地址不能为空');
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->jsonError('网站地址格式不正确');
        }
        
        $id = $this->linkModel->createLink([
            'name' => $name,
            'url' => $url,
            'description' => $description,
            'sort_order' => $sortOrder,
            'status' => 1
        ]);
        
        if ($id) {
            $this->jsonSuccess(['id' => $id], '添加成功');
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
        $name = trim(Helper::post('name'));
        $url = trim(Helper::post('url'));
        $description = trim(Helper::post('description', ''));
        $sortOrder = (int)Helper::post('sort_order', 0);
        
        if (empty($name)) {
            $this->jsonError('网站名称不能为空');
        }
        
        if (empty($url)) {
            $this->jsonError('网站地址不能为空');
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->jsonError('网站地址格式不正确');
        }
        
        $link = $this->linkModel->find($id);
        if (!$link) {
            $this->jsonError('链接不存在');
        }
        
        $this->linkModel->updateLink($id, [
            'name' => $name,
            'url' => $url,
            'description' => $description,
            'sort_order' => $sortOrder
        ]);
        
        $this->jsonSuccess(null, '更新成功');
    }

    public function delete()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $id = (int)Helper::post('id');
        
        $link = $this->linkModel->find($id);
        if (!$link) {
            $this->jsonError('链接不存在');
        }
        
        $this->linkModel->deleteLink($id);
        
        $this->jsonSuccess(null, '删除成功');
    }

    public function toggle()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $id = (int)Helper::post('id');
        
        $link = $this->linkModel->find($id);
        if (!$link) {
            $this->jsonError('链接不存在');
        }
        
        $this->linkModel->toggleStatus($id);
        
        $newStatus = $link['status'] ? 0 : 1;
        $this->jsonSuccess(['status' => $newStatus], $newStatus ? '已启用' : '已禁用');
    }
}

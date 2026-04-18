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
class PointController extends Controller
{
    private $pointModel;

    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('admin');
        $this->pointModel = new PointModel();
    }

    public function index()
    {
        $this->checkAdmin();
        
        $rules = $this->pointModel->getAllRules();
        
        $this->render('admin/points', ['rules' => $rules]);
    }

    public function add()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $action = trim(Helper::post('action'));
        $name = trim(Helper::post('name'));
        $points = (int)Helper::post('points');
        $dailyLimit = (int)Helper::post('daily_limit', 0);
        
        if (empty($action)) {
            $this->jsonError('动作标识不能为空');
        }
        
        if (empty($name)) {
            $this->jsonError('动作名称不能为空');
        }
        
        if (!preg_match('/^[a-z_]+$/', $action)) {
            $this->jsonError('动作标识只能包含小写字母和下划线');
        }
        
        $existing = $this->pointModel->getRuleByAction($action);
        if ($existing) {
            $this->jsonError('该动作标识已存在');
        }
        
        $id = $this->pointModel->createRule([
            'action' => $action,
            'name' => $name,
            'points' => $points,
            'daily_limit' => $dailyLimit,
            'status' => 1
        ]);
        
        if ($id) {
            $this->jsonSuccess(['id' => $id], '规则添加成功');
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
        $points = (int)Helper::post('points');
        $dailyLimit = (int)Helper::post('daily_limit', 0);
        
        if (empty($name)) {
            $this->jsonError('动作名称不能为空');
        }
        
        $rule = $this->pointModel->find($id);
        if (!$rule) {
            $this->jsonError('规则不存在');
        }
        
        $this->pointModel->updateRule($id, [
            'name' => $name,
            'points' => $points,
            'daily_limit' => $dailyLimit
        ]);
        
        $this->jsonSuccess(null, '规则更新成功');
    }

    public function delete()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $id = (int)Helper::post('id');
        
        $rule = $this->pointModel->find($id);
        if (!$rule) {
            $this->jsonError('规则不存在');
        }
        
        $this->pointModel->deleteRule($id);
        
        $this->jsonSuccess(null, '规则已删除');
    }

    public function toggle()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            $this->jsonError('安全验证失败');
        }
        
        $id = (int)Helper::post('id');
        
        $rule = $this->pointModel->find($id);
        if (!$rule) {
            $this->jsonError('规则不存在');
        }
        
        $this->pointModel->toggleStatus($id);
        
        $newStatus = $rule['status'] ? 0 : 1;
        $this->jsonSuccess(['status' => $newStatus], $newStatus ? '规则已启用' : '规则已禁用');
    }
}

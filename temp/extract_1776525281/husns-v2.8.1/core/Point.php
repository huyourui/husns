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
class Point
{
    private static $instance = null;
    private $model;
    private $userModel;

    private function __construct()
    {
        $this->model = new PointModel();
        $this->userModel = new UserModel();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function change($userId, $action, $relatedType = null, $relatedId = null, $remark = '')
    {
        $instance = self::getInstance();
        
        $rule = $instance->model->getRuleByAction($action);
        
        if (!$rule || !$rule['status']) {
            return false;
        }

        $points = (int)$rule['points'];
        
        if ($points === 0) {
            return false;
        }

        if ($rule['daily_limit'] > 0) {
            $todayCount = $instance->model->getTodayCount($userId, $action);
            if ($todayCount >= $rule['daily_limit']) {
                return false;
            }
        }

        $user = $instance->userModel->find($userId);
        if (!$user) {
            return false;
        }

        $currentBalance = (int)$user['points'];
        $newBalance = $currentBalance + $points;

        if ($newBalance < 0) {
            return false;
        }

        $instance->userModel->update($userId, ['points' => $newBalance, 'updated_at' => time()]);

        $instance->model->addLog(
            $userId,
            $action,
            $points,
            $newBalance,
            $relatedType,
            $relatedId,
            $remark ?: ($rule['name'] ?? $action)
        );

        return true;
    }

    public static function getBalance($userId)
    {
        $instance = self::getInstance();
        $user = $instance->userModel->find($userId);
        return $user ? (int)$user['points'] : 0;
    }

    public static function getRule($action)
    {
        $instance = self::getInstance();
        return $instance->model->getRuleByAction($action);
    }

    public static function getAllRules()
    {
        $instance = self::getInstance();
        return $instance->model->getAllRules();
    }

    public static function getUserLogs($userId, $page = 1, $pageSize = 20)
    {
        $instance = self::getInstance();
        return $instance->model->getUserLogs($userId, $page, $pageSize);
    }
}

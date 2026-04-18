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
class NotificationController extends Controller
{
    private $notificationModel;

    public function __construct()
    {
        parent::__construct();
        $this->notificationModel = new NotificationModel();
    }

    public function index()
    {
        $this->checkLogin();
        
        $userId = $_SESSION['user_id'];
        $page = (int)Helper::get('page', 1);
        $pageSize = 20;
        
        $notifications = $this->notificationModel->getUserNotifications($userId, $page, $pageSize);
        $total = $this->notificationModel->countUserNotifications($userId);
        $unreadCount = $this->notificationModel->getUnreadCount($userId);
        
        $title = '消息通知';
        
        $this->render('notification/index', [
            'notifications' => $notifications,
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'unreadCount' => $unreadCount,
            'title' => $title
        ]);
    }

    public function getUnreadCount()
    {
        if (!isset($_SESSION['user_id'])) {
            Helper::jsonSuccess(['count' => 0]);
        }
        
        $count = $this->notificationModel->getUnreadCount($_SESSION['user_id']);
        Helper::jsonSuccess(['count' => $count]);
    }

    public function markRead()
    {
        $this->checkLogin();
        
        $id = (int)Helper::post('id');
        
        if (!$id) {
            Helper::jsonError('参数错误');
        }
        
        $result = $this->notificationModel->markAsRead($id, $_SESSION['user_id']);
        
        if ($result) {
            Helper::jsonSuccess(null, '已标记为已读');
        }
        
        Helper::jsonError('操作失败');
    }

    public function markAllRead()
    {
        $this->checkLogin();
        
        $result = $this->notificationModel->markAllAsRead($_SESSION['user_id']);
        
        if ($result !== false) {
            Helper::jsonSuccess(null, '已全部标记为已读');
        }
        
        Helper::jsonError('操作失败');
    }

    public function delete()
    {
        $this->checkLogin();
        
        $id = (int)Helper::post('id');
        
        if (!$id) {
            Helper::jsonError('参数错误');
        }
        
        $result = $this->notificationModel->deleteNotification($id, $_SESSION['user_id']);
        
        if ($result) {
            Helper::jsonSuccess(null, '删除成功');
        }
        
        Helper::jsonError('删除失败');
    }
}

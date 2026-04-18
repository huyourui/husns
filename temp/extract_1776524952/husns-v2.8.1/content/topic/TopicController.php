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
class TopicController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('admin');
    }
    
    public function index()
    {
        $this->checkAdmin();
        
        $topics = [];
        $page = 1;
        $totalPages = 1;
        $keyword = '';
        $status = '';
        $statDays = 7;
        
        try {
            $db = Database::getInstance();
            $statDays = (int)Setting::get('topic_stat_days', 7);
            $page = (int)Helper::get('page', 1);
            $keyword = trim(Helper::get('keyword', ''));
            $status = trim(Helper::get('status', ''));
            
            $startTime = time() - ($statDays * 86400);
            $sql = "SELECT content FROM __PREFIX__posts WHERE status = 1 AND created_at >= ?";
            $posts = $db->fetchAll($sql, [$startTime]);
            
            $allTopics = [];
            foreach ($posts as $post) {
                preg_match_all('/#([^#]+)#/', $post['content'], $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $topic) {
                        $topic = trim($topic);
                        if ($topic) {
                            if (!isset($allTopics[$topic])) {
                                $allTopics[$topic] = 0;
                            }
                            $allTopics[$topic]++;
                        }
                    }
                }
            }
            
            $managedTopics = $db->fetchAll("SELECT * FROM __PREFIX__topics");
            $managedMap = [];
            foreach ($managedTopics as $mt) {
                $managedMap[$mt['name']] = $mt;
            }
            
            $result = [];
            foreach ($allTopics as $name => $count) {
                if ($keyword !== '' && stripos($name, $keyword) === false) {
                    continue;
                }
                
                $managed = $managedMap[$name] ?? null;
                $isPinned = $managed ? (int)$managed['is_pinned'] : 0;
                $isBlocked = $managed ? (int)$managed['is_blocked'] : 0;
                $sortOrder = $managed ? (int)$managed['sort_order'] : 0;
                $createdAt = $managed ? $managed['created_at'] : time();
                
                if ($status === 'pinned' && !$isPinned) continue;
                if ($status === 'blocked' && !$isBlocked) continue;
                if ($status === 'normal' && ($isPinned || $isBlocked)) continue;
                
                $result[] = [
                    'id' => $managed ? $managed['id'] : null,
                    'name' => $name,
                    'count' => $count,
                    'is_pinned' => $isPinned,
                    'is_blocked' => $isBlocked,
                    'sort_order' => $sortOrder,
                    'created_at' => $createdAt
                ];
            }
            
            foreach ($managedMap as $name => $mt) {
                if (!isset($allTopics[$name])) {
                    if ($keyword !== '' && stripos($name, $keyword) === false) {
                        continue;
                    }
                    
                    $isPinned = (int)$mt['is_pinned'];
                    $isBlocked = (int)$mt['is_blocked'];
                    
                    if ($status === 'pinned' && !$isPinned) continue;
                    if ($status === 'blocked' && !$isBlocked) continue;
                    if ($status === 'normal' && ($isPinned || $isBlocked)) continue;
                    
                    $result[] = [
                        'id' => $mt['id'],
                        'name' => $name,
                        'count' => 0,
                        'is_pinned' => $isPinned,
                        'is_blocked' => $isBlocked,
                        'sort_order' => (int)$mt['sort_order'],
                        'created_at' => $mt['created_at']
                    ];
                }
            }
            
            usort($result, function($a, $b) {
                if ($a['is_pinned'] !== $b['is_pinned']) {
                    return $b['is_pinned'] - $a['is_pinned'];
                }
                if ($a['is_pinned'] && $b['is_pinned']) {
                    return $a['sort_order'] - $b['sort_order'];
                }
                return $b['count'] - $a['count'];
            });
            
            $total = count($result);
            $totalPages = max(1, ceil($total / 20));
            $offset = ($page - 1) * 20;
            $topics = array_slice($result, $offset, 20);
            
        } catch (Exception $e) {
            $topics = [];
        }
        
        $this->render('admin/topics', [
            'topics' => $topics,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'status' => $status,
            'statDays' => $statDays
        ]);
    }
    
    public function saveStatDays()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        $days = (int)Helper::post('days', 7);
        $days = max(1, min(365, $days));
        
        try {
            $db = Database::getInstance();
            $exists = $db->fetch("SELECT * FROM __PREFIX__settings WHERE `key` = 'topic_stat_days'");
            
            if ($exists) {
                $db->update('settings', ['value' => (string)$days], '`key` = ?', ['topic_stat_days']);
            } else {
                $db->insert('settings', [
                    'key' => 'topic_stat_days',
                    'value' => (string)$days,
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
            }
            
            Helper::jsonSuccess(null, '保存成功');
        } catch (Exception $e) {
            Helper::jsonError('保存失败');
        }
    }
    
    public function create()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        $name = trim(Helper::post('name'));
        $sortOrder = (int)Helper::post('sort_order', 0);
        
        if ($name === '') {
            Helper::jsonError('话题名称不能为空');
        }
        
        $name = preg_replace('/^#|#$/u', '', $name);
        
        try {
            $db = Database::getInstance();
            $existing = $db->fetch("SELECT * FROM __PREFIX__topics WHERE name = ?", [$name]);
            
            if ($existing) {
                $db->update('topics', [
                    'is_pinned' => 1,
                    'is_blocked' => 0,
                    'sort_order' => $sortOrder,
                    'updated_at' => time()
                ], 'id = ?', [$existing['id']]);
                
                Helper::jsonSuccess(null, '话题已存在，已设置为人工置顶');
            } else {
                $db->insert('topics', [
                    'name' => $name,
                    'is_pinned' => 1,
                    'is_blocked' => 0,
                    'sort_order' => $sortOrder,
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
                
                Helper::jsonSuccess(null, '添加成功');
            }
        } catch (Exception $e) {
            Helper::jsonError('操作失败');
        }
    }
    
    public function pin()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        $name = trim(Helper::post('name'));
        $id = (int)Helper::post('id', 0);
        $sortOrder = (int)Helper::post('sort_order', 0);
        
        if ($name === '') {
            Helper::jsonError('话题名称不能为空');
        }
        
        try {
            $db = Database::getInstance();
            $existing = $db->fetch("SELECT * FROM __PREFIX__topics WHERE name = ?", [$name]);
            
            if ($existing) {
                $db->update('topics', [
                    'is_pinned' => 1,
                    'is_blocked' => 0,
                    'sort_order' => $sortOrder,
                    'updated_at' => time()
                ], 'id = ?', [$existing['id']]);
            } else {
                $db->insert('topics', [
                    'name' => $name,
                    'is_pinned' => 1,
                    'is_blocked' => 0,
                    'sort_order' => $sortOrder,
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
            }
            
            Helper::jsonSuccess(null, '置顶成功');
        } catch (Exception $e) {
            Helper::jsonError('操作失败');
        }
    }
    
    public function unpin()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        $name = trim(Helper::post('name'));
        $id = (int)Helper::post('id', 0);
        
        if ($name === '') {
            Helper::jsonError('话题名称不能为空');
        }
        
        try {
            $db = Database::getInstance();
            $existing = $db->fetch("SELECT * FROM __PREFIX__topics WHERE name = ?", [$name]);
            
            if ($existing) {
                $db->update('topics', [
                    'is_pinned' => 0,
                    'sort_order' => 0,
                    'updated_at' => time()
                ], 'id = ?', [$existing['id']]);
            }
            
            Helper::jsonSuccess(null, '取消置顶成功');
        } catch (Exception $e) {
            Helper::jsonError('操作失败');
        }
    }
    
    public function block()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        $name = trim(Helper::post('name'));
        $id = (int)Helper::post('id', 0);
        
        if ($name === '') {
            Helper::jsonError('话题名称不能为空');
        }
        
        try {
            $db = Database::getInstance();
            $existing = $db->fetch("SELECT * FROM __PREFIX__topics WHERE name = ?", [$name]);
            
            if ($existing) {
                $db->update('topics', [
                    'is_blocked' => 1,
                    'is_pinned' => 0,
                    'updated_at' => time()
                ], 'id = ?', [$existing['id']]);
            } else {
                $db->insert('topics', [
                    'name' => $name,
                    'is_pinned' => 0,
                    'is_blocked' => 1,
                    'sort_order' => 0,
                    'created_at' => time(),
                    'updated_at' => time()
                ]);
            }
            
            Helper::jsonSuccess(null, '屏蔽成功');
        } catch (Exception $e) {
            Helper::jsonError('操作失败');
        }
    }
    
    public function unblock()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        $name = trim(Helper::post('name'));
        $id = (int)Helper::post('id', 0);
        
        if ($name === '') {
            Helper::jsonError('话题名称不能为空');
        }
        
        try {
            $db = Database::getInstance();
            $existing = $db->fetch("SELECT * FROM __PREFIX__topics WHERE name = ?", [$name]);
            
            if ($existing) {
                $db->update('topics', [
                    'is_blocked' => 0,
                    'updated_at' => time()
                ], 'id = ?', [$existing['id']]);
            }
            
            Helper::jsonSuccess(null, '取消屏蔽成功');
        } catch (Exception $e) {
            Helper::jsonError('操作失败');
        }
    }
    
    public function delete()
    {
        $this->checkAdmin();
        
        if (!Helper::verifyCsrf()) {
            Helper::jsonError('安全验证失败');
        }
        
        $id = (int)Helper::post('id');
        
        try {
            $db = Database::getInstance();
            $db->delete('topics', 'id = ?', [$id]);
            Helper::jsonSuccess(null, '删除成功');
        } catch (Exception $e) {
            Helper::jsonError('删除失败');
        }
    }
}

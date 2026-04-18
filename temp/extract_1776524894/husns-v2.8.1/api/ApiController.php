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

/**
 * API控制器
 * 
 * 提供RESTful API接口，支持：
 * - 用户认证（Token）
 * - 动态操作
 * - 评论互动
 * - 用户信息
 */
class ApiAuthController extends ApiController
{
    /**
     * 用户登录获取Token
     * 
     * POST /api/auth/login
     * 
     * @param string $username 用户名
     * @param string $password 密码
     * @param int $expires 有效期（天数，默认30天）
     */
    public function login()
    {
        $username = trim($this->input('username'));
        $password = $this->input('password');
        $expires = (int)$this->input('expires', 30);
        
        if (empty($username) || empty($password)) {
            $this->validationError([
                'username' => empty($username) ? '用户名不能为空' : null,
                'password' => empty($password) ? '密码不能为空' : null
            ]);
        }
        
        $userModel = new UserModel();
        $user = $userModel->findByUsername($username);
        
        if (!$user) {
            $user = $userModel->findByEmail($username);
        }
        
        if (!$user || !Security::verifyPassword($password, $user['password'])) {
            $this->error('用户名或密码错误', 1001, 401);
        }
        
        if ($user['status'] != 1) {
            $this->error('账号已被禁用', 1002, 403);
        }
        
        $banInfo = $userModel->getBanInfo($user['id']);
        if ($banInfo && $banInfo['type'] == 2) {
            $this->error('账号已被封禁：' . $banInfo['reason'], 1003, 403);
        }
        
        $token = $this->createToken($user['id'], $expires);
        
        $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expires * 86400,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'avatar' => $user['avatar'] ? Helper::uploadUrl($user['avatar']) : null,
                'is_admin' => (bool)$user['is_admin']
            ]
        ], '登录成功');
    }
    
    /**
     * 创建API Token
     * 
     * @param int $userId 用户ID
     * @param int $expiresDays 有效期天数
     * @return string
     */
    private function createToken($userId, $expiresDays = 30)
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = time() + ($expiresDays * 86400);
        
        $this->db->insert('api_tokens', [
            'user_id' => $userId,
            'token' => $token,
            'name' => 'API Token',
            'status' => 1,
            'expires_at' => $expiresAt,
            'created_at' => time()
        ]);
        
        return $token;
    }
    
    /**
     * 获取当前用户信息
     * 
     * GET /api/auth/me
     */
    public function me()
    {
        $this->requireAuth();
        
        $userModel = new UserModel();
        $user = $userModel->find($this->apiUser['id']);
        
        if (!$user) {
            $this->notFound('用户不存在');
        }
        
        $postModel = new PostModel();
        $postCount = $postModel->count('user_id = ? AND status = 1', [$user['id']]);
        
        $followerCount = $this->db->count('follows', 'follow_id = ?', [$user['id']]);
        $followingCount = $this->db->count('follows', 'user_id = ?', [$user['id']]);
        
        $this->success([
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'avatar' => $user['avatar'] ? Helper::uploadUrl($user['avatar']) : null,
            'bio' => $user['bio'],
            'points' => (int)$user['points'],
            'is_admin' => (bool)$user['is_admin'],
            'created_at' => (int)$user['created_at'],
            'stats' => [
                'posts' => $postCount,
                'followers' => $followerCount,
                'following' => $followingCount
            ]
        ]);
    }
    
    /**
     * 注销Token
     * 
     * POST /api/auth/logout
     */
    public function logout()
    {
        $this->requireAuth();
        
        if ($this->token) {
            $this->db->update('api_tokens', [
                'status' => 0
            ], 'token = ?', [$this->token]);
        }
        
        $this->success(null, '注销成功');
    }
    
    /**
     * 刷新Token
     * 
     * POST /api/auth/refresh
     */
    public function refresh()
    {
        $this->requireAuth();
        
        $expires = (int)$this->input('expires', 30);
        $newToken = $this->createToken($this->apiUser['id'], $expires);
        
        if ($this->token) {
            $this->db->update('api_tokens', [
                'status' => 0
            ], 'token = ?', [$this->token]);
        }
        
        $this->success([
            'token' => $newToken,
            'token_type' => 'Bearer',
            'expires_in' => $expires * 86400
        ], 'Token刷新成功');
    }
}

/**
 * 动态API控制器
 */
class ApiPostController extends ApiController
{
    private $postModel;
    private $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->postModel = new PostModel();
        $this->userModel = new UserModel();
    }
    
    /**
     * 获取动态列表
     * 
     * GET /api/posts
     */
    public function index()
    {
        $page = (int)$this->input('page', 1);
        $pageSize = min((int)$this->input('page_size', 20), 50);
        $tab = $this->input('tab', 'all');
        
        $userId = $this->apiUser ? $this->apiUser['id'] : null;
        
        $posts = $this->postModel->getTimeline($page, $pageSize, $userId, $tab);
        $total = $this->postModel->getTimelineCount($userId, $tab);
        
        $items = array_map(function($post) use ($userId) {
            return $this->formatPost($post, $userId);
        }, $posts);
        
        $this->paginate($items, $total, $page, $pageSize);
    }
    
    /**
     * 获取动态详情
     * 
     * GET /api/posts/{id}
     */
    public function show()
    {
        $id = (int)$this->input('id');
        
        if (!$id) {
            $this->validationError(['id' => '动态ID不能为空']);
        }
        
        $userId = $this->apiUser ? $this->apiUser['id'] : 0;
        $post = $this->postModel->getPost($id, $userId);
        
        if (!$post || $post['status'] != 1) {
            $this->notFound('动态不存在');
        }
        
        $this->success($this->formatPost($post, $userId));
    }
    
    /**
     * 发布动态
     * 
     * POST /api/posts
     */
    public function store()
    {
        $this->requireAuth();
        
        $content = trim($this->input('content'));
        
        if (empty($content)) {
            $this->validationError(['content' => '内容不能为空']);
        }
        
        if (mb_strlen($content, 'UTF-8') > Setting::getMaxPostLength()) {
            $this->validationError(['content' => '内容超过最大长度限制']);
        }
        
        $banInfo = $this->userModel->getBanInfo($this->apiUser['id']);
        if ($banInfo) {
            $this->error('您已被限制发布', 2001, 403);
        }
        
        $content = Security::xssClean($content);
        
        $postId = $this->postModel->publish([
            'user_id' => $this->apiUser['id'],
            'content' => $content,
            'images' => [],
            'attachments' => [],
            'videos' => []
        ]);
        
        if ($postId) {
            Point::change($this->apiUser['id'], 'publish_post', 'post', $postId);
            
            $post = $this->postModel->getPost($postId, $this->apiUser['id']);
            $this->success($this->formatPost($post, $this->apiUser['id']), '发布成功', 201);
        }
        
        $this->error('发布失败', 2002);
    }
    
    /**
     * 删除动态
     * 
     * DELETE /api/posts/{id}
     */
    public function destroy()
    {
        $this->requireAuth();
        
        $id = (int)$this->input('id');
        
        if (!$id) {
            $this->validationError(['id' => '动态ID不能为空']);
        }
        
        $isAdmin = $this->apiUser['is_admin'];
        $result = $this->postModel->deletePost($id, $this->apiUser['id'], $isAdmin);
        
        if ($result) {
            Point::change($this->apiUser['id'], 'delete_post', 'post', $id);
            $this->success(null, '删除成功');
        }
        
        $this->error('删除失败', 2003);
    }
    
    /**
     * 点赞动态
     * 
     * POST /api/posts/{id}/like
     */
    public function like()
    {
        $this->requireAuth();
        
        $id = (int)$this->input('id');
        
        if (!$id) {
            $this->validationError(['id' => '动态ID不能为空']);
        }
        
        $result = $this->postModel->like($id, $this->apiUser['id']);
        
        if ($result) {
            Point::change($this->apiUser['id'], 'like_post', 'post', $id);
            
            $post = $this->postModel->getPost($id, $this->apiUser['id']);
            $this->success(['likes' => $post['likes']]);
        }
        
        $this->error('已点赞过', 2004);
    }
    
    /**
     * 取消点赞
     * 
     * DELETE /api/posts/{id}/like
     */
    public function unlike()
    {
        $this->requireAuth();
        
        $id = (int)$this->input('id');
        
        if (!$id) {
            $this->validationError(['id' => '动态ID不能为空']);
        }
        
        $result = $this->postModel->unlike($id, $this->apiUser['id']);
        
        if ($result) {
            $post = $this->postModel->getPost($id, $this->apiUser['id']);
            $this->success(['likes' => $post['likes']]);
        }
        
        $this->error('取消点赞失败', 2005);
    }
    
    /**
     * 格式化动态数据
     */
    private function formatPost($post, $userId)
    {
        $isLiked = $userId ? $this->postModel->isLiked($post['id'], $userId) : false;
        
        return [
            'id' => (int)$post['id'],
            'user' => [
                'id' => (int)$post['user_id'],
                'username' => $post['username'],
                'avatar' => $post['avatar'] ? Helper::uploadUrl($post['avatar']) : null
            ],
            'content' => $post['content'],
            'images' => $post['images'] ?? [],
            'stats' => [
                'likes' => (int)$post['likes'],
                'comments' => (int)$post['comments'],
                'reposts' => (int)$post['reposts']
            ],
            'is_liked' => $isLiked,
            'is_pinned' => (bool)($post['is_pinned'] ?? 0),
            'is_featured' => (bool)($post['is_featured'] ?? 0),
            'created_at' => (int)$post['created_at'],
            'time_ago' => Helper::formatTime($post['created_at'])
        ];
    }
}

/**
 * 评论API控制器
 */
class ApiCommentController extends ApiController
{
    private $postModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->postModel = new PostModel();
    }
    
    /**
     * 获取评论列表
     * 
     * GET /api/posts/{post_id}/comments
     */
    public function index()
    {
        $postId = (int)$this->input('post_id');
        $page = (int)$this->input('page', 1);
        $pageSize = min((int)$this->input('page_size', 20), 50);
        
        if (!$postId) {
            $this->validationError(['post_id' => '动态ID不能为空']);
        }
        
        $comments = $this->postModel->getComments($postId, $page, $pageSize);
        
        $items = array_map(function($comment) {
            return $this->formatComment($comment);
        }, $comments);
        
        $total = $this->db->count('comments', 'post_id = ? AND status = 1 AND parent_id = 0', [$postId]);
        
        $this->paginate($items, $total, $page, $pageSize);
    }
    
    /**
     * 发表评论
     * 
     * POST /api/posts/{post_id}/comments
     */
    public function store()
    {
        $this->requireAuth();
        
        $postId = (int)$this->input('post_id');
        $content = trim($this->input('content'));
        $parentId = (int)$this->input('parent_id', 0);
        $replyToUserId = (int)$this->input('reply_to_user_id', 0);
        
        if (!$postId) {
            $this->validationError(['post_id' => '动态ID不能为空']);
        }
        
        if (empty($content)) {
            $this->validationError(['content' => '评论内容不能为空']);
        }
        
        if (mb_strlen($content, 'UTF-8') > Setting::getMaxCommentLength()) {
            $this->validationError(['content' => '评论内容超过最大长度限制']);
        }
        
        $content = Security::xssClean($content);
        
        $commentId = $this->db->insert('comments', [
            'post_id' => $postId,
            'user_id' => $this->apiUser['id'],
            'content' => $content,
            'parent_id' => $parentId,
            'reply_to_user_id' => $replyToUserId,
            'status' => 1,
            'ip' => Helper::getIp(),
            'created_at' => time()
        ]);
        
        if ($commentId) {
            Point::change($this->apiUser['id'], 'publish_comment', 'comment', $commentId);
            
            $this->db->query(
                "UPDATE __PREFIX__posts SET comments = comments + 1 WHERE id = ?",
                [$postId]
            );
            
            $comment = $this->db->fetch(
                "SELECT c.*, u.username, u.avatar 
                 FROM __PREFIX__comments c 
                 INNER JOIN __PREFIX__users u ON c.user_id = u.id 
                 WHERE c.id = ?",
                [$commentId]
            );
            
            $this->success($this->formatComment($comment), '评论成功', 201);
        }
        
        $this->error('评论失败', 3001);
    }
    
    /**
     * 格式化评论数据
     */
    private function formatComment($comment)
    {
        return [
            'id' => (int)$comment['id'],
            'user' => [
                'id' => (int)$comment['user_id'],
                'username' => $comment['username'],
                'avatar' => $comment['avatar'] ? Helper::uploadUrl($comment['avatar']) : null
            ],
            'content' => $comment['content'],
            'parent_id' => (int)$comment['parent_id'],
            'reply_to_user_id' => (int)($comment['reply_to_user_id'] ?? 0),
            'created_at' => (int)$comment['created_at'],
            'time_ago' => Helper::formatTime($comment['created_at'])
        ];
    }
}

/**
 * 用户API控制器
 */
class ApiUserController extends ApiController
{
    private $userModel;
    private $postModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->postModel = new PostModel();
    }
    
    /**
     * 获取用户信息
     * 
     * GET /api/users/{id}
     */
    public function show()
    {
        $id = (int)$this->input('id');
        
        if (!$id) {
            $this->validationError(['id' => '用户ID不能为空']);
        }
        
        $user = $this->userModel->find($id);
        
        if (!$user) {
            $this->notFound('用户不存在');
        }
        
        $postCount = $this->postModel->count('user_id = ? AND status = 1', [$user['id']]);
        $followerCount = $this->db->count('follows', 'follow_id = ?', [$user['id']]);
        $followingCount = $this->db->count('follows', 'user_id = ?', [$user['id']]);
        
        $this->success([
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'avatar' => $user['avatar'] ? Helper::uploadUrl($user['avatar']) : null,
            'bio' => $user['bio'],
            'points' => (int)$user['points'],
            'created_at' => (int)$user['created_at'],
            'stats' => [
                'posts' => $postCount,
                'followers' => $followerCount,
                'following' => $followingCount
            ]
        ]);
    }
    
    /**
     * 获取用户动态列表
     * 
     * GET /api/users/{id}/posts
     */
    public function posts()
    {
        $id = (int)$this->input('id');
        $page = (int)$this->input('page', 1);
        $pageSize = min((int)$this->input('page_size', 20), 50);
        
        if (!$id) {
            $this->validationError(['id' => '用户ID不能为空']);
        }
        
        $result = $this->postModel->getUserPosts($id, $page, $pageSize);
        
        $userId = $this->apiUser ? $this->apiUser['id'] : 0;
        $items = array_map(function($post) use ($userId) {
            $isLiked = $userId ? $this->postModel->isLiked($post['id'], $userId) : false;
            return [
                'id' => (int)$post['id'],
                'content' => $post['content'],
                'images' => $post['images'] ?? [],
                'stats' => [
                    'likes' => (int)$post['likes'],
                    'comments' => (int)$post['comments'],
                    'reposts' => (int)$post['reposts']
                ],
                'is_liked' => $isLiked,
                'created_at' => (int)$post['created_at'],
                'time_ago' => Helper::formatTime($post['created_at'])
            ];
        }, $result['items']);
        
        $this->paginate($items, $result['total'], $page, $pageSize);
    }
    
    /**
     * 关注用户
     * 
     * POST /api/users/{id}/follow
     */
    public function follow()
    {
        $this->requireAuth();
        
        $id = (int)$this->input('id');
        
        if (!$id) {
            $this->validationError(['id' => '用户ID不能为空']);
        }
        
        if ($id == $this->apiUser['id']) {
            $this->error('不能关注自己', 4001);
        }
        
        $targetUser = $this->userModel->find($id);
        if (!$targetUser) {
            $this->notFound('用户不存在');
        }
        
        $exists = $this->db->fetch(
            "SELECT * FROM __PREFIX__follows WHERE user_id = ? AND follow_id = ?",
            [$this->apiUser['id'], $id]
        );
        
        if ($exists) {
            $this->error('已关注该用户', 4002);
        }
        
        $this->db->insert('follows', [
            'user_id' => $this->apiUser['id'],
            'follow_id' => $id,
            'created_at' => time()
        ]);
        
        Point::change($this->apiUser['id'], 'follow_user', 'user', $id);
        Point::change($id, 'be_followed', 'user', $this->apiUser['id']);
        
        $this->success(null, '关注成功');
    }
    
    /**
     * 取消关注
     * 
     * DELETE /api/users/{id}/follow
     */
    public function unfollow()
    {
        $this->requireAuth();
        
        $id = (int)$this->input('id');
        
        if (!$id) {
            $this->validationError(['id' => '用户ID不能为空']);
        }
        
        $result = $this->db->delete('follows', 'user_id = ? AND follow_id = ?', [$this->apiUser['id'], $id]);
        
        if ($result) {
            $this->success(null, '取消关注成功');
        }
        
        $this->error('取消关注失败', 4003);
    }
}

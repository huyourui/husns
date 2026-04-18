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

namespace Repository;

use Core\BaseRepository;
use Security;

/**
 * 用户数据仓库
 * 
 * 处理用户相关的数据操作，包括注册、登录、关注等
 * 
 * @package Repository
 */
class UserRepository extends BaseRepository
{
    /**
     * 表名
     * @var string
     */
    protected $table = 'users';

    /**
     * 根据用户名查找用户
     *
     * @param string $username 用户名
     * @return array|null
     */
    public function findByUsername(string $username): ?array
    {
        $result = $this->db->fetch("SELECT * FROM __PREFIX__users WHERE username = ?", [$username]);
        return $result ?: null;
    }

    /**
     * 根据邮箱查找用户
     *
     * @param string $email 邮箱
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $result = $this->db->fetch("SELECT * FROM __PREFIX__users WHERE email = ?", [$email]);
        return $result ?: null;
    }

    /**
     * 注册用户
     *
     * @param array $data 用户数据
     * @return int 用户ID
     */
    public function register(array $data): int
    {
        $data['password'] = Security::hashPassword($data['password']);
        $data['created_at'] = time();
        $data['updated_at'] = time();
        $data['status'] = 1;
        $data['is_admin'] = 0;
        
        return (int)$this->insert($data);
    }

    /**
     * 用户登录验证
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return array|null 成功返回用户信息，失败返回null
     */
    public function login(string $username, string $password): ?array
    {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return null;
        }

        if ($user['status'] != 1) {
            return null;
        }

        if (!Security::verifyPassword($password, $user['password'])) {
            return null;
        }

        return $user;
    }

    /**
     * 更新用户资料
     *
     * @param int $id 用户ID
     * @param array $data 更新数据
     * @return int 影响行数
     */
    public function updateProfile(int $id, array $data): int
    {
        $data['updated_at'] = time();
        return $this->update($id, $data);
    }

    /**
     * 更新密码
     *
     * @param int $id 用户ID
     * @param string $oldPassword 旧密码
     * @param string $newPassword 新密码
     * @return bool
     */
    public function updatePassword(int $id, string $oldPassword, string $newPassword): bool
    {
        $user = $this->find($id);
        
        if (!$user) {
            return false;
        }

        if (!Security::verifyPassword($oldPassword, $user['password'])) {
            return false;
        }

        return $this->update($id, [
            'password' => Security::hashPassword($newPassword),
            'updated_at' => time()
        ]) > 0;
    }

    /**
     * 获取关注统计
     *
     * @param int $userId 用户ID
     * @return array
     */
    public function getFollowCount(int $userId): array
    {
        $following = $this->db->count('follows', 'user_id = ?', [$userId]);
        $followers = $this->db->count('follows', 'follow_id = ?', [$userId]);
        
        return [
            'following' => $following,
            'followers' => $followers
        ];
    }

    /**
     * 检查是否已关注
     *
     * @param int $userId 用户ID
     * @param int $targetId 目标用户ID
     * @return bool
     */
    public function isFollowing(int $userId, int $targetId): bool
    {
        return $this->db->count('follows', 'user_id = ? AND follow_id = ?', [$userId, $targetId]) > 0;
    }

    /**
     * 关注用户
     *
     * @param int $userId 用户ID
     * @param int $targetId 目标用户ID
     * @return bool
     */
    public function follow(int $userId, int $targetId): bool
    {
        if ($userId == $targetId) {
            return false;
        }

        if ($this->isFollowing($userId, $targetId)) {
            return false;
        }

        return $this->db->insert('follows', [
            'user_id' => $userId,
            'follow_id' => $targetId,
            'created_at' => time()
        ]) > 0;
    }

    /**
     * 取消关注
     *
     * @param int $userId 用户ID
     * @param int $targetId 目标用户ID
     * @return bool
     */
    public function unfollow(int $userId, int $targetId): bool
    {
        return $this->db->delete('follows', 'user_id = ? AND follow_id = ?', [$userId, $targetId]) > 0;
    }

    /**
     * 获取封禁信息
     *
     * @param int $userId 用户ID
     * @return array|null
     */
    public function getBanInfo(int $userId): ?array
    {
        $user = $this->find($userId);
        
        if (!$user) {
            return null;
        }
        
        $banType = isset($user['ban_type']) ? (int)$user['ban_type'] : 0;
        $banUntil = isset($user['ban_until']) ? (int)$user['ban_until'] : 0;
        $banReason = isset($user['ban_reason']) ? $user['ban_reason'] : '';
        
        if ($banType === 0) {
            return null;
        }
        
        if ($banType === 1 && $banUntil > 0 && $banUntil < time()) {
            return null;
        }
        
        return [
            'type' => $banType,
            'until' => $banUntil,
            'reason' => $banReason,
            'type_text' => $banType === 1 ? '禁言' : '封禁',
            'until_text' => $banUntil === 0 ? '永久' : date('Y-m-d H:i', $banUntil)
        ];
    }

    /**
     * 检查用户是否被封禁
     *
     * @param int $userId 用户ID
     * @return bool
     */
    public function isBanned(int $userId): bool
    {
        $user = $this->find($userId);
        
        if (!$user) {
            return false;
        }
        
        $banType = isset($user['ban_type']) ? (int)$user['ban_type'] : 0;
        $banUntil = isset($user['ban_until']) ? (int)$user['ban_until'] : 0;
        
        if ($banType === 0) {
            return false;
        }
        
        if ($banType === 1) {
            if ($banUntil === 0) {
                return true;
            }
            if ($banUntil > time()) {
                return true;
            }
            return false;
        }
        
        if ($banType === 2) {
            return true;
        }
        
        return false;
    }

    /**
     * 搜索用户
     *
     * @param string $keyword 关键词
     * @param int $limit 数量限制
     * @return array
     */
    public function searchUsers(string $keyword, int $limit = 10): array
    {
        $keyword = '%' . $keyword . '%';
        $sql = "SELECT id, username, avatar FROM __PREFIX__users 
                WHERE status = 1 AND username LIKE ? 
                ORDER BY id DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$keyword, $limit]);
    }

    /**
     * 搜索关注的人
     *
     * @param int $userId 用户ID
     * @param string $keyword 关键词
     * @param int $limit 数量限制
     * @return array
     */
    public function searchFollowing(int $userId, string $keyword, int $limit = 10): array
    {
        $keyword = '%' . $keyword . '%';
        $sql = "SELECT u.id, u.username, u.avatar 
                FROM __PREFIX__users u 
                INNER JOIN __PREFIX__follows f ON u.id = f.follow_id 
                WHERE f.user_id = ? AND u.status = 1 AND u.username LIKE ? 
                ORDER BY u.id DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$userId, $keyword, $limit]);
    }

    /**
     * 获取粉丝列表
     *
     * @param int $userId 用户ID
     * @return array
     */
    public function getFollowers(int $userId): array
    {
        $sql = "SELECT u.* FROM __PREFIX__users u 
                INNER JOIN __PREFIX__follows f ON u.id = f.user_id 
                WHERE f.follow_id = ?";
        return $this->db->fetchAll($sql, [$userId]);
    }

    /**
     * 获取关注列表
     *
     * @param int $userId 用户ID
     * @return array
     */
    public function getFollowing(int $userId): array
    {
        $sql = "SELECT u.* FROM __PREFIX__users u 
                INNER JOIN __PREFIX__follows f ON u.id = f.follow_id 
                WHERE f.user_id = ?";
        return $this->db->fetchAll($sql, [$userId]);
    }
}

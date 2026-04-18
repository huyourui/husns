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
class UserModel extends Model
{
    protected $table = 'users';

    public function findByUsername($username)
    {
        return $this->db->fetch("SELECT * FROM __PREFIX__users WHERE username = ?", [$username]);
    }

    public function findByEmail($email)
    {
        return $this->db->fetch("SELECT * FROM __PREFIX__users WHERE email = ?", [$email]);
    }

    public function register($data)
    {
        $data['password'] = Security::hashPassword($data['password']);
        $data['created_at'] = time();
        $data['updated_at'] = time();
        $data['status'] = 1;
        $data['is_admin'] = 0;
        
        return $this->insert($data);
    }

    public function login($username, $password)
    {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return false;
        }

        if ($user['status'] != 1) {
            return false;
        }

        if (!Security::verifyPassword($password, $user['password'])) {
            return false;
        }

        return $user;
    }

    public function updateProfile($id, $data)
    {
        $data['updated_at'] = time();
        return $this->update($id, $data);
    }

    public function updatePassword($id, $oldPassword, $newPassword)
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
        ]);
    }

    public function getFollowers($userId)
    {
        $sql = "SELECT u.* FROM __PREFIX__users u 
                INNER JOIN __PREFIX__follows f ON u.id = f.user_id 
                WHERE f.follow_id = ?";
        return $this->db->fetchAll($sql, [$userId]);
    }

    public function getFollowing($userId)
    {
        $sql = "SELECT u.* FROM __PREFIX__users u 
                INNER JOIN __PREFIX__follows f ON u.id = f.follow_id 
                WHERE f.user_id = ?";
        return $this->db->fetchAll($sql, [$userId]);
    }

    public function getFollowCount($userId)
    {
        $following = $this->db->count('follows', 'user_id = ?', [$userId]);
        $followers = $this->db->count('follows', 'follow_id = ?', [$userId]);
        
        return [
            'following' => $following,
            'followers' => $followers
        ];
    }

    public function isFollowing($userId, $targetId)
    {
        return $this->db->count('follows', 'user_id = ? AND follow_id = ?', [$userId, $targetId]) > 0;
    }

    public function follow($userId, $targetId)
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
        ]);
    }

    public function unfollow($userId, $targetId)
    {
        return $this->db->delete('follows', 'user_id = ? AND follow_id = ?', [$userId, $targetId]);
    }

    public function isBanned($userId)
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

    public function getBanInfo($userId)
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

    public function searchUsers($keyword, $limit = 10)
    {
        $keyword = '%' . $keyword . '%';
        $sql = "SELECT id, username, avatar FROM __PREFIX__users 
                WHERE status = 1 AND username LIKE ? 
                ORDER BY id DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$keyword, $limit]);
    }

    public function searchFollowing($userId, $keyword, $limit = 10)
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
}

<?php

class FavoriteModel extends Model
{
    protected $table = 'favorites';

    public function add($postId, $userId)
    {
        $exists = $this->db->fetch(
            "SELECT * FROM __PREFIX__favorites WHERE post_id = ? AND user_id = ?",
            [$postId, $userId]
        );
        
        if ($exists) {
            return false;
        }
        
        return $this->insert([
            'post_id' => $postId,
            'user_id' => $userId,
            'created_at' => time()
        ]);
    }

    public function remove($postId, $userId)
    {
        return $this->db->delete('favorites', 'post_id = ? AND user_id = ?', [$postId, $userId]);
    }

    public function isFavorited($postId, $userId)
    {
        return $this->db->count('favorites', 'post_id = ? AND user_id = ?', [$postId, $userId]) > 0;
    }

    public function getUserFavorites($userId, $page = 1, $pageSize = 10)
    {
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT f.created_at as favorited_at, p.*, u.username, u.avatar 
                FROM __PREFIX__favorites f 
                INNER JOIN __PREFIX__posts p ON f.post_id = p.id 
                INNER JOIN __PREFIX__users u ON p.user_id = u.id 
                WHERE f.user_id = ? AND p.status = 1 
                ORDER BY f.created_at DESC 
                LIMIT {$offset}, {$pageSize}";
        
        $posts = $this->db->fetchAll($sql, [$userId]);
        
        foreach ($posts as &$post) {
            $post['images'] = is_array($post['images']) ? $post['images'] : ($post['images'] ? json_decode($post['images'], true) : []);
            $post['attachments'] = is_array($post['attachments']) ? $post['attachments'] : ($post['attachments'] ? json_decode($post['attachments'], true) : []);
            $post['videos'] = is_array($post['videos']) ? $post['videos'] : ($post['videos'] ? json_decode($post['videos'], true) : []);
            $post['time_ago'] = Helper::formatTime($post['created_at']);
            $post['favorited_time_ago'] = Helper::formatTime($post['favorited_at']);
            $post['content'] = Security::escape($post['content']);
            $post['content'] = preg_replace('/#(.+?)#/', '<a href="' . Helper::url('post/topic?keyword=$1') . '">#$1#</a>', $post['content']);
            $post['content'] = preg_replace('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]+)(?=\s|:|$|\/\/)/u', '<a href="' . Helper::url('user/profile?username=$1') . '">@$1</a>', $post['content']);
        }
        
        return $posts;
    }

    public function countUserFavorites($userId)
    {
        $sql = "SELECT COUNT(*) FROM __PREFIX__favorites f 
                INNER JOIN __PREFIX__posts p ON f.post_id = p.id 
                WHERE f.user_id = ? AND p.status = 1";
        return (int)$this->db->fetchColumn($sql, [$userId]);
    }

    public function getFavoriteCount($postId)
    {
        return $this->db->count('favorites', 'post_id = ?', [$postId]);
    }
}

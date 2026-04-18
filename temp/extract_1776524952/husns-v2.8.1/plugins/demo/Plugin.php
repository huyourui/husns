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
namespace Plugin\demo;

use Hook;

class Plugin
{
    public function __construct()
    {
        Hook::register('post_after_publish', [$this, 'onPostPublish']);
        Hook::register('user_login', [$this, 'onUserLogin']);
        Hook::register('head', [$this, 'addHeadContent']);
    }

    public function onPostPublish($data)
    {
        error_log("Demo Plugin: New post published - ID: " . $data['id']);
        return $data;
    }

    public function onUserLogin($user)
    {
        error_log("Demo Plugin: User logged in - " . $user['username']);
        return $user;
    }

    public function addHeadContent($content)
    {
        return $content . '<meta name="demo-plugin" content="active">';
    }
}

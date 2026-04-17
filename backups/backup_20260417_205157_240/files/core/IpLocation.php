<?php

class IpLocation
{
    private static $cache = [];
    private static $cacheFile = null;
    private static $initialized = false;

    private static function init()
    {
        if (self::$initialized) {
            return;
        }
        
        self::$cacheFile = ROOT_PATH . 'cache/ip_location.json';
        
        if (file_exists(self::$cacheFile)) {
            $content = file_get_contents(self::$cacheFile);
            self::$cache = json_decode($content, true) ?: [];
        }
        
        self::$initialized = true;
    }

    public static function getLocation($ip)
    {
        if (empty($ip)) {
            return '';
        }
        
        if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
            return '本地网络';
        }
        
        self::init();
        
        if (isset(self::$cache[$ip])) {
            return self::$cache[$ip];
        }
        
        $location = self::fetchFromApi($ip);
        
        self::$cache[$ip] = $location;
        self::saveCache();
        
        return $location;
    }

    private static function fetchFromApi($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return '';
        }
        
        $url = 'http://ip-api.com/json/' . $ip . '?lang=zh-CN&fields=status,country,regionName,city';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || empty($response)) {
            return self::fetchFromBackupApi($ip);
        }
        
        $data = json_decode($response, true);
        
        if (!$data || ($data['status'] ?? '') !== 'success') {
            return '';
        }
        
        $parts = [];
        
        if (!empty($data['country']) && $data['country'] !== 'China') {
            $parts[] = $data['country'];
        }
        
        if (!empty($data['regionName'])) {
            $parts[] = $data['regionName'];
        }
        
        if (!empty($data['city']) && $data['city'] !== $data['regionName']) {
            $parts[] = $data['city'];
        }
        
        return implode(' ', $parts);
    }

    private static function fetchFromBackupApi($ip)
    {
        $url = 'https://ip.taobao.com/outGetIpInfo?ip=' . $ip . '&accessKey=alibaba-inc';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || empty($response)) {
            return '';
        }
        
        $data = json_decode($response, true);
        
        if (!$data || ($data['code'] ?? 0) !== 0) {
            return '';
        }
        
        $info = $data['data'] ?? [];
        
        $parts = [];
        
        if (!empty($info['country']) && $info['country'] !== '中国') {
            $parts[] = $info['country'];
        }
        
        if (!empty($info['region']) && $info['region'] !== $info['country']) {
            $parts[] = $info['region'];
        }
        
        if (!empty($info['city']) && $info['city'] !== $info['region']) {
            $parts[] = $info['city'];
        }
        
        return implode(' ', $parts);
    }

    private static function saveCache()
    {
        $cacheDir = dirname(self::$cacheFile);
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        
        $content = json_encode(self::$cache, JSON_UNESCAPED_UNICODE);
        file_put_contents(self::$cacheFile, $content);
    }

    public static function getBatchLocations(array $ips)
    {
        $results = [];
        
        foreach ($ips as $ip) {
            $results[$ip] = self::getLocation($ip);
        }
        
        return $results;
    }
}

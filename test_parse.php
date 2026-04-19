<?php
require_once __DIR__ . '/index.php';

// 测试 parseContent
$content = '测试 #话题# @admin http://huyourui.com';
$topicUrl = Helper::url('mobile/topic?keyword=$1');
$userUrl = Helper::url('mobile/user?username=$1');

$result = Helper::parseContent($content, $topicUrl, $userUrl);

echo "原始内容: " . $content . "\n\n";
echo "解析结果:\n";
echo $result . "\n\n";
echo "HTML实体编码后的结果:\n";
echo htmlspecialchars($result) . "\n";

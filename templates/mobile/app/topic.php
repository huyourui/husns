<?php
$pageType = 'topic';
$bodyClass = 'topic-page';
$hideTabBar = false;
$showBack = true;
$headerTitle = '#' . $keyword . '#';
$pageData = ['topic-keyword' => $keyword];
$this->setLayout('app');
?>
<div class="post-list" id="postList"></div>

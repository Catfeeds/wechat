<?php
/**
 * 2016 慕马科技
 * visited http://wx.51muma.com/ for more details.
 */
defined('IN_IA') or exit('Access Denied');
if (!empty($_W['uid'])) {
	header('Location: '.url('account/display'));
	exit;
}
$settings = $_W['setting'];
$copyright = $settings['copyright'];
$copyright['slides'] = iunserializer($copyright['slides']);
if (isset($copyright['showhomepage']) && empty($copyright['showhomepage'])) {
	header("Location: ".url('user/login'));
	exit;
}
load()->model('article');
$notices = article_notice_home();
$news = article_news_home();

$them = $_GET[a];
if($them){
	template('account/'.$them);
}else{
	template('account/welcome');
}

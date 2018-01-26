<?php
/**
 * 
 * 
 */
load()->func('tpl');
$_W['token'] = token();
$session = json_decode(base64_decode($_GPC['__ad_session']), true);
if(is_array($session)) {
	$user = pdo_get('sj_news_ad_account',array('id'=>$session['ad_id']));
	if(is_array($user) && $session['ad_hash'] == md5($user['password'] . $user['salt'])) {
		$_W['uniacid'] = $user['uniacid'];
		$_W['ad_id'] = $user['id'];
		$_W['ad_type'] = $user['type'];
		$_W['ad_username'] = $user['username'];
		$_W['province'] = $user['province'];
		$_W['city'] = $user['city'];
		$_W['district'] = $user['district'];
	} else {
		isetcookie('__ad_session', false, -100);
	}
	unset($user);
}
unset($session);
$_W['template'] = 'default';
if(!empty($_W['setting']['basic']['template'])) {
	$_W['template'] = $_W['setting']['basic']['template'];
}

load()->func('compat.biz');

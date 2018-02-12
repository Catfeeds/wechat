<?php
/**
 * 
 * 
 */
load()->func('tpl');
$_W['token'] = token();
$session = json_decode(base64_decode($_GPC['__servicer_session']), true);
if(is_array($session)) {
	$servicer = pdo_get('servicer_user',array('id'=>$session['servicer_id']));
	if(is_array($servicer) && $session['servicer_hash'] == md5($servicer['password'] . $servicer['salt'])) {
		$_W['uniacid'] = $servicer['uniacid'];
		$_W['servicer_id'] = $servicer['id'];
		$_W['province'] = $servicer['province'];
		$_W['city'] = $servicer['city'];
		$_W['district'] = $servicer['district'];
		$_W['admin'] = $servicer['username'];
	} else {
		isetcookie('__servicer_session', false, -100);
	}
	unset($servicer);
}
unset($session);
$_W['template'] = 'default';
if(!empty($_W['setting']['basic']['template'])) {
	$_W['template'] = $_W['setting']['basic']['template'];
}
load()->func('compat.biz');

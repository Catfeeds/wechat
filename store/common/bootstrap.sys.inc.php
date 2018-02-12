<?php
/**
 * 
 * 
 */
load()->func('tpl');
$_W['token'] = token();
$session = json_decode(base64_decode($_GPC['__store_session']), true);
if(is_array($session)) {
	$store = pdo_get('store_list',array('id'=>$session['store_id']));
	if(is_array($store) && $session['store_hash'] == md5($store['password'] . $store['salt'])) {
		$_W['uniacid'] = $store['uniacid'];
		$_W['store_id'] = $store['id'];
		$_W['store_type'] = $store['type'];
		$_W['store_manager'] = $store['username'];
		$_W['store_title'] = $store['title'];
		//铃声设置
		$_W['system']['voice'] = pdo_get('store_voice_config',array(
			'uniacid' => $_W['uniacid'],
			'store_id' => $_W['store_id'],
			'store_type' => $_W['store_type']
		));
	} else {
		isetcookie('__store_session', false, -100);
	}
	unset($user);
}
unset($session);
$_W['template'] = 'default';
if(!empty($_W['setting']['basic']['template'])) {
	$_W['template'] = $_W['setting']['basic']['template'];
}
load()->func('compat.biz');

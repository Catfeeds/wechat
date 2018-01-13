<?php
/**
 * 
 * 
 */
load()->func('tpl');
$_W['token'] = token();
$session = json_decode(base64_decode($_GPC['__account_session']), true);
if(is_array($session)) {
	$account = pdo_get('account_user',array('id'=>$session['account_id']));
	if(is_array($account) && $session['account_hash'] == md5($account['password'] . $account['salt'])) {
		$_W['uniacid'] = $account['uniacid'];
		$_W['account_id'] = $account['id'];
		$_W['account_type'] = $account['type'];
		$_W['account_manager'] = $account['username'];
		$_W['account_title'] = $account['title'];
		//铃声设置

		$_W['system']['voice'] = pdo_get('account_voice_config',array(
			'uniacid' => $_W['uniacid'],
			'account_id' => $_W['account_id']
		));

	} else {
		isetcookie('__account_session', false, -100);
	}
	unset($account);
}
unset($session);
$_W['template'] = 'default';
if(!empty($_W['setting']['basic']['template'])) {
	$_W['template'] = $_W['setting']['basic']['template'];
}

load()->func('compat.biz');

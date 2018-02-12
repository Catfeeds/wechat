<?php
/**
 * 
 * 
 */
load()->func('tpl');
$_W['token'] = token();
$session = json_decode(base64_decode($_GPC['__vapp_session']), true);
if(is_array($session)) {
	$vapp = pdo_get('vapp_member',array('uid'=>$session['user_id']));
	if(is_array($vapp) && $session['vapp_hash'] == md5($vapp['password'] . $vapp['salt'])) {
		$_W['uniacid'] = $vapp['uniacid'];
		$_W['user_id'] = $vapp['uid'];
		$_W['admin'] = $vapp['mobile'];
        if(!empty($_W['nickname'])){
            $_W['admin'] = $vapp['nickname'];
        }
		if(!empty($_W['realname'])){
		    $_W['admin'] = $vapp['realname'];
        }
	} else {
		isetcookie('__vapp_session', false, -100);
	}
	unset($vapp);
}
unset($session);
$_W['template'] = 'default';
if(!empty($_W['setting']['basic']['template'])) {
	$_W['template'] = $_W['setting']['basic']['template'];
}
load()->func('compat.biz');

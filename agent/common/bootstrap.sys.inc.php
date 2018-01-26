<?php
/**
 * 
 * 
 */
load()->func('tpl');
$_W['token'] = token();
$session = json_decode(base64_decode($_GPC['__agent_session']), true);
if(is_array($session)) {
	$agent = pdo_fetch("SELECT a.*,b.`nickname`,b.realname FROM ".tablename('agent_user')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE a.id='{$session['agent_id']}'");
	if(is_array($agent) && $session['agent_hash'] == md5("AGENT_LOGIN_AUTH_KEY")) {
		$_W['uniacid'] = $agent['uniacid'];
		$_W['agent_id'] = $agent['id'];
		$_W['province'] = $agent['province'];
		$_W['city'] = $agent['city'];
		$_W['district'] = $agent['district'];
		$_W['admin'] = !empty($agent['realname'])?$agent['realname']:$agent['nickname'];
	} else {
		isetcookie('__agent_session', false, -100);
	}
	unset($agent);
}

unset($session);
$_W['template'] = 'default';
if(!empty($_W['setting']['basic']['template'])) {
	$_W['template'] = $_W['setting']['basic']['template'];
}
load()->func('compat.biz');

<?php
/**
 * 
 * 
 */
defined('IN_IA') or exit('Access Denied');
define('IN_GW', true);
if($_W['isajax']) {
	$username = trim($_GPC['username']);
	if(empty($username)){
		message('请输入用户名','','error');
	}
	$password = $_GPC['password'];
	if(empty($password)){
		message('请输入密码','','error');
	}
	if (!empty($_W['setting']['copyright']['verifycode'])) {
		$verify = trim($_GPC['verify']);
		if(empty($verify)) {
			message('请输入验证码','','error');
		}
		$verify_status = checkcaptcha($verify);
		if (!$verify_status) {
			message('输入验证码错误','','error');
		}
	}
	//获取商家信息
	$user_info = pdo_get('store_list',array('username'=>$username));
	if(empty($user_info)){
		message('用户名不存在','','error');
	}
	if($user_info['is_check'] != CHECK_PASS){
		message('您的账号已被平台拒绝，请咨询平台，了解详情','','error');
	}
	load()->model('user');
	if($user_info['password'] != user_hash($password,$user_info['salt'])){
		message('用户名或密码错误','','error');
	}
	$cookie = array();
	$cookie['store_id'] = $user_info['id'];
	$cookie['store_hash'] = md5($user_info['password'] . $user_info['salt']);
	$session = base64_encode(json_encode($cookie));
	isetcookie('__store_session', $session, !empty($_GPC['rember']) ? 7 * 86400 : 0, true);
	message('登录成功',url('account/display'),'success');
}
template('user/login');


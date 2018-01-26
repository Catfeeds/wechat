<?php
/**
 * 
 * 
 */
defined('IN_IA') or exit('Access Denied');
$uniacid = floor(trim($_GPC['i']));
if(empty($uniacid)){
    message('未选择公众号','','error');
}
if($_W['isajax']) {
    $info = pdo_get('mc_code_login',array(
        'uniacid' => $uniacid,
        'code' => trim($_GPC['code'])
    ));
    if(empty($info) || !is_array($info)){
        message('登录信息不存在','','not_exist');
    }
    if($info['is_cancel'] == CANCEL_YES){
        message('用户已取消','','cancel_yes');
    }
    if(!empty($info['uid']) && $info['is_cancel'] == CANCEL_NO){
        $agent = pdo_get('agent_user',array(
            'uniacid' => $uniacid,
            'uid' => $info['uid']
        ));
        if (empty($agent)) {
            message('代理信息不存在', '', 'error');
        }
        if($agent['is_check'] != CHECK_PASS){
            message('账号已被禁用','','no_qualification');
        }
        $cookie = array();
        $cookie['agent_id'] = $agent['id'];
        $cookie['agent_hash'] = md5("AGENT_LOGIN_AUTH_KEY");
        $session = base64_encode(json_encode($cookie));
        isetcookie('__agent_session', $session, 0, true);
        message('登录成功',url('account/display'),'success');
    }
    message('其它错误','','error');
}
$code = uniqid('login_');
$status = pdo_insert('mc_code_login',array(
    'uniacid' => $uniacid,
    'code' => $code,
    'createtime' => TIMESTAMP
));
if(!$status){
    message('二维码生成失败','','error');
}
$link = "{$_W['siteroot']}app/index.php?i={$uniacid}&c=auth&a=codelogin&do=display&code={$code}";
$link = urlencode($link);
$img = "{$_W['siteroot']}app/index.php?i={$uniacid}&c=mc&a=poster&do=image&ps={$link}";
template('user/login');


<?php
/* 扫码登录 */
defined('IN_IA') or exit('Access Denied');
$do = empty($_GPC['do'])?'display':trim($_GPC['do']);
if($do == 'display'){
    $_W['page']['title'] = '扫码登录';
    checkauth();
    if($_W['isajax']){
        $data = array(
            'uid' => $_W['member']['uid'],
            'updatetime' => TIMESTAMP,
            'ip' => CLIENT_IP,
            'is_cancel' => floor(trim($_GPC['is_cancel'])) == CANCEL_NO?CANCEL_NO:CANCEL_YES
        );
        if($data['is_cancel'] == CANCEL_YES){
            $tip = "登录取消";
        }else{
            $tip = "扫码登录";
        }
        $status = pdo_update('mc_code_login',$data,array(
            'uniacid' => $_W['uniacid'],
            'code' => trim($_GPC['code'])
        ));
        if(!$status){
            message($tip.'失败','','error');
        }
        message($tip.'成功',url('agent/display/display'),'success');
    }
}
template('auth/codelogin');
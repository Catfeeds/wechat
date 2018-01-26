<?php
defined('IN_IA') or exit('Access Denied');
$dos = array('send');
$do = in_array($do, $dos) ? $do : 'send';
load()->func('check');
load()->func('sms');
if($do == 'send'){
    //发送短信
    if($_W['isajax']){
        $mobile = trim($_GPC['mobile']);
        if(empty($mobile)){
            message('请输入手机号','','error');
        }
        if(!check_mobile($mobile)){
            message('手机号码格式错误','','error');
        }
        $result = sms_code($mobile,"用户");
        if(is_error($result)){
            message($result['message'],'','error');
        }
        message('发送成功','','success');
    }
    message('请求方式错误','','error');
}

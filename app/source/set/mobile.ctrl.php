<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/18
 * Time: 21:31
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
if($do == 'display'){
    if($_W['isajax']){
        load()->model('mc');
        load()->model('user');
        load()->func('check');
        $member_info = mc_fetch($_W['member']['uid']);
        if(empty($member_info) || !is_array($member_info)){
            message('会员信息不存在','','error');
        }
        $mobile = trim($_GPC['mobile']);
        if(empty($mobile)){
            message('请输入手机号码','','error');
        }
        if(!check_mobile($mobile)){
            message('手机号格式错误','','error');
        }
        $code = trim($_GPC['code']);
        if(empty($code)){
            message('请输入验证码','','error');
        }
        $result = checkMobileCode($mobile,$code);
        if(is_error($result)){
            message($result['message'],'','error');
        }
        $is_used = pdo_fetch("SELECT * FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid!='{$_W['member']['uid']}' AND mobile='{$mobile}'");
        if(!empty($is_used) && is_array($is_used)){
            message('手机号已被占用，请更换手机号','','error');
        }
        $status = mc_update($_W['member']['uid'], array(
            'mobile' => $mobile,
            'updatetime' => TIMESTAMP
        ));
        if(!$status){
            message('手机号绑定失败','','error');
        }
        message('手机号绑定成功',referer(),'success');
    }
}elseif($do == 'send'){
    if($_W['isajax']){
        load()->func('sms');
        load()->func('check');
        $mobile = trim($_GPC['mobile']);
        if(!check_mobile($mobile)){
            message('请输入正确的手机号','','error');
        }
        $code = random(6,true);
        sms_code($mobile,$code);
    }
    message('请求方式错误','','error');
}
template('set/mobile');
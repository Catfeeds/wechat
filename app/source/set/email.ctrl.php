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
        $email = trim($_GPC['email']);
        if(empty($email)){
            message('请输入邮箱地址','','error');
        }
        if(!check_email($email)){
            message('邮箱格式错误','','error');
        }
        $is_used = pdo_fetch("SELECT * FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid!='{$_W['member']['uid']}' AND email='{$email}'");
        if(!empty($is_used) && is_array($is_used)){
            message('邮箱已被占用，请更换邮箱','','error');
        }
        $status = mc_update($_W['member']['uid'], array(
            'email' => $email,
            'updatetime' => TIMESTAMP
        ));
        if(!$status){
            message('邮箱绑定失败','','error');
        }
        message('邮箱绑定成功',referer(),'success');
    }
}
template('set/email');
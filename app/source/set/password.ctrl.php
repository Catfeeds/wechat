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
        $member_info = mc_fetch($_W['member']['uid']);
        if(empty($member_info) || !is_array($member_info)){
            message('会员信息不存在','','error');
        }
        $salt = $member_info['salt'];
        $password = $_GPC['password'];
        $re_password = $_GPC['re_password'];
        if(empty($password)){
            message('请输入密码','','error');
        }
        if(mb_strlen($password,'utf-8') < 6){
            message('密码不能少于6位','','error');
        }
        if(empty($re_password)){
            message('请再次输入密码','','error');
        }
        if($re_password != $password){
            message('两次密码输入不一致','','error');
        }
        $status = mc_update($_W['member']['uid'], array(
            'password' => md5($password . $member_info['salt'] . $_W['config']['setting']['authkey']),
            'updatetime' => TIMESTAMP
        ));
        if(!$status){
            message('重置密码失败','','error');
        }
        message('密码已重置',referer(),'success');
    }
}
template('set/password');
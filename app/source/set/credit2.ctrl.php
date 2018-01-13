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
        $salt = $member_info['salt'];
        $pay_credit2_password = $_GPC['pay_credit2_password'];
        $re_pay_credit2_password = $_GPC['re_pay_credit2_password'];
        if(empty($pay_credit2_password)){
            message('请输入密码','','error');
        }
        if(!check_n_number($pay_credit2_password,6)) {
            message('密码必须是6位数字','','error');
        }
        if(empty($re_pay_credit2_password)){
            message('请再次输入密码','','error');
        }
        if($re_pay_credit2_password != $pay_credit2_password){
            message('两次密码输入不一致','','error');
        }
        $status = mc_update($_W['member']['uid'], array(
            'pay_credit2_password' => md5($pay_credit2_password . $member_info['salt'] . $_W['config']['setting']['authkey']),
            'updatetime' => TIMESTAMP
        ));
        if(!$status){
            message('支付密码设置失败','','error');
        }
        message('支付密码设置成功',referer(),'success');
    }
}
template('set/credit2');
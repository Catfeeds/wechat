<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/6/5
 * Time: 18:44
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
load()->func('check');
if($do == 'display'){
    if($_W['isajax']){
        $data = array(
            'realname' => trim($_GPC['realname']),
            'bank_no' => trim($_GPC['bank_no']),
            'bank_address' => trim($_GPC['bank_address'])
        );
        $re_bank_no = trim($_GPC['re_bank_no']);
       if(empty($data['realname'])){
            message('请输入您的真实姓名','','error');
       }
        if(empty($data['bank_no'])){
            message('请输入您的银行卡号','','error');
        }
        if(empty($re_bank_no)){
            message('请再次输入您的银行卡号','','error');
        }
        if($data['bank_no'] != $re_bank_no){
            message('两次输入的银行卡号不一致','','error');
        }
        if(empty($data['bank_address'])){
            message('请输入开户行','','error');
        }
        $status = pdo_update('mc_members',array(
            'withdraw_account' => iserializer($data),
            'updatetime'=>TIMESTAMP
        ),array(
            'uniacid'=>$_W['uniacid'],
            'uid'=>$_W['member']['uid']
        ));
        if(!$status){
            message('保存失败','','error');
        }
        message('保存成功','','success');
    }
    $member = pdo_get('mc_members',array(
        'uniacid'=>$_W['uniacid'],
        'uid'=>$_W['member']['uid']
    ));
    if(check_data($member)){
        if(!empty($member['withdraw_account'])){
            $withdraw_account = iunserializer($member['withdraw_account']);
        }
    }
}
template('set/withdraw');
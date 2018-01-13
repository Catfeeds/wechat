<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/9/24
 * Time: 13:34
 */
checkauth();
load()->model('mc');
load()->func('notice');
$member = pdo_get('mc_members',array(
    'uniacid' => $_W['uniacid'],
    'uid' => $_W['member']['uid']
));
if($op == 'display'){
    //系统配置
    $config = pdo_get('sj_news_config',array(
        'uniacid' => $_W['uniacid']
    ));
    $setting = array();
    if(!empty($config['setting'])){
        $setting = iunserializer($config['setting']);
    }
    if($_W['isajax']){
        $credit1 = floatval(trim($_GPC['credit1']));
        if(!is_numeric($credit1) || $credit1 <= 0){
            message('积分数量输入不正确','','error');
        }
        if($credit1 > $member['credit1']){
            message('积分数量不足','','error');
        }
        mc_credit_update($member['uid'],'credit1',-$credit1,array($member['uid'],"积分兑换礼物，扣除{$credit1}积分"));
        notice_send_simple_text($member['uid'],"积分兑换成功，扣除{$credit1}积分");
        message('兑换成功',referer(),'success');
    }
}
include $this->template('user');
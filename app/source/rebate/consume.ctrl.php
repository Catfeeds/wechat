<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/5/29
 * Time: 9:17
 */
load()->func('check');
defined('IN_IA') or exit('Access Denied');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
$credit6 = pdo_fetchcolumn("SELECT credit6 FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'");
$credit6 = empty($credit6)?0:$credit6;
$credit7 = pdo_fetchcolumn("SELECT credit7 FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'");
$credit7 = empty($credit7)?0:$credit7;
$order_type = floor(trim($_GPC['order_type']));
if(empty($order_type)){
    $order_type = 1;
}
$order_type_desc = array(
    1 => '商城下单',
    2 => '店内消费'
);
$title = $order_type_desc[$order_type];
$list = array();
$psize = 20;
$pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;

//商城消费金额
$money_1 = pdo_fetchcolumn("SELECT SUM(pay_price) FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND pay_status=".PAY_YES);
$money_2 = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND pay_status=".PAY_YES);
$money_3 = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_person')." WHERE uniacid='{$_W['uniacid']}' AND pay_uid='{$_W['member']['uid']}' AND pay_status=".PAY_YES);
$total_money = $money_1+$money_2+$money_3;

switch($order_type){
    case ORDER_TYPE_OTO_GOODS:
        $list = pdo_fetchall("SELECT order_no,createtime,pay_price AS money FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND pay_status=".PAY_YES." ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
        break;
    case ORDER_TYPE_OFFLINE:
        $list = pdo_fetchall("SELECT * FROM ".tablename('order_offline')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND pay_status=".PAY_YES." ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
        break;
    case ORDER_TYPE_PERSON:
        $list = pdo_fetchall("SELECT * FROM ".tablename('order_person')." WHERE uniacid='{$_W['uniacid']}' AND pay_uid='{$_W['member']['uid']}' AND pay_status=".PAY_YES." ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
        break;
}
if(check_data($list)){
    foreach($list as $k => &$v){
        $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
    }
}
if($_W['isajax']){
    if(check_data($list)){
        message($list,'','success');
    }
    message('没有更多消费记录','','error');
}
template('rebate/consume');
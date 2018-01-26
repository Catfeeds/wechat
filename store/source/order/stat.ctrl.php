<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/5/27
 * Time: 23:56
 */
load()->model('store');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
if($do == 'display'){
    $balance_rate = 0;
    $config = pdo_get('distribution_config',array(
        'uniacid' => $_W['uniacid']
    ));
    if(!empty($config) && is_array($config)){
        $setting = iunserializer($config['setting']);
        if(isset($setting['store']['balance_rate'])){
            $balance_rate =  floatval($setting['store']['balance_rate']*0.01);
        }
    }
    $order_pay = pdo_fetchall("SELECT order_status,SUM(pay_total_price) AS pay FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$store['id']}' GROUP BY order_status",array(),'order_status');
    $li1 = empty($order_pay[ORDER_STATUS_NOT_DELIVER]['pay'])?0:$order_pay[ORDER_STATUS_NOT_DELIVER]['pay'];
    $li2 = empty($order_pay[ORDER_STATUS_NOT_PAY]['pay'])?0:$order_pay[ORDER_STATUS_NOT_PAY]['pay'];
    $li3 = empty($order_pay[ORDER_STATUS_NOT_CONFIRM]['pay'])?0:$order_pay[ORDER_STATUS_NOT_CONFIRM]['pay'];
    $li4 = empty($order_pay[ORDER_STATUS_RETURN]['pay'])?0:$order_pay[ORDER_STATUS_RETURN]['pay'];
    $li5 = empty($order_pay[ORDER_STATUS_COMPLETE]['pay'])?0:$order_pay[ORDER_STATUS_COMPLETE]['pay'];
    $li6 = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('store_balance_apply')." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$store['id']}' AND status=".IS_STATUS);
    if(empty($li6)){
        $li6 = 0;
    }
    $li7 = 0;
    $total_order_pay = pdo_fetchcolumn("SELECT SUM(pay_total_price) FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$store['id']}' AND pay_status=".PAY_YES);
    $total_offline_pay = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$store['id']}' AND pay_status=".PAY_YES);
    $li7 = $total_order_pay+$total_offline_pay;
    if(empty($li7)){
        $li7 = 0;
    }
    $li7 = $li7*$balance_rate;

    $li8 = $li7-$li6;
    $li9 = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('store_balance_apply')." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$store['id']}' AND status=".NO_STATUS);
    if(empty($li9)){
        $li9 = 0;
    }
}
template('order/stat');
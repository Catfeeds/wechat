<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/7/16
 * Time: 1:32
 */
header("Content-Type:text/html;charset=UTF-8");
require_once '../framework/bootstrap.inc.php';
require_once 'libs/global.func.php';
load()->func('check');
load()->func('notice');
load()->func('logging');
load()->model('pay');
load()->model('mc');
load()->model('user');
$order = pdo_get('fangyuanbao_user_order',array(
    'uniacid'=>7,
    'id' => 30
));
$setting = iunserializer(pdo_fetchcolumn("SELECT setting FROM ".tablename('fangyuanbao_old_config')." WHERE uniacid='7'"));
$user_data = array(
    'product_key' => $order['product_key'],
    'price' => $setting['product'][$order['product_key']]['price']
);
$product_name = array(
    1 => 'A套餐',
    2 => 'B套餐',
    3 => 'C套餐'
);
$relation = pdo_fetchcolumn("SELECT relation FROM ".tablename('mc_members')." WHERE uniacid='{$order['uniacid']}' AND uid='{$order['uid']}'");
if(!empty($relation)){
    $parents_ids = explode(SPLIT_RELATION,$relation);
    if(!empty($parents_ids) && is_array($parents_ids)){
        foreach($parents_ids as $k => $parent_uid){
            if($parent_uid == 0 || $k > 2){
                break;
            }
            $parent = pdo_get('fangyuanbao_user',array(
                'uniacid' => $order['uniacid'],
                'uid' => $parent_uid
            ));
            if($order['is_up'] == IS_STATUS){
                //升级只和直推有关
                if(!check_data($parent)){
                    return false;
                }
                if($parent['product_key'] == 3 && $k == 0){
                    //最高套餐
                    $rebate_money = $order['pay_price']*$setting['up_rate']*0.01;
                    if($rebate_money > 0){
                        $update_parent = pdo_query("UPDATE ".tablename('mc_members')." SET credit3=credit3+{$rebate_money} WHERE uniacid='{$order['uniacid']}' AND uid='{$parent_uid}'");
                        if(!$update_parent){
                            return false;
                        }
                        $insert_log = pdo_insert('fangyuanbao_user_rebate',array(
                            'uniacid' => $order['uniacid'],
                            'uid' => $parent_uid,
                            'buy_uid' => $order['uid'],
                            'order_no' => $order['order_no'],
                            'money' => $rebate_money
                        ));
                        if(!$insert_log){
                            return false;
                        }
                        load()->model('mc');
                        mc_credit_update($parent_uid,'credit3',$rebate_money,array($parent_uid,"会员{$insert_log['buy_uid']}缴纳服务费，赠送您:{$rebate_money}元，订单号：{$order['order_no']}"));
                        echo "发送{$rebate_money}元到{$parent_uid}<br>";
                        notice_send_simple_text($parent_uid,"恭喜您收到服务推广{$rebate_money}积分，请在您的个人中心查收");
                    }
                }else{
                    return false;
                }
                break;
            }else{  //购买
                //判断上级
                if(($k > 0 && $parent['product_key'] <3)){
                    break;
                }
                //判断当前套餐
                if(($order['product_key'] == 1 && $k >0) || ($order['product_key'] == 2 && $k > 1)){
                    break;
                }

                if($parent['product_key'] == 1 && $k == 0){
                    $count = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_user_rebate')." WHERE uniacid='{$order['uniacid']}' AND uid='{$parent_uid}'");
                    if($count >= $setting['product'][1]['num']){
                        return false;
                    }
                }
                $rebate_rate = pow(0.01*$setting['pay_rate'],($k+1));
                $rebate_money = min($parent['price'],$order['pay_price'])*$rebate_rate;
                if($rebate_money > 0){
                    $insert_log = pdo_insert('fangyuanbao_user_rebate',array(
                        'uniacid' => $order['uniacid'],
                        'uid' => $parent_uid,
                        'buy_uid' => $order['uid'],
                        'order_no' => $order['order_no'],
                        'money' => $rebate_money,
                        'createtime' => TIMESTAMP
                    ));
                    if(!$insert_log){
                        return false;
                    }
                    mc_credit_update($parent_uid,'credit3',$rebate_money,array($parent_uid,"会员{$insert_log['buy_uid']}缴纳服务费，赠送您:{$rebate_money}元，订单号：{$order['order_no']}"));
                    echo "发送{$rebate_money}元到{$parent_uid}<br>";
                    notice_send_simple_text($parent_uid,"恭喜您收到服务推广{$rebate_money}积分，请在您的个人中心查收");
                }
            }
        }
    }
}
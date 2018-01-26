<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/5/28
 * Time: 12:46
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
load()->func('check');
//核实商家信息
$store_id = floor(trim($_GPC['store_id']));
$store_info = array();
if(!empty($store_id)){
    $store_info = pdo_get('store_list',array(
        'uniacid'=>$_W['uniacid'],
        'saler_uid'=>$_W['member']['uid'],
        'id'=>$store_id
    ));
}
if($do == 'display'){
    $stores = pdo_fetchall("SELECT * FROM ".tablename('store_list')." WHERE uniacid='{$_W['uniacid']}' AND saler_uid='{$_W['member']['uid']}'",array(),'id');
    if(!check_data($stores)){
        message('您还不是商家，不能进入','','error');
    }
    if(!empty($store_id) && !check_data($store_info)){
        message('商家信息不存在','','error');
    }
}else{
    if(!check_data($store_info)){
        message('商家信息不存在','','error');
    }
    if($do == 'order'){
        $payMethodArrSpan = array(
            1 => '<span class="label label-default">余额</span>',
            2 => '<span class="label label-info">微信</span>',
            3 => '<span class="label label-warning">支付宝</span>',
            4 => '<span class="label label-success">银行卡</span>',
            5 => '<span class="label label-warning">微信</span>',
            6 => '<span class="label label-danger">货到</span>'
        );
        $psize = 20;
        $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
        $list = pdo_fetchall("SELECT a.*,b.nickname FROM ".tablename('order_offline')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE a.uniacid='{$_W['uniacid']}' AND a.store_id ='{$store_info['id']}' ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['pay_method'] = $payMethodArrSpan[$v['pay_method']];
                $v['label'] = $v['pay_status'] == PAY_YES?'label-success':'label-danger';
                $v['label_text'] = $v['pay_status'] == PAY_YES?'已支付':'未支付';
                $v['createtime'] = date('Y-m-d H:i',$v['createtime']);
            }
        }
        if($_W['isajax']){
            if(!empty($list) && is_array($list)){
                message($list,'','success');
            }
            message('没有获取到更多订单','','error');
        }
    }elseif($do == 'withdraw_record'){
        $withdraw_method = array(
            1 => '支付宝',
            2 => '银行卡',
            3 => '微信'
        );
        $psize = 20;
        $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
        $list = pdo_fetchall("SELECT * FROM ".tablename('store_balance_apply')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['label'] = $v['status'] == STORE_BALANCE_STATUS_YES?'label-success':'label-danger';
                $v['label_text'] = $v['status'] == STORE_BALANCE_STATUS_YES?'已结算':'未结算';
                $v['createtime'] = date('Y-m-d H:i',$v['createtime']);
                $v['withdraw_method'] = $withdraw_method[$v['withdraw_method']];
            }
        }
        if($_W['isajax']){
            if(!empty($list) && is_array($list)){
                message($list,'','success');
            }
            message('没有获取到更多结算记录','','error');
        }
    }elseif($do == 'withdraw_apply'){
        $member = pdo_get('mc_members',array(
            'uniacid'=>$_W['uniacid'],
            'uid'=>$_W['member']['uid']
        ));
        $withdraw_account = array();
        if(check_data($member)){
            if(!empty($member['withdraw_account'])){
                $withdraw_account = iunserializer($member['withdraw_account']);
            }
        }
        if(empty($withdraw_account)){
            message('请先设置提现账户',url('set/withdraw/display'),'error');
        }
        //获取平台设置的结算比例
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

        //已成功提现的佣金
        $total_withdraw_success = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('store_balance_apply')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND status=".IS_STATUS);
        if(empty($total_withdraw_success)){
            $total_withdraw_success = 0;
        }
        //提现中的佣金
        $total_withdraw_deal = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('store_balance_apply')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND status=".NO_STATUS);
        if(empty($total_withdraw_deal)){
            $total_withdraw_deal = 0;
        }
        //总的可结算金额
        $total_withdraw_money = 0;

        //2017年12月31之前的收入
        $total_order_pay_1 = pdo_fetchcolumn("SELECT SUM(pay_total_price) FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND createtime<=1514735999 AND pay_status=".PAY_YES);
        $total_offline_pay_1 = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND createtime<=1514735999 AND pay_status=".PAY_YES);
        $total_withdraw_money += (($total_order_pay_1 + $total_offline_pay_1)*0.9);

        //2017年12月31之后的收入
        $total_order_pay_2 = pdo_fetchcolumn("SELECT SUM(pay_total_price) FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND createtime>1514735999 AND pay_status=".PAY_YES);
        $total_offline_pay_2 = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND createtime>1514735999 AND pay_status=".PAY_YES);
        $total_withdraw_money += (($total_order_pay_2 + $total_offline_pay_2)*$balance_rate);

        //总的提现金额，2017年12月31日之前，时间戳：1514735999
        if(empty($total_withdraw_money)){
            $total_withdraw_money = 0;
        }




        $can_withdraw_money = $total_withdraw_money - ($total_withdraw_success+$total_withdraw_deal);
        if($_W['isajax']){
            $data = array(
                'uniacid' => $_W['uniacid'],
                'store_id' => $store_info['id'],
                'store_type' => $store_info['type'],
                'createtime' => TIMESTAMP,
                'username' => trim($_GPC['username']),
                'tel' => trim($_GPC['tel']),
                'money' => floatval(trim($_GPC['money'])),
                'withdraw_method' => STORE_BALANCE_TYPE_WECHAT
            );
            $error = array(
                'username' => '请输入姓名',
                'tel' => '请输入电话',
                'money' => '请输入付款金额'
            );
            foreach($error as $k => $message){
                if(empty($data[$k])){
                    message($message,'','error');
                }
            }
            if($data['money'] <= 0){
                message('提现金额错误','','error');
            }
            if($balance_rate > 0){
                if($data['money'] > $can_withdraw_money){
                    message('提现金额不能超过可提现金额','','error');
                }
                $status = pdo_insert('store_balance_apply',$data);
                if(!$status){
                    message('提交失败','','error');
                }
                message('提交成功，请耐心等待审核',referer(),'success');
            }
            message('平台未设置结算比例，不能提现','','error');
        }
    }elseif($do == 'income'){
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
        $order_pay = pdo_fetchall("SELECT order_status,SUM(pay_total_price) AS pay FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' GROUP BY order_status",array(),'order_status');
        $li1 = empty($order_pay[ORDER_STATUS_NOT_DELIVER]['pay'])?0:$order_pay[ORDER_STATUS_NOT_DELIVER]['pay'];
        $li2 = empty($order_pay[ORDER_STATUS_NOT_PAY]['pay'])?0:$order_pay[ORDER_STATUS_NOT_PAY]['pay'];
        $li3 = empty($order_pay[ORDER_STATUS_NOT_CONFIRM]['pay'])?0:$order_pay[ORDER_STATUS_NOT_CONFIRM]['pay'];
        $li4 = empty($order_pay[ORDER_STATUS_RETURN]['pay'])?0:$order_pay[ORDER_STATUS_RETURN]['pay'];
        $li5 = empty($order_pay[ORDER_STATUS_COMPLETE]['pay'])?0:$order_pay[ORDER_STATUS_COMPLETE]['pay'];
        $li6 = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('store_balance_apply')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND status=".IS_STATUS);
        if(empty($li6)){
            $li6 = 0;
        }


        //已成功提现的佣金
        $total_withdraw_success = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('store_balance_apply')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND status=".IS_STATUS);
        if(empty($total_withdraw_success)){
            $total_withdraw_success = 0;
        }
        //提现中的佣金
        $total_withdraw_deal = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('store_balance_apply')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND status=".NO_STATUS);
        if(empty($total_withdraw_deal)){
            $total_withdraw_deal = 0;
        }
        //总的可结算金额
        $total_withdraw_money = 0;

        //2017年12月31之前的收入
        $total_order_pay_1 = pdo_fetchcolumn("SELECT SUM(pay_total_price) FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND createtime<=1514735999 AND pay_status=".PAY_YES);
        $total_offline_pay_1 = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND createtime<=1514735999 AND pay_status=".PAY_YES);
        $total_withdraw_money += (($total_order_pay_1 + $total_offline_pay_1)*0.9);

        //2017年12月31之后的收入
        $total_order_pay_2 = pdo_fetchcolumn("SELECT SUM(pay_total_price) FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND createtime>1514735999 AND pay_status=".PAY_YES);
        $total_offline_pay_2 = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND createtime>1514735999 AND pay_status=".PAY_YES);
        $total_withdraw_money += (($total_order_pay_2 + $total_offline_pay_2)*$balance_rate);

        //总的提现金额，2017年12月31日之前，时间戳：1514735999
        if(empty($total_withdraw_money)){
            $total_withdraw_money = 0;
        }
        $li7 = $total_withdraw_money;

        //可提现的金额

        $li8 = $li7-$li6;
        $li9 = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('store_balance_apply')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store_info['id']}' AND status=".NO_STATUS);
        if(empty($li9)){
            $li9 = 0;
        }

    }
}
template('mc/balance');
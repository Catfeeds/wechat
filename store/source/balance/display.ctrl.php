<?php
load()->model('store');
load()->func('check');
if(empty($do)){$do = 'display';}
if($do == 'display'){
    $psize = 50;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $starttime = empty($_GPC['createtime']['start']) ? strtotime('-90 days') : strtotime($_GPC['createtime']['start']);
    $endtime = empty($_GPC['createtime']['end']) ? TIMESTAMP + 86399 : strtotime($_GPC['createtime']['end']) + 86399;
    $status = $_GPC['status'];
    $list = StoreModel::getStoreBalanceApplyList($starttime,$endtime,$status,$pindex,$psize);
    $total = StoreModel::getStoreBalanceApplyCount($starttime,$endtime,$status);
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);
}elseif($do == 'apply'){
    //核实商家信息
    $store = pdo_get('store_list',array(
        'uniacid' => $_W['uniacid'],
        'id' => $_W['store_id']
    ));
    if(!check_data($store)){
        message('商家信息不存在','','error');
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
    $total_withdraw_success = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('store_balance_apply')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store['id']}' AND status=".IS_STATUS);
    if(empty($total_withdraw_success)){
        $total_withdraw_success = 0;
    }
    //提现中的佣金
    $total_withdraw_deal = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('store_balance_apply')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store['id']}' AND status=".NO_STATUS);
    if(empty($total_withdraw_deal)){
        $total_withdraw_deal = 0;
    }
    //总的可结算金额
    $total_withdraw_money = 0;

    //2017年12月31之前的收入
    $total_order_pay_1 = pdo_fetchcolumn("SELECT SUM(pay_total_price) FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store['id']}' AND createtime<=1514735999 AND pay_status=".PAY_YES);
    $total_offline_pay_1 = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store['id']}' AND createtime<=1514735999 AND pay_status=".PAY_YES);
    $total_withdraw_money += (($total_order_pay_1 + $total_offline_pay_1)*0.9);

    //2017年12月31之后的收入
    $total_order_pay_2 = pdo_fetchcolumn("SELECT SUM(pay_total_price) FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store['id']}' AND createtime>1514735999 AND pay_status=".PAY_YES);
    $total_offline_pay_2 = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." WHERE uniacid='{$_W['uniacid']}' AND store_id ='{$store['id']}' AND createtime>1514735999 AND pay_status=".PAY_YES);
    $total_withdraw_money += (($total_order_pay_2 + $total_offline_pay_2)*$balance_rate);

    //总的提现金额，2017年12月31日之前，时间戳：1514735999
    if(empty($total_withdraw_money)){
        $total_withdraw_money = 0;
    }



    $can_withdraw_money = $total_withdraw_money - ($total_withdraw_success+$total_withdraw_deal);



    $list = StoreModel::getStoreBalanceAccountList();
    if($_W['isajax']){
        $id = floor(trim($_GPC['account_id']));
        if(empty($id)){
            message('请选择结算账户','','error');
        }
        $account = StoreModel::getStoreBalanceAccountInfo($id);
        if(empty($account) || !is_array($account)){
            message('结算账户不存在','','error');
        }
        $money = doubleval(trim($_GPC['money']));
        if($money <= 0){
            message('结算金额错误','','error');
        }
        if($balance_rate <= 0){
            message('平台未设置结算比例，暂不能提现','','error');
        }

        $data = array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $_W['store_id'],
            'store_type' => $_W['store_type'],
            'username' => $account['username'],
            'tel' => $account['tel'],
            'money' => $money,
            'withdraw_method' => $account['method'],
            'info' => iserializer($account['info']),
            'createtime' => TIMESTAMP
        );
        if($data['money'] > $can_withdraw_money){
            message('提现金额不能超过可提现金额','','error');
        }
        $status = StoreModel::insertStoreBalanceApplyInfo($data);
        if(!$status){
            message('申请提交失败','','error');
        }
        message('提交成功，请耐心等待审核',referer(),'success');
    }
}
template('balance/display');
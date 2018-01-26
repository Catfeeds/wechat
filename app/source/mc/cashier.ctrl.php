<?php
/* 收银台 */
defined('IN_IA') or exit('Access Denied');
load()->func('check');
if(empty($do)){
    $do = 'display';
}
if($do == 'display'){
    $fyb_user = pdo_get('fangyuanbao_user',array(
        'uniacid' => $_W['uniacid'],
        'uid' => $_W['member']['uid']
    ));
    if(!check_data($fyb_user)){
        message('未购买服务费，不能使用','','error');
    }
    $member = pdo_fetch("SELECT * FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'");
    if(empty($member['city']) || empty($member['province']) ||empty($member['district'])){
        message('请先设置所在地',url('set/location/display'),'error');
    }
    $tip = "二维码收款";
    load()->model('mc');
    $member = mc_fetch($_W['member']['uid']);
    $id = floor(trim($_GPC['id']));
    $link = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=mc&a=cashier&do=pay&cashier_uid={$_W['member']['uid']}&auth=".payPersonAuthEncode($_W['member']['uid'],$_W['uniacid']);
    $link = urlencode($link);
    $img = url('mc/poster/image')."&ps={$link}";
}elseif($do == 'pay'){  //付款界面
    $member = pdo_fetch("SELECT * FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'");
    if(empty($member['city']) || empty($member['province']) ||empty($member['district'])){
        message('请先设置所在地',url('set/location/display'),'error');
    }
    $cashier_uid = floor(trim($_GPC['cashier_uid']));
    if($cashier_uid == $_W['member']['uid']){
        message('请勿向自己付款','','error');
    }
    $goods_id = floor(trim($_GPC['goods_id']));
    if(!empty($goods_id)){
        $goods = pdo_get('old_goods',array(
            'uniacid'=>$_W['uniacid'],
            'id'=>$goods_id,
            'uid'=>$cashier_uid
        ));
        if(check_data($goods)){
            $goods['thumbs'] = json_decode($goods['thumbs'],true);
        }
    }
    if(!payPersonAuthCheck($_W['member']['uid'],$cashier_uid,$_W['uniacid'],$_GPC['auth'])){
        message('安全验证失败，无法支付','','error');
    }
    load()->model('mc');
    $cashier = mc_fetch($_GPC['cashier_uid']);
    if(empty($cashier)){
        message('收款人信息不存在','','error');
    }
    $tip = "付款";
    if($_W['isajax']){
        $order_no = generateOrderSnByBuyTodayTradeCount();
        $data = array(
            'uniacid' => $_W['uniacid'],
            'pay_uid' => $_W['member']['uid'],
            'cashier_uid' => $cashier['uid'],
            'old_goods_id' => $goods_id,
            'order_no' => $order_no,
            'money' => floatval(trim($_GPC['money'])),
            'note' => $_GPC['note'],
            'createtime' => TIMESTAMP
        );
        if(!is_numeric($data['money']) || $data['money'] <= 0){
            message('金额输入有误，请重新输入','','error');
        }
        pdo_begin();
        $status = pdo_insert('order_person',$data);
        if(!$status){
            pdo_rollback();
            message('订单生成失败','','error');
        }
        $insert_order_id = pdo_insertid();
        if(!$insert_order_id){
            pdo_rollback();
            message('订单生成失败','','error');
        }
        //插入支付记录
        $pay_log_data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'order_ids' => $insert_order_id,
            'out_trade_no' => $order_no,
            'pay_price' => $data['money'],
            'order_type' => ORDER_TYPE_PERSON,
            'createtime' => TIMESTAMP
        );
        $status = pdo_insert('pay_log',$pay_log_data);
        if(!$status){
            pdo_rollback();
            message('支付信息，提交失败','','error');
        }
        $insert_pay_log_id = pdo_insertid();
        if(!$insert_pay_log_id){
            pdo_rollback();
            message('支付信息，提交失败','','error');
        }
        //提交事务
        pdo_commit();
        message('提交成功，正在跳转',url('mc/pay/display',array('id'=>$insert_pay_log_id)),'success');
    }
}elseif($do == 'push'){
    $member = pdo_fetch("SELECT * FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'");
    if(empty($member['city']) || empty($member['province']) ||empty($member['district'])){
        message('请先设置所在地',url('set/location/display'),'error');
    }
    //再次支付
    $order_no = generateOrderSnByBuyTodayTradeCount();
    $id = floor(trim($_GPC['id']));
    $order_info = pdo_get('order_person',array(
        'uniacid' => $_W['uniacid'],
        'pay_uid' => $_W['member']['uid'],
        'id' => $id
    ));
    if(empty($order_info) || !is_array($order_info)){
        message('订单信息不存在','','error');
    }
    if($order_info['pay_status'] == PAY_YES){
        message('您已经支付成功，请勿重复支付','','error');
    }
    //更改订单编号
    $update_order_no = pdo_update('order_person',array('order_no' => $order_no,'createtime' => TIMESTAMP),array(
        'uniacid' => $_W['uniacid'],
        'pay_uid' => $_W['member']['uid'],
        'id' => $id
    ));
    if(!$update_order_no){
        message('订单号更新失败','','error');
    }

    //插入支付记录
    $pay_log_data = array(
        'uniacid' => $order_info['uniacid'],
        'uid' => $order_info['pay_uid'],
        'order_ids' => $order_info['id'],
        'out_trade_no' => $order_no,
        'pay_price' => $order_info['money'],
        'order_type' => ORDER_TYPE_PERSON,
        'createtime' => TIMESTAMP
    );
    $status = pdo_insert('pay_log',$pay_log_data);
    if(!$status){
        message('支付信息，提交失败','','error');
    }
    $insert_pay_log_id = pdo_insertid();
    if(!$insert_pay_log_id ){
        message('支付信息，提交失败','','error');
    }
    header("location:".url('mc/pay/display',array('id'=>$insert_pay_log_id)));
}elseif($do == 'pay_log'){ //付款记录
    $tip = "转账记录";
    $page = getApartPageNo('page');
    $psize = 50;
    $pindex = ($page-1)*$psize;
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.realname FROM ".tablename('order_person')." a LEFT JOIN ".tablename('mc_members')." b ON a.cashier_uid=b.uid WHERE a.uniacid='{$_W['uniacid']}' AND a.pay_uid='{$_W['member']['uid']}' AND a.is_delete=".DELETE_NO." ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}");
    if(!empty($list) && is_array($list)){
        foreach($list as $k => &$v){
            $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
            $v['span_class'] = $v['pay_status'] == PAY_YES?'success':'danger';
            $v['span_desc'] = $v['pay_status'] == PAY_YES?'已支付':'未支付';
            $v['note'] = empty($v['note'])?'未备注':$v['note'];
        }
    }
    if($_W['isajax']){
        $ac = trim($_GPC['ac']);
        if($ac == 'delete'){ //删除
            $order_id = floor(trim($_GPC['order_id']));
            $status = pdo_update('order_person',array(
                'uniacid' => $_W['uniacid'],
                'pay_uid' => $_W['member']['uid'],
                'id' => $order_id,
                'is_delete' => DELETE_YES,
                'updatetime' => TIMESTAMP
            ));
            if(!$status){
                message('删除失败','','error');
            }
            message('删除成功','','success');
        }else{
            //加载更多
            if(!empty($list) && is_array($list)){
                message($list,'','success');
            }else{
                message('没有更多订单','','error');
            }
        }
    }
}elseif($do == 'income_log'){//收款记录
    $tip = "收款记录";
    $page = getApartPageNo('page');
    $psize = 50;
    $pindex = ($page-1)*$psize;
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.realname FROM ".tablename('order_person')." a LEFT JOIN ".tablename('mc_members')." b ON a.pay_uid=b.uid WHERE a.uniacid='{$_W['uniacid']}' AND a.cashier_uid='{$_W['member']['uid']}' AND a.is_delete=".DELETE_NO." ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}");
    if(!empty($list) && is_array($list)){
        foreach($list as $k => &$v){
            $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
            $v['span_class'] = $v['pay_status'] == PAY_YES?'success':'danger';
            $v['span_desc'] = $v['pay_status'] == PAY_YES?'已支付':'未支付';
            $v['note'] = empty($v['note'])?'未备注':$v['note'];
        }
    }
    if($_W['isajax']){
        //加载更多
        if(!empty($list) && is_array($list)){
            message($list,'','success');
        }else{
            message('没有更多收款记录','','error');
        }
    }
}elseif($do == 'balance'){
    $balance_rate = 0;
    $config = pdo_get('distribution_config',array(
        'uniacid' => $_W['uniacid']
    ));
    if(!empty($config) && is_array($config)){
        $setting = iunserializer($config['setting']);
        if(isset($setting['person']['balance_rate'])){
            $balance_rate =  floatval($setting['person']['balance_rate']*0.01);
        }
    }
    $tip = "申请结算";
    $total_sale_money = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_person')." WHERE uniacid='{$_W['uniacid']}' AND cashier_uid='{$_W['member']['uid']}' AND pay_status=".PAY_YES);
    $total_already_withdraw_money = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('person_balance')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'");
    $can_withdraw_money = round(floatval($total_sale_money*$balance_rate - $total_already_withdraw_money),3);
    if($_W['isajax']){
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'tel' => trim($_GPC['tel']),
            'username' => trim($_GPC['username']),
            'money' => floatval(trim($_GPC['money'])),
            'withdraw_method' => PAY_METHOD_WECHAT,
            'createtime' => TIMESTAMP
        );
        if($data['money'] <= 0){
            message('提现金额有误，请重新输入','','error');
        }
        if($data['money'] < 1){
            message('提现金额不能小于1元','','error');
        }
        if($data['money'] >= $can_withdraw_money){
            message('提现金额不能超过实际金额','','error');
        }
        $status = pdo_insert('person_balance',$data);
        if(!$status){
            message("申请失败！", "", "error");
        }
        message('申请成功，请耐心等待平台审核^-^',referer(),'success');
    }
}elseif($do == 'balance_log'){
    $tip = "结算记录";
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $list = pdo_fetchall("SELECT * FROM ".tablename('person_balance')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    if(check_data($list)){
        foreach($list  as $k => &$v){
            $v['createtime'] = date('Y-m-d H:i',$v['createtime']);
        }
    }
    if($_W['isajax']){
        if(check_data($list)){
            message($list,'','success');
        }
        message('没有更多记录','','error');
    }
}
template('mc/cashier');
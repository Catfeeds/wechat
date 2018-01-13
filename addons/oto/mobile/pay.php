<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/26
 * Time: 12:29
 */
if($op == 'display'){
    $member = pdo_fetch("SELECT * FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'");
    if(empty($member['city']) || empty($member['province']) ||empty($member['district'])){
        message('请先设置所在地',url('set/location/display'),'error');
    }
    $store_id = floor(trim($_GPC['store_id']));
    $store_info = OtoModel::getStoreInfoById($store_id);
    if(empty($store_info) || !is_array($store_info)){
        message('店铺信息不存在','','error');
    }
    if(!payOfflineAuthCheck($store_id,$_W['uniacid'],$_GPC['auth'])){
        message('安全验证失败，无法支付','','error');
    }
    //验证店铺等级
    $store_key = pdo_fetchcolumn("SELECT product_key FROM ".tablename('fangyuanbao_user')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$store_info['saler_uid']}'");

    //积分兑换比例
    $credit_exchange_rate = 0;
    $config = pdo_get('distribution_config',array(
        'uniacid' => $_W['uniacid']
    ));
    if(!empty($config) && is_array($config)){
        $setting = iunserializer($config['setting']);
        if(isset($setting['credit1']['exchange_rate'])){
            $credit_exchange_rate =  floatval($setting['credit1']['exchange_rate']*0.01);
        }
    }

    if($_W['isajax']){
        $order_no = generateOrderSnByBuyTodayTradeCount();
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'store_id' => $store_info['id'],
            'store_type' => STORE_TYPE_OTO,
            'order_no' => $order_no,
            'money' => floatval(trim($_GPC['money'])),
            'note' => $_GPC['note'],
            'createtime' => TIMESTAMP
        );
        if(!is_numeric($data['money']) || $data['money'] <= 0){
            message('金额输入有误，请重新输入','','error');
        }
        if($data['store_id'] == '3928'){ //免费店铺，扣除方圆宝
            $reduce_num = ceil($data['money']*2/60);
            $fybCount = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND status=0");
            if($fybCount < $reduce_num){
                message("你当前拥有{$fybCount}个方圆宝，不足抵税",'','error');
            }
            //获取的时候截取到所需个数
            $fybList = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND status=0 ORDER BY createtime DESC LIMIT 0,{$reduce_num}");
            foreach($fybList as $k => $fyb){
                if($k+1 > $reduce_num)break;
                pdo_update('fangyuanbao_queue',array(
                    'status' => 1,
                    'is_buy' => 1 //购物抵扣
                ),array(
                    'uniacid' => $_W['uniacid'],
                    'uid'=>$_W['member']['uid'],
                    'id'=>$fyb['id'],
                    'status'=>0
                ));
            }
        }
        $insert_order_id = OtoModel::insertOfflineOrderInfoReturnInsertId($data);
        if(!$insert_order_id){
            message('订单生成失败','','error');
        }
        //插入支付记
        $pay_log_data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'order_ids' => $insert_order_id,
            'out_trade_no' => $order_no,
            'pay_price' => $data['money'],
            'order_type' => ORDER_TYPE_OFFLINE,
            'createtime' => TIMESTAMP
        );
        //判断是否使用积分
        $is_use_credit = floor(trim($_GPC['is_use_credit'])) == IS_STATUS?IS_STATUS:NO_STATUS;
        if($is_use_credit == IS_STATUS){
            //判断店铺是否符合条件
            if($store_key != 3){
                message('该店铺未开启积分消费','','error');
            }
            //平台积分购买
            if($credit_exchange_rate <= 0){
                message('积分抵扣暂未开启','','error');
            }
            //会员积分判断
            if(floor($member['credit1']) <= 0){
                message('没有可用的积分','','error');
            }
            $need_credit1 = floor($data['money']/$credit_exchange_rate);
            $pay_log_data['use_credit1'] = $need_credit1;
            if($need_credit1 <= $member['credit1']) {//积分满足消费
                $pay_log_data['pay_price'] = 0;
                $insert_pay_log_id = OtoModel::insertOrderPayLogReturnInsertId($pay_log_data);
                if($insert_pay_log_id == false){
                    message('支付信息，提交失败','','error');
                }
                func_pay_credit1_deal($pay_log_data);
            }else{//积分不满足,成功支付后扣除积分
                $exchange_money = $member['credit1']*$credit_exchange_rate;
                $pay_log_data['use_credit1'] = $member['credit1'];
                $pay_log_data['pay_price'] -= $exchange_money;
                $insert_pay_log_id = OtoModel::insertOrderPayLogReturnInsertId($pay_log_data);
                if($insert_pay_log_id == false){
                    message('支付信息，提交失败','','error');
                }
                message('提交成功，正在跳转',url('mc/pay/display',array('id'=>$insert_pay_log_id)),'success');
            }
        }else{
            $insert_pay_log_id = OtoModel::insertOrderPayLogReturnInsertId($pay_log_data);
            if($insert_pay_log_id == false){
                message('支付信息，提交失败','','error');
            }
            message('提交成功，正在跳转',url('mc/pay/display',array('id'=>$insert_pay_log_id)),'success');
        }
    }
}elseif($op == 'order'){
    $psize = 50;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $list = OtoModel::getMemberOfflineOrderList($pindex,$psize);
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
            $status = OtoModel::updateMemberOfflineOrderInfoById($order_id,array(
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
}elseif($op == 'push'){
    //再次支付
    $order_no = generateOrderSnByBuyTodayTradeCount();
    $id = floor(trim($_GPC['id']));
    $order_info = OtoModel::getMemberOfflineOrderInfoById($id);
    if(empty($order_info) || !is_array($order_info)){
        message('订单信息不存在','','error');
    }
    if($order_info['pay_status'] == PAY_YES){
        message('您已经支付成功，请勿重复支付','','error');
    }
    //更改订单编号
    $update_order_no = OtoModel::updateMemberOfflineOrderInfoById($id,array(
        'order_no' => $order_no,
        'createtime' => TIMESTAMP
    ));
    if(!$update_order_no){
        message('订单号更新失败','','error');
    }

    //插入支付记录
    $pay_log_data = array(
        'uniacid' => $order_info['uniacid'],
        'uid' => $order_info['uid'],
        'order_ids' => $order_info['id'],
        'out_trade_no' => $order_no,
        'pay_price' => $order_info['money'],
        'order_type' => ORDER_TYPE_OFFLINE,
        'createtime' => TIMESTAMP
    );
    $insert_pay_log_id = OtoModel::insertOrderPayLogReturnInsertId($pay_log_data);
    if($insert_pay_log_id == false){
        message('支付信息，提交失败','','error');
    }
    header("location:".url('mc/pay/display',array('id'=>$insert_pay_log_id)));
}

/**
 * @param $password
 * @param $pay_info
 * 积分支付处理函数
 */
function func_pay_credit1_deal($pay_info){
    global $_W;
    load()->model('user');
    $member_info = mc_fetch($_W['member']['uid']);
    if(empty($member_info) || !is_array($member_info)){
        message('会员信息不存在','','error');
    }
    if($pay_info['use_credit1'] > $member_info['credit1']){
        message('积分不足！','','error');
    }
    load()->model('mc');
    $status = mc_credit_update($_W['member']['uid'],'credit1',-$pay_info['use_credit1'],array($_W['member']['uid'],"积分购物消费，扣除{$pay_info['use_credit1']}积分"));
    if($status !== true){
        message('积分扣除失败','','error');
    }
    //处理交易成功后逻辑
    load()->classs('pay.notify');
    $notify = new PayNotify($pay_info);
    $status = $notify -> finish();
    if(!$status){
        message('交易失败','','error');
    }
    message("支付成功",getPayBackUrl($pay_info['order_type']),'success');
}
include $this->template('pay');
<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/15
 * Time: 10:57
 */
if($op == 'display'){
    $psize = 20;
    $pindex = (max(floor(trim($_GPC['page'])),1)-1)*$psize;
    $list = OtoModel::getMemberOrderList(trim($_GPC['status']),$pindex,$psize);
    $order_status_arr = OtoModel::$orderStatusArr;
}elseif($op == 'pay'){
    $order_no = generateOrderSnByBuyTodayTradeCount();
    $id = floor(trim($_GPC['id']));
    $order_info = OtoModel::getMemberOrderInfoById($id);
    if(empty($order_info) || !is_array($order_info)){
        message('订单信息不存在','','error');
    }
    if($order_info['pay_status'] == PAY_YES){
        message('您已经支付成功，请勿重复支付','','error');
    }
    $update_order_no = OtoModel::updateMemberOrderInfoById($id,array('order_no' => $order_no,'createtime' => TIMESTAMP));
    if(!$update_order_no){
        message('订单号更新失败','','error');
    }
    //插入支付记录
    $pay_log_data = array(
        'uniacid' => $order_info['uniacid'],
        'uid' => $order_info['uid'],
        'order_ids' => $order_info['id'],
        'out_trade_no' => $order_no,
        'order_type' => ORDER_TYPE_OTO_GOODS,
        'pay_price' => $order_info['pay_total_price'],
        'createtime' => TIMESTAMP
    );
    $insert_pay_log_id = OtoModel::insertOrderPayLogReturnInsertId($pay_log_data);
    if($insert_pay_log_id == false){
        message('支付信息，提交失败','','error');
    }
    message('提交成功，正在跳转',url('mc/pay/display',array('id'=>$insert_pay_log_id,'store_type'=>STORE_TYPE_OTO)),'success');
}elseif($op == 'cancel'){ //取消订单
    $id = floor(trim($_GPC['id']));
    $order_info = OtoModel::getMemberOrderInfoById($id);
    if(empty($order_info) || !is_array($order_info)){
        message('订单信息不存在','','error');
    }
    if($order_info['order_status'] == ORDER_STATUS_NOT_PAY || $order_info['pay_status'] == PAY_NO){
        $status = OtoModel::updateMemberOrderInfoById($id,array('order_status'=>ORDER_STATUS_CLOSE));
        if(!$status){
            message('关闭失败','','error');
        }
        message('关闭成功',referer(),'success');
    }
    message('订单当前状态，不支持关闭或取消','','error');
}elseif($op == 'confirm'){  //确认收货
    $id = floor(trim($_GPC['id']));
    $order_info = OtoModel::getMemberOrderInfoById($id);
    if(empty($order_info) || !is_array($order_info)){
        message('订单信息不存在','','error');
    }
    if($order_info['order_status'] == ORDER_STATUS_NOT_CONFIRM && $order_info['pay_status'] == PAY_YES){
        //执行分销
        $status = OtoModel::updateMemberOrderInfoById($id,array('order_status' => ORDER_STATUS_COMPLETE));
        if(!$status){
            message('确认收货失败','','error');
        }
        message('确认成功',referer(),'success');
    }
    message('确认收货失败','','error');
}
include $this->template('order');
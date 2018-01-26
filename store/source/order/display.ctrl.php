<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/13
 * Time: 17:18
 */
load()->model('store');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
$payMethodArrSpan = array(
    1 => '<span class="label label-default">余额</span>',
    2 => '<span class="label label-info">微信</span>',
    3 => '<span class="label label-warning">支付宝</span>',
    4 => '<span class="label label-success">银行卡</span>',
    5 => '<span class="label label-warning">微信</span>',
    6 => '<span class="label label-danger">货到</span>'
);
if($do == 'display'){
    $orderStatusArrSpan = array(
        0 => '<span class="label label-default">待付款</span>',
        1 => '<span class="label label-info">待发货</span>',
        2 => '<span class="label label-warning">待确认</span>',
        3 => '<span class="label label-success">已完成</span>',
        4 => '<span class="label label-warning">待退款</span>',
        5 => '<span class="label label-danger">已退款</span>',
        6 => '<span class="label label-danger">关闭取消</span>'
    );
    $orderStatusArr = array(
        0 => '未付款',
        1 => '等待发货',
        2 => '等待买家确认',
        3 => '买家已确认',
        4 => '等待平台退款',
        5 => '平台已退款',
        6 => '买家已关闭'
    );
    if($_W['isajax']){
        $ac = trim($_GPC['ac']);
        if($ac == 'deliver'){
            if($_W['isajax']){
                $order_id = floor(trim($_GPC['order_id']));
                $deliver_no = trim($_GPC['deliver_no']);
                $re_deliver_no = trim($_GPC['re_deliver_no']);
                if(!empty($deliver_no) || !empty($re_deliver_no)){
                    if($deliver_no != $re_deliver_no){
                        message('两次运单号输入不一致，请先确认后，再发货','','error');
                    }
                }
                $order_info = StoreModel::getStoreOrderInfoById($order_id);
                if(empty($order_info)){
                    message('订单信息不存在','','error');
                }
                if($order_info['pay_status'] != PAY_YES){
                    message('订单未支付，不能发货','','error');
                }
                if($order_info['order_status'] != ORDER_STATUS_NOT_DELIVER){
                    message('当前订单处于【'.$orderStatusArr[$order_info['order_status']].'】不能发货','','error');
                }
                $status = StoreModel::storeDeliverOrder($order_id,$re_deliver_no);
                if(!$status){
                    message('发货失败','','error');
                }
                message('发货成功',referer(),'success');
            }
            message('请求方式错误','','error');
        }elseif($ac == 'verify'){
            //核销
            if($_W['isajax']){
                $order_no = trim($_GPC['order_no']);
                if(empty($order_no)){
                    message('请输入订单号','','error');
                }
                $verify_code = trim($_GPC['verify_code']);
                if(empty($verify_code)){
                    message('请输入核销码','','error');
                }
                $info = StoreModel::getOrderInfoByNoVerifyCode($order_no,$verify_code);
                if(!empty($info) && is_array($info)){
                    if($info['pay_status'] == PAY_NO){
                        message('该订单未付款，不能核销','','error');
                    }
                    if($info['is_verify'] == ORDER_VERIFY_YES){
                        message('该订单已经核销','','error');
                    }
                    $verify_data = array(
                        'order_status' => ORDER_STATUS_COMPLETE,
                        'confirmtime' => TIMESTAMP,
                        'is_verify' => ORDER_VERIFY_YES,
                        'finish_time' => TIMESTAMP
                    );
                    $status = StoreModel::updateOrderInfoById($info['id'],$verify_data);
                    /* 分销 */
                    load()->classs('distribution');
                    $distribution = new Distribution($info['id'],MODULE_NAME_OTO);
                    $distribution ->deal();
                    /* 分销 */
                    if(!$status){
                        message('核销失败','','error');
                    }
                    message('核销成功','','success');
                }
                message('订单校验失败','','error');
            }
            message('请求方式错误','','error');
        }
    }
    $psize = 20;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $starttime = empty($_GPC['createtime']['start']) ? strtotime('-90 days') : strtotime($_GPC['createtime']['start']);
    $endtime = empty($_GPC['createtime']['end']) ? TIMESTAMP + 86399 : strtotime($_GPC['createtime']['end']) + 86399;
    $keyword = trim($_GPC['keyword']);
    $pay_status = $_GPC['pay_status'];
    $order_status = $_GPC['order_status'];
    $pay_methods = $_GPC['pay_methods'];
    $list = StoreModel::getStoreOrderList($keyword,$pay_methods,$pay_status,$order_status,$starttime,$endtime,$pindex,$psize);
    $total = StoreModel::getStoreOrderCount($keyword,$pay_methods,$pay_status,$order_status,$starttime,$endtime);
    $total_pay_price =  StoreModel::getStoreOrderTotalPayPrice($keyword,$pay_methods,$pay_status,$order_status,$starttime,$endtime);
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);
}elseif($do == 'offline'){
    $psize = 50;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $starttime = empty($_GPC['createtime']['start']) ? strtotime('-90 days') : strtotime($_GPC['createtime']['start']);
    $endtime = empty($_GPC['createtime']['end']) ? TIMESTAMP + 86399 : strtotime($_GPC['createtime']['end']) + 86399;
    $keyword = trim($_GPC['keyword']);
    $pay_status = $_GPC['pay_status'];
    $pay_methods = $_GPC['pay_methods'];
    $list = StoreModel::getStoreOfflineOrderList($keyword,$pay_methods,$pay_status,$starttime,$endtime,$pindex,$psize);
    $total = StoreModel::getStoreOfflineOrderCount($keyword,$pay_methods,$pay_status,$starttime,$endtime);
    $total_pay_price =  StoreModel::getStoreOfflineOrderTotalPayPrice($keyword,$pay_methods,$pay_status,$starttime,$endtime);
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);
}
template('order/display');
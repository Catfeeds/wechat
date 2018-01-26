<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/15
 * Time: 10:59
 */
$payMethodArrSpan = array(
    1 => '<span class="label label-default">余额</span>',
    2 => '<span class="label label-info">微信</span>',
    3 => '<span class="label label-warning">支付宝</span>',
    4 => '<span class="label label-success">银行卡</span>',
    5 => '<span class="label label-warning">微信</span>',
    6 => '<span class="label label-danger">货到</span>'
);
if($op == 'display'){
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
        0 => '等待买家付款',
        1 => '等待发货',
        2 => '等待买家确认',
        3 => '买家已确认',
        4 => '等待平台退款',
        5 => '平台已退款',
        6 => '买家已关闭'
    );
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $keyword = trim($_GPC['keyword']);
    $pay_status = $_GPC['pay_status'];
    $order_status = $_GPC['order_status'];
    $pay_methods = $_GPC['pay_methods'];
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $store_name = trim($_GPC['store_name']);
    $list = OtoModel::getOrderList($store_name,$keyword,$pay_methods,$pay_status,$order_status,$province,$city,$district,$starttime,$endtime,$pindex,$psize,EXPORT_NO);
    $total = OtoModel::getOrderCount($store_name,$keyword,$pay_methods,$pay_status,$order_status,$province,$city,$district,$starttime,$endtime);
    $total_pay_price = OtoModel::getOrderTotalPayPrice($store_name,$keyword,$pay_methods,$pay_status,$order_status,$province,$city,$district,$starttime,$endtime);
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);
}elseif($op == 'offline'){
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $keyword = trim($_GPC['keyword']);
    $pay_status = $_GPC['pay_status'];
    $list = OtoModel::getOrderOfflineList($keyword,$pay_status,$starttime,$endtime,$pindex,$psize,EXPORT_NO);
    $total = OtoModel::getOrderOfflineCount($keyword,$pay_status,$starttime,$endtime);
    $total_pay_price = OtoModel::getOrderOfflineTotalPayPrice($keyword,$pay_status,$starttime,$endtime);
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);
}
include $this->template('order');
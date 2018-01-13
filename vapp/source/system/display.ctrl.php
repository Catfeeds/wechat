<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/13
 * Time: 13:58
 */
load()->model('vapp');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
$item = vappModel::getvappVoiceConfig();
if($do == 'display'){
    if($_W['isajax']){
        $data = array(
            'status' => floor(trim($_GPC['status'])) == OPEN_STATUS?OPEN_STATUS:CLOSE_STATUS,
            'play_rate' => max(1,floor(trim($_GPC['play_rate'])))
        );
        if(empty($item)){
            $data['uniacid'] = $_W['uniacid'];
            $data['vapp_id'] = $_W['vapp_id'];
            $data['vapp_type'] = $_W['vapp_type'];
            $data['createtime'] = TIMESTAMP;
            $flag = vappModel::insertvappVoiceConfig($data);
        }else{
            $data['updatetime'] = TIMESTAMP;
            $flag = vappModel::updatevappVoiceConfig($data);
        }
        if(!$flag){
            message('设置失败','','error');
        }
        message('设置成功',referer(),'success');
    }
}elseif($do == 'voice'){
    /* 查询未发货订单 */
    $tips = "订单提示信息如下：<br>";
    $orderStatusList = vappModel::getvappOrderCountGroupOrderStatus();
    if(isset($orderStatusList[ORDER_STATUS_NOT_DELIVER]['count'])){
        if($orderStatusList[ORDER_STATUS_NOT_DELIVER]['count'] > 0){
            $not_deliver_order_link = url('order/display/display',array('notice_order_status' => ORDER_STATUS_NOT_DELIVER));
            message("您有:<span style='color: red'>{$orderStatusList[ORDER_STATUS_NOT_DELIVER]['count']}</span>个订单【<a href='{$not_deliver_order_link}'>点击查看详情</a>】尚未发货",'','success');
        }
    }
    message('暂无通知','','error');
}
template('system/display');
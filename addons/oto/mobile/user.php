<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/20
 * Time: 19:36
 */
if($op == 'display'){
    $check_info = OtoModel::getMemberCheckInfo();
    if(empty($check_info) || !is_array($check_info)){
        header('location:'.$this->createMobileUrl('verify'));
    }
    //获取所有订单数目
    load()->model('mc');
    $user = mc_fetch($_W['member']['uid']);
    if(empty($user)){
        message('用户不存在','','error');
    }
    $parents = explode(SPLIT_RELATION,$user['relation']);
    if(!empty($parents[0])){
        $parent_uid = $parents[0];
    }else{
        $parent_uid = 0;
    }
    $fyb_user = pdo_get('fangyuanbao_user',array(
        'uniacid' => $_W['uniacid'],
        'uid' => $_W['member']['uid']
    ));
    $agent = pdo_get('agent_user',array(
        'uniacid' => $_W['uniacid'],
        'uid' => $_W['member']['uid']
    ));
    $order_group = OtoModel::getOrderCountGroupByStatus();
    $no_talk_order_num = OtoModel::getOrderNotTalkCount();
    $goods_collect_count = OtoModel::getMemberCollectCount(COLLECT_TYPE_GOODS);
    $store_collect_count = OtoModel::getMemberCollectCount(COLLECT_TYPE_STORE);
    $look_collect_count = 0;
}
include $this->template('user');
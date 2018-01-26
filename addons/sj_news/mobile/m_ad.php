<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/9/24
 * Time: 13:34
 */
checkauth();
load()->model('mc');
load()->func('check');
$member = mc_fetch($_W['member']['uid']);
//广告配置
$adConfig = pdo_get('sj_news_ad_config',array(
    'uniacid' => $_W['uniacid']
));
if(!empty($adConfig['setting'])){
    $adConfig['setting'] = iunserializer($adConfig['setting']);
}
if($op == 'display'){
    if($_W['isajax']){
        $id = floor(trim($_GPC['id']));
        if(!check_id($id)){
            message('广告ID错误','','error');
        }
        $order = pdo_get('sj_news_ad_order',array(
            'sj_uniacid' => $_W['uniacid'],
            'sj_uid' => $_W['member']['uid'],
            'ad_id' => $id
        ));
        //检查订单是否存在
        if(!check_data($order)){
            message('订单不存在','','error');
        }
        if($order['pay_status'] == 1){
            message('订单已支付，无需重复支付','','error');
        }
        $log_id = pdo_fetchcolumn("SELECT id FROM ".tablename('pay_log')." WHERE uniacid='{$order['uniacid']}' AND out_trade_no='{$order['order_no']}'");
        if(!check_id($log_id)){
            message('支付记录不存在','','error');
        }
        message('正在跳转到支付',"{$_W['siteroot']}app/index.php?i=7&c=mc&a=pay&do=display&id={$log_id}&wxref=mp.weixin.qq.com",'success');
    };
    $page = getApartPageNo();
    $psize = 100;
    $pindex = ($page-1)*$psize;
    $list = pdo_fetchall("SELECT a.*,b.pay_status FROM ".tablename('sj_news_ad')." a LEFT JOIN ".tablename('sj_news_ad_order')." b ON a.id=b.ad_id WHERE a.uniacid='{$_W['uniacid']}' AND a.uid='{$member['uid']}' ORDER BY a.id DESC LIMIT {$pindex},{$psize}");
    if(empty($list)){
        message('您还尚未发布广告','','error');
    }
    foreach($list as $k => &$v){
        $selectedAd = $adConfig['setting'][$v['package_id']];
        $v['package_name'] = $selectedAd['name'];
        $v['package_json'] = json_encode($selectedAd);
    }
}

//续费
if($op == 'renew'){
    if($_W['isajax']){
        $id = floor(trim($_GPC['id']));
        if(!check_id($id)){
            message('广告ID错误','','error');
        }
        $buy_num = floor(trim($_GPC['num']));
        if($buy_num<1 || $buy_num > 10){
            message('购买数量为1-10','','error');
        }
        $ad_info = pdo_get('sj_news_ad',array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'id' => $id
        ));
        if(!check_data($ad_info)){
            message('广告信息不存在','','error');
        }
        //选择的广告类型
        $selectedAd = $adConfig['setting'][$ad_info['package_id']];
        $order_data = array(
            'sj_uniacid' => $_W['uniacid'],
            'sj_uid' => $_W['member']['uid'],
            'ad_id' => $id,
            'order_no' => generateOrderSnByBuyTodayTradeCount(),
            'buy_num' => $buy_num,
            'price' => $selectedAd['price']*(1-0.01*$selectedAd['pay_rate']),
            'pay_goods_price' =>  $selectedAd['price']*0.01*$selectedAd['pay_rate'],
            'day' => $selectedAd['day'],
            'createtime' => TIMESTAMP
        );
        $order_data['pay_total_price'] = round(floatval($order_data['buy_num']*$order_data['price']),2);
        $order_data['pay_total_goods_price'] = round(floatval($order_data['buy_num']*$order_data['pay_goods_price']),2);
        $order_data['total_day'] = $order_data['buy_num']*$order_data['day'];

        //插入订单
        $status = pdo_insert('sj_news_ad_renew_order',$order_data);
        $insert_ad_order_id = pdo_insertid();
        if(!$status || !$insert_ad_order_id){
            message('续费订单生成失败','','error');
        }
        $log_data = array(
            'order_ids' => $insert_ad_order_id,
            'out_trade_no' => $order_data['order_no'],
            'pay_price' => $order_data['pay_total_price'],
            'order_type' => ORDER_TYPE_SJ_NEWS_RENEW,
            'createtime' => TIMESTAMP
        );
        $status2 = pdo_insert('pay_log',$log_data);
        $insert_log_id = pdo_insertid();
        if(!$status2 || !$insert_log_id){
            message('支付记录生成失败','','error');
        }
        message('正在跳转到支付',"{$_W['siteroot']}app/index.php?i=7&c=mc&a=pay&do=display&id={$insert_log_id}&wxref=mp.weixin.qq.com",'success');
    }
    message('请求方式错误','','error');
}

include $this->template('m_ad');
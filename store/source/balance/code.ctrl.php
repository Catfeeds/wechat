<?php
load()->model('store');
if(empty($do)){$do = 'display';}
if($do == 'display'){
    $link = "";
    if($_W['store_type'] == STORE_TYPE_OTO){
        $auth = payOfflineAuthEncode($_W['store_id'],$_W['uniacid']);
        $link = urlencode("{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&store_id={$_W['store_id']}&auth={$auth}&do=pay&m=oto");
    }
    $img = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=mc&a=poster&do=image&ps={$link}";
}elseif($do == 'list'){
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
template('balance/code');
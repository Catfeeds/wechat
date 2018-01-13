<?php
load()->model('account');
if(empty($do)){$do = 'display';}
$payMethodArrSpan = array(
    1 => '<span class="label label-default">余额</span>',
    2 => '<span class="label label-info">微信</span>',
    3 => '<span class="label label-warning">支付宝</span>',
    4 => '<span class="label label-success">银行卡</span>',
    5 => '<span class="label label-warning">微信</span>',
    6 => '<span class="label label-danger">货到</span>'
);
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
if($do == 'display'){
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $uid = floor(trim($_GPC['uid']));
    $keyword = trim($_GPC['keyword']);
    $pay_status = $_GPC['pay_status'];
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $where = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    if(!empty($uid)){
        $where .= " AND a.uid='{$uid}'";
    }
    if(!empty($keyword)){
        $where .= " AND (a.uid='{$keyword}' OR a.order_no LIKE '%{$keyword}%')";
    }
    if(!empty($pay_status) && is_array($pay_status)){
        $where .= " AND a.pay_status IN (".implode(',',$pay_status).")";
    }
    $store_where = "uniacid='{$_W['uniacid']}'";
    if(!empty($store_name)){
        $store_where .= " AND title LIKE '%{$store_name}%'";
    }
    if(!empty($province)){
        $store_where .= " AND province='{$province}'";
    }
    if(!empty($city)){
        $store_where .= "AND city='{$city}'";
    }
    if(!empty($district)){
        $store_where .= " AND district='{$district}'";
    }
    $where .= " AND a.store_id IN (SELECT id FROM ".tablename('store_list')." WHERE {$store_where})";
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('order_offline')." a WHERE {$where}");
    $total_pay_price = pdo_fetchcolumn("SELECT SUM(a.money) FROM ".tablename('order_offline')." a WHERE {$where}");
    $where .= " ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.title AS store_title,c.nickname,c.realname FROM ".tablename('order_offline')." a LEFT JOIN ".tablename('store_list')." b ON a.store_id=b.id LEFT JOIN ".tablename('mc_members')." c ON a.uid=c.uid WHERE {$where}");
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);

}elseif($do == 'fee'){
    $keyword = trim($_GPC['keyword']);
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $product = $_GPC['product'];
    $pay_status = $_GPC['pay_status'];
    $pay_methods = $_GPC['pay_methods'];
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $where = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    if(!empty($province)){
        $where .= " AND b.province='{$province}'";
    }
    if(!empty($city)){
        $where .= " AND b.city='{$city}'";
    }
    if(!empty($district)){
        $where .= " AND b.district='{$district}'";
    }
    if(!empty($keyword)){
        $where .= " AND (a.uid='{$keyword}' OR a.order_no LIKE '%{$keyword}%' OR b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
    }
    if(!empty($product)){
        $where .= " AND product_key IN (".implode(',',$product).")";
    }
    if(!empty($pay_status)){
        $where .= " AND pay_status IN (".implode(',',$pay_status).")";
    }
    if(!empty($pay_methods)){
        if(in_array(PAY_METHOD_WECHAT,$pay_methods)){
            array_push($pay_methods,PAY_METHOD_FUIOU);
        }
        $where .= " AND pay_method IN (".implode(',',$pay_methods).")";
    }
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('fangyuanbao_user_order')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $total_pay_price = pdo_fetchcolumn("SELECT SUM(a.pay_price) FROM ".tablename('fangyuanbao_user_order')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $where .=" ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.nickname FROM ".tablename('fangyuanbao_user_order')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}elseif($do == 'person'){
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $keyword = trim($_GPC['keyword']);
    $pay_status = $_GPC['pay_status'];
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $where = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    $where2 = "uniacid='{$_W['uniacid']}'";
    if(!empty($province)){
        $where2 .= " AND province='{$province}'";
    }
    if(!empty($city)){
        $where2 .= " AND city='{$city}'";
    }
    if(!empty($district)){
        $where2 .= " AND district='{$district}'";
    }
    $where .= " AND a.cashier_uid IN (SELECT uid FROM ".tablename('mc_members')." WHERE {$where2})";
    if(!empty($keyword)){
        $where .= " AND (a.pay_uid='{$keyword}' OR a.cashier_uid='{$keyword}' OR a.order_no LIKE '%{$keyword}%')";
    }
    if(!empty($pay_status) && is_array($pay_status)){
        $where .= " AND a.pay_status IN (".implode(',',$pay_status).")";
    }
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('order_person')."a WHERE {$where}");
    $total_pay_price = pdo_fetchcolumn("SELECT SUM(a.money) FROM ".tablename('order_person')." a WHERE {$where}");
    $where .= " ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.realname FROM ".tablename('order_person')." a LEFT JOIN ".tablename('mc_members')." b ON a.pay_uid=b.uid WHERE {$where}");
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);
}elseif($do == 'shop'){
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $keyword = trim($_GPC['keyword']);
    $uid = floor(trim($_GPC['uid']));
    $pay_status = $_GPC['pay_status'];
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $where = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    $where2 = "uniacid='{$_W['uniacid']}'";
    if(!empty($province)){
        $where2 .= " AND province='{$province}'";
    }
    if(!empty($city)){
        $where2 .= " AND city='{$city}'";
    }
    if(!empty($district)){
        $where2 .= " AND district='{$district}'";
    }
    $where .= " AND a.uid IN (SELECT uid FROM ".tablename('mc_members')." WHERE {$where2})";
    if(!empty($keyword)){
        $where .= " AND a.order_no LIKE '%{$keyword}%'";
    }
    if(!empty($uid)){
        $where .= " AND a.uid='{$uid}'";
        $children_count = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_shop_user')." WHERE uniacid='{$_W['uniacid']}' AND (relation REGEXP '^{$uid}-' OR relation REGEXP '-{$uid}-')");
    }
    if(!empty($pay_status) && is_array($pay_status)){
        $where .= " AND a.pay_status IN (".implode(',',$pay_status).")";
    }
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('fangyuanbao_shop_order')."a WHERE {$where}");
    $total_pay_price = pdo_fetchcolumn("SELECT SUM(a.pay_price) FROM ".tablename('fangyuanbao_shop_order')." a WHERE {$where}");
    $where .= " ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.realname FROM ".tablename('fangyuanbao_shop_order')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);
}
template('account/display');
<?php
load()->func('check');
if(empty($do)){$do = 'display';}
$province = $_W['province'];
$city = $_W['city'];
$district = $_GPC['area']['district'];
if($_W['is_big_city']){
    $district = $_W['district'];
}
list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');

if($do == 'display'){
    $payMethodArrSpan = array(
        1 => '<span class="label label-default">余额</span>',
        2 => '<span class="label label-info">微信</span>',
        3 => '<span class="label label-warning">支付宝</span>',
        4 => '<span class="label label-success">银行卡</span>',
        5 => '<span class="label label-warning">微信</span>',
        6 => '<span class="label label-danger">货到</span>'
    );
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $store_name = trim($_GPC['store_name']);
    $keyword = trim($_GPC['keyword']);
    $store_where = "uniacid='{$_W['uniacid']}'";
    if(!empty($store_name)){
        $store_where .= " AND title LIKE '%{$store_name}%'";
    }
    if(!empty($province)){
        $store_where .= " AND province='{$province}'";
    }
    if(!empty($city)){
        $store_where .= " AND city='{$city}'";
    }
    if(!empty($district)){
        $store_where .= " AND district='{$district}'";
    }
    $where = "a.uniacid='{$_W['uniacid']}'";
    if(!empty($keyword)){
        $where .= " AND (a.order_no LIKE '%{$keyword}%')";
    }
    if(check_data($_GPC['pay_methods'])){
        if(in_array(PAY_METHOD_WECHAT,$_GPC['pay_methods'])){
            array_push($_GPC['pay_methods'],PAY_METHOD_FUIOU);
        }
        $where .= " AND a.pay_method IN (".implode(',',$_GPC['pay_methods']).")";
    }
    if(check_data($_GPC['pay_status'])){
        $where .= " AND a.pay_status IN (".implode(',',$_GPC['pay_status']).")";
    }
    $where .= " AND a.store_id IN (SELECT id FROM ".tablename('store_list')." WHERE {$store_where})";
    $where .= " AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('order_offline')." a LEFT JOIN ".tablename('store_list')." b ON a.store_id=b.id WHERE {$where}");
    $total_pay_price = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." a LEFT JOIN ".tablename('store_list')." b ON a.store_id=b.id WHERE {$where}");
    $where .= " ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,c.nickname,c.realname,b.title AS store_name FROM ".tablename('order_offline')." a LEFT JOIN ".tablename('store_list')." b ON a.store_id=b.id LEFT JOIN ".tablename('mc_members')." c ON a.uid=c.uid WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}elseif($do == 'person'){
    $payMethodArrSpan = array(
        1 => '<span class="label label-default">余额</span>',
        2 => '<span class="label label-info">微信</span>',
        3 => '<span class="label label-warning">支付宝</span>',
        4 => '<span class="label label-success">银行卡</span>',
        5 => '<span class="label label-warning">微信</span>',
        6 => '<span class="label label-danger">货到</span>'
    );
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $uid_where = "uniacid='{$_W['uniacid']}'";
    if(!empty($province)){
        $uid_where .= " AND province='{$province}'";
    }
    if(!empty($city)){
        $uid_where .= " AND city='{$city}'";
    }
    if(!empty($district)){
        $uid_where .= " AND district='{$district}'";
    }
    $where = "a.uniacid='{$_W['uniacid']}'";
    if(check_data($_GPC['pay_methods'])){
        if(in_array(PAY_METHOD_WECHAT,$_GPC['pay_methods'])){
            array_push($_GPC['pay_methods'],PAY_METHOD_FUIOU);
        }
        $where .= " AND a.pay_method IN (".implode(',',$_GPC['pay_methods']).")";
    }
    if(check_data($_GPC['pay_status'])){
        $where .= " AND a.pay_status IN (".implode(',',$_GPC['pay_status']).")";
    }
    $where .= " AND a.cashier_uid IN (SELECT uid FROM ".tablename('mc_members')." WHERE {$uid_where})";
    $where .= " AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('order_person')." a LEFT JOIN ".tablename('mc_members')." b ON a.cashier_uid=b.uid WHERE {$where}");
    $total_pay_price = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_person')." a LEFT JOIN ".tablename('mc_members')." b ON a.cashier_uid=b.uid WHERE {$where}");
    $where .= " ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.realname FROM ".tablename('order_person')." a LEFT JOIN ".tablename('mc_members')." b ON a.cashier_uid=b.uid WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}elseif($do == 'fee'){
    $payMethodArrSpan = array(
        1 => '<span class="label label-default">余额</span>',
        2 => '<span class="label label-info">微信</span>',
        3 => '<span class="label label-warning">支付宝</span>',
        4 => '<span class="label label-success">银行卡</span>',
        5 => '<span class="label label-warning">微信</span>',
        6 => '<span class="label label-danger">货到</span>'
    );
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $uid = trim($_GPC['uid']);
    $uid_where = "uniacid='{$_W['uniacid']}'";
    if(!empty($uid)){
        $uid_where .= " AND uid='{$uid}'";
    }
    $keyword = trim($_GPC['keyword']);
    if(!empty($keyword)){
        $uid_where .= " AND (nickname LIKE '%{$keyword}%' OR realname LIKE '%{$keyword}%')";
    }
    $where = "a.uniacid='{$_W['uniacid']}'";
    if(check_data($_GPC['pay_methods'])){
        if(in_array(PAY_METHOD_WECHAT,$_GPC['pay_methods'])){
            array_push($_GPC['pay_methods'],PAY_METHOD_FUIOU);
        }
        $where .= " AND a.pay_method IN (".implode(',',$_GPC['pay_methods']).")";
    }
    if(check_data($_GPC['pay_status'])){
        $where .= " AND a.pay_status IN (".implode(',',$_GPC['pay_status']).")";
    }
    if(!empty($province)){
        $where .= " AND b.province='{$province}'";
    }
    if(!empty($city)){
        $where .= " AND b.city='{$city}'";
    }
    if(!empty($district)){
        $where .= " AND b.district='{$district}'";
    }
    $where .= " AND a.uid IN (SELECT uid FROM ".tablename('mc_members')." WHERE {$uid_where})";
    $where .= " AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('fangyuanbao_user_order')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $total_pay_price = pdo_fetchcolumn("SELECT SUM(pay_price) FROM ".tablename('fangyuanbao_user_order')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}
template('order/display');
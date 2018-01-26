<?php
//新晋传媒
$uniacid = 11;

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

//广告订单
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
    $where = "a.uniacid='{$uniacid}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    if(!empty($uid)){
        $where .= " AND a.uid='{$uid}'";
    }
    if(!empty($keyword)){
        $where .= " AND (a.uid='{$keyword}' OR a.order_no LIKE '%{$keyword}%')";
    }
    if(!empty($pay_status) && is_array($pay_status)){
        $where .= " AND a.pay_status IN (".implode(',',$pay_status).")";
    }
    $ad_where = "uniacid='{$uniacid}'";
    if(!empty($province)){
        $ad_where .= " AND province='{$province}'";
    }
    if(!empty($city)){
        $ad_where .= "AND city='{$city}'";
    }
    if(!empty($district)){
        $ad_where .= " AND district='{$district}'";
    }
    $where .= " AND a.ad_id IN (SELECT id FROM ".tablename('sj_news_ad')." WHERE {$ad_where})";
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('sj_news_ad_order')." a WHERE {$where}");
    $total_pay_price = pdo_fetchcolumn("SELECT SUM(a.price) FROM ".tablename('sj_news_ad_order')." a WHERE {$where}");
    $where .= " ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.title,b.package_id,c.nickname,c.realname FROM ".tablename('sj_news_ad_order')." a LEFT JOIN ".tablename('sj_news_ad')." b ON a.ad_id=b.id LEFT JOIN ".tablename('mc_members')." c ON a.uid=c.uid WHERE {$where}");
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);
}

//续费订单
if($do == 'repay'){
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $uid = floor(trim($_GPC['uid']));
    $keyword = trim($_GPC['keyword']);
    $pay_status = $_GPC['pay_status'];
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $where = "a.uniacid='{$uniacid}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    if(!empty($uid)){
        $where .= " AND a.uid='{$uid}'";
    }
    if(!empty($keyword)){
        $where .= " AND (a.uid='{$keyword}' OR a.order_no LIKE '%{$keyword}%')";
    }
    if(!empty($pay_status) && is_array($pay_status)){
        $where .= " AND a.pay_status IN (".implode(',',$pay_status).")";
    }
    $ad_where = "uniacid='{$uniacid}'";
    if(!empty($province)){
        $ad_where .= " AND province='{$province}'";
    }
    if(!empty($city)){
        $ad_where .= "AND city='{$city}'";
    }
    if(!empty($district)){
        $ad_where .= " AND district='{$district}'";
    }
    $where .= " AND a.ad_id IN (SELECT id FROM ".tablename('sj_news_ad')." WHERE {$ad_where})";
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('sj_news_ad_renew_order')." a WHERE {$where}");
    $total_pay_price = pdo_fetchcolumn("SELECT SUM(a.price) FROM ".tablename('sj_news_ad_renew_order')." a WHERE {$where}");
    $where .= " ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.title,b.package_id,c.nickname,c.realname FROM ".tablename('sj_news_ad_renew_order')." a LEFT JOIN ".tablename('sj_news_ad')." b ON a.ad_id=b.id LEFT JOIN ".tablename('mc_members')." c ON a.uid=c.uid WHERE {$where}");
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);
}

template('sjnews/display');
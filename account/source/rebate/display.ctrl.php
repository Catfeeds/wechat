<?php
load()->model('account');
if(empty($do)){$do = 'display';}
if($do == 'display'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $uid = floor(trim($_GPC['uid']));
    $keyword = trim($_GPC['keyword']);
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $where = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    if(!empty($uid)){
        $where .= " AND a.uid='{$uid}'";
    }
    if(!empty($keyword)){
        $where .= " AND (b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
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
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('fangyuanbao_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $total_price = pdo_fetchcolumn("SELECT SUM(a.credit1+a.credit3) FROM ".tablename('fangyuanbao_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $list  = pdo_fetchall("SELECT a.*,b.nickname,b.realname FROM ".tablename('fangyuanbao_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}elseif($do == 'fee'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $uid = floor(trim($_GPC['uid']));
    $keyword = trim($_GPC['keyword']);
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $where = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    if(!empty($uid)){
        $where .= " AND a.uid='{$uid}'";
    }
    if(!empty($keyword)){
        $where .= " AND (b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
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
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('fangyuanbao_user_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $total_price = pdo_fetchcolumn("SELECT SUM(a.money) FROM ".tablename('fangyuanbao_user_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $list  = pdo_fetchall("SELECT a.*,b.nickname,b.realname FROM ".tablename('fangyuanbao_user_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}elseif($do == 'develop'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $uid = floor(trim($_GPC['uid']));
    $keyword = trim($_GPC['keyword']);
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $where = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    if(!empty($uid)){
        $where .= " AND a.uid='{$uid}'";
    }
    if(!empty($keyword)){
        $where .= " AND (b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
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
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('fangyuanbao_shop_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $total_price = pdo_fetchcolumn("SELECT SUM(a.credit1+a.credit3) FROM ".tablename('fangyuanbao_shop_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $list  = pdo_fetchall("SELECT a.*,b.nickname,b.realname FROM ".tablename('fangyuanbao_shop_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}
template('rebate/display');
<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/15
 * Time: 11:00
 */
if($op == 'display'){
    $keyword = trim($_GPC['keyword']);
    $page = getApartPageNo('page');
    list($starttime, $endtime) = getStartTimeEndTimeByGPC('createtime');
    $psize = 20;
    $pindex = ($page - 1) * $psize;
    $where = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    if(!empty($keyword)){
        $where .= " AND (a.uid='{$keyword}' OR b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
    }
    $list = pdo_fetchall("SELECT a.*,b.nickname FROM ".tablename('fangyuanbao_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where} ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}");
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('fangyuanbao_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $total_credit1 = pdo_fetchcolumn("SELECT SUM(a.credit1) FROM ".tablename('fangyuanbao_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $total_credit3 = pdo_fetchcolumn("SELECT SUM(a.credit3) FROM ".tablename('fangyuanbao_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}elseif($op == 'fangyuanbao_queue') {
    $page = getApartPageNo('page');
    list($starttime, $endtime) = getStartTimeEndTimeByGPC('createtime');
    $psize = 20;
    $pindex = ($page - 1) * $psize;
    $keyword = trim($_GPC['keyword']);
    list($province, $city, $district) = array(
        $_GPC['area']['province'],
        $_GPC['area']['city'],
        $_GPC['area']['district']
    );
    $status = $_GPC['status'];
    $list = OtoModel::getFangyuanbaoQueueList($keyword, $starttime, $endtime, $province, $city, $district, $status, $pindex, $psize);
    $total = OtoModel::getFangyuanbaoQueueCount($keyword, $starttime, $endtime, $province, $city, $district, $status);
    $pager = pagination($total, $page, $psize);
}elseif($op == 'op_fangyuanbao_log'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $where = "uniacid='{$_W['uniacid']}' AND createtime BETWEEN {$starttime} AND {$endtime}";
    if(!empty($province)){
        $where .= " AND province='{$province}'";
    }
    if(!empty($city)){
        $where .= "AND city='{$city}'";
    }
    if(!empty($district)){
        $where .= " AND district='{$district}'";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_op_log')." WHERE {$where} ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_op_log')." WHERE {$where}");
    $total_send_num = pdo_fetchcolumn("SELECT SUM(num) FROM ".tablename('fangyuanbao_op_log')." WHERE {$where}");
    $total_old_send_num = pdo_fetchcolumn("SELECT SUM(old_num) FROM ".tablename('fangyuanbao_op_log')." WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}
include $this->template('commission');
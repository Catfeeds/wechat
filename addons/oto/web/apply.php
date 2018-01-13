<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/15
 * Time: 10:58
 */
load()->func('check');
if($op == 'display'){
    if($_W['isajax']){
        if(!check_data($_GPC['ids'])){
            message('请选择要删除的信息','','error');
        }
        $delete_status = pdo_query("DELETE FROM ".tablename('agent_apply')." WHERE uniacid='{$_W['uniacid']}' AND id IN (".implode(',',$_GPC['ids']).")");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $page = getApartPageNo('page');
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $mobile = trim($_GPC['keyword']);
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $where = "uniacid='{$_W['uniacid']}'";
    if(!empty($mobile)){
        $where .= " AND mobile LIKE '%{$mobile}%'";
    }
    if(!empty($province)){
        $where .= " AND province='{$province}'";
    }
    if(!empty($city)){
        $where .= " AND city='{$city}'";
    }
    if(!empty($district)){
        $where .= " AND district='{$district}'";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('agent_apply')." WHERE {$where} ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('agent_apply')." WHERE {$where} "),$page,$psize);
}
include $this->template('apply');
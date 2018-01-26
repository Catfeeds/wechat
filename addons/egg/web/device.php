<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/20
 * Time: 16:57
 */
load()->func('check');
if($op == 'display'){
    if($_W['isajax']){
        $ids = $_GPC['ids'];
        if(!check_data($ids)){
            message('请选择要删除的设备信息','','error');
        }
        $delete_status = pdo_query("DELETE FROM ".tablename('egg_device')." WHERE uniacid='{$_W['uniacid']}' AND id IN (".implode(',',$ids).")");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $where = "uniacid='{$_W['uniacid']}' AND createtime BETWEEN {$starttime} AND {$endtime}";
    $device_no = trim($_GPC['device_no']);
    if(!empty($device_no)){
        $where .= " AND device_no LIKE '%{$device_no}%'";
    }
    $agent_wx = trim($_GPC['agent_wx']);
    if(!empty($agent_wx)){
        $where .= " AND agent_wx LIKE '%{$agent_wx}%'";
    }
    $agent_name = trim($_GPC['agent_name']);
    if(!empty($agent_name)){
        $where .= " AND agent_name LIKE '%{$agent_name}%'";
    }
    $is_active = $_GPC['is_active'];
    if(check_data($is_active)){
        $where .= " AND is_active IN (".implode(',',$is_active).")";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('egg_device')." WHERE {$where} ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('egg_device')." WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}elseif($op == 'post'){
    $id = floor(trim($_GPC['id']));
    if(check_id($id)){
        $item = pdo_get('egg_device',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    if($_W['isajax']){
        $data = array(
            'device_no' => trim($_GPC['device_no']),
            'is_active' => floor(trim($_GPC['is_active'])) == 1?1:0,
            'agent_wx' => trim($_GPC['agent_wx']),
            'agent_name' => trim($_GPC['agent_name']),
            'price' => round(floatval(trim($_GPC['price'])),2)
        );
        if(empty($data['device_no'])){
            message('请输入设备号','','error');
        }
        $is_exist = pdo_get('egg_device',array(
            'uniacid' => $_W['uniacid'],
            'device_no' => $data['device_no']
        ));
        if(empty($item)){ //新增
            if(check_data($is_exist)){
                message('设备号已添加，请勿重复添加','','error');
            }
            $tip = "添加";
            $data['createtime'] = TIMESTAMP;
            $data['uniacid'] = $_W['uniacid'];
            $status = pdo_insert('egg_device',$data);
        }else{
            if(check_data($is_exist)){
                if($is_exist['device_no'] != $item['device_no']){
                    message('设备号已添加，请勿重复添加','','error');
                }
            }
            $data['updatetime'] =TIMESTAMP;
            $tip ="修改";
            $status = pdo_update('egg_device',$data,array(
                'uniacid' => $_W['uniacid'],
                'id' => $id
            ));
        }
        if(!$status){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
}
include $this->template('device');
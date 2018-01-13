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
            message('请选择要删除的推广信息','','error');
        }
        $delete_status = pdo_query("DELETE FROM ".tablename('egg_ad')." WHERE uniacid='{$_W['uniacid']}' AND id IN (".implode(',',$ids).")");
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
    $keyword = trim($_GPC['keyword']);
    if(!empty($keyword)){
        $where .= " AND title LIKE '%{$keyword}%'";
    }
    $is_display = $_GPC['is_display'];
    if(check_data($is_display)){
        $where .= " AND is_active IN (".implode(',',$is_display).")";
    }
    $type = $_GPC['type'];
    if(check_data($type)){
        $where .= " AND type IN (".implode(',',$type).")";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('egg_ad')." WHERE {$where} ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('egg_ad')." WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}elseif($op == 'post'){
    $id = floor(trim($_GPC['id']));
    if(check_id($id)){
        $item = pdo_get('egg_ad',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'thumb' => trim($_GPC['thumb']),
            'url' => trim($_GPC['url']),
            'type' => floor(trim($_GPC['type'])) == 1?1:0,
            'desc' => $_GPC['desc'],
            'detail' => $_GPC['detail'],
            'wx_code' => trim($_GPC['wx_code']),
            'order_by' => floor(trim($_GPC['order_by'])),
            'is_display' => floor(trim($_GPC['is_display'])) == 1?1:0
        );
        if(empty($data['title'])){
            message('请输入标题','','error');
        }
        if(empty($data['wx_code'])){
            message('请上传推广微信二维码','','error');
        }
        if(empty($item)){ //新增
            $tip = "添加";
            $data['createtime'] = TIMESTAMP;
            $data['uniacid'] = $_W['uniacid'];
            $status = pdo_insert('egg_ad',$data);
        }else{
            $data['updatetime'] =TIMESTAMP;
            $tip ="修改";
            $status = pdo_update('egg_ad',$data,array(
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
include $this->template('ad');
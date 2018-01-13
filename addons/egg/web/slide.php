<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/15
 * Time: 11:00
 */
if($op == 'display'){
    if($_W['isajax']){
        $ids = $_GPC['ids'];
        if(!check_data($ids)){
            message('请选择要删除的轮播图','','error');
        }
        $delete_status = pdo_query("DELETE FROM ".tablename('egg_slide')." WHERE uniacid='{$_W['uniacid']}' AND id IN (".implode(',',$ids).")");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $list = pdo_getall("egg_slide",array(
        'uniacid'=>$_W['uniacid']
    ));
}elseif($op == 'post'){
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = pdo_get('egg_slide',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'thumb' => trim($_GPC['thumb']),
            'link' => trim($_GPC['link']),
            'is_display' => floor(trim($_GPC['is_display'])) == 1?1:0,
            'order_by' => floor(trim($_GPC['order_by']))
        );
        if(empty($data['title'])){
            message('请输入轮播图标题','','error');
        }
        if(empty($data['thumb'])){
            message('请上传轮播图图片','','error');
        }
        if(empty($item)){
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $tip = "添加";
            $flag = pdo_insert('egg_slide',$data);
        }else{
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = pdo_update('egg_slide',$data,array(
                'uniacid' => $_W['uniacid'],
                'id' => $id
            ));
        }
        if(!$flag){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
}
include $this->template('slide');
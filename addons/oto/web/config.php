<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/15
 * Time: 11:00
 */
if($op == 'display'){
    $item = OtoModel::getPlatformIndexSetting();
    if($_W['isajax']){
        $data = array(
            'setting' => empty($_GPC['setting'])?'':iserializer($_GPC['setting']),
            'store_type' => STORE_TYPE_OTO
        );
        if(empty($item)){
            $data['createtime'] = TIMESTAMP;
            $data['uniacid'] = $_W['uniacid'];
            $flag = OtoModel::insertPlatformIndexSetting($data);
        }else{
            $data['updatetime'] = TIMESTAMP;
            $flag = OtoModel::updatePlatformIndexSetting($data);
        }
        if(!$flag){
            message('设置失败','','error');
        }
        message('设置成功',referer(),'success');
    }
}elseif($op == 'slide'){
    $list = OtoModel::getPlatformSlideList();
}elseif($op == 'post_slide'){
    if(trim($_GPC['ac']) == 'delete'){
        $delete_status = OtoModel::deletePlatformSlideByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = OtoModel::getPlatformSlideById($id);
    }
    if($_W['isajax']){
        $data = array(
            'store_type' => STORE_TYPE_OTO,
            'title' => trim($_GPC['title']),
            'thumb' => trim($_GPC['thumb']),
            'link' => trim($_GPC['link']),
            'is_display' => floor(trim($_GPC['is_display'])) == DISPLAY_YES?DISPLAY_YES:DISPLAY_NO,
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
            $flag = OtoModel::insertPlatformSlide($data);
        }else{
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = OtoModel::updatePlatformSlide($id,$data);
        }
        if(!$flag){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
}
include $this->template('config');
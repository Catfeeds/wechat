<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/7
 * Time: 22:42
 */
load()->model('store');
$do = in_array(trim($_GPC['do']),array('display','slide','post_slide'))?trim($_GPC['do']):'display';
if($do == 'display'){
    $item = StoreModel::getStoreShopConfig();
    if($_W['isajax']){
        $data = array(
            'setting' => empty($_GPC['setting'])?'':iserializer($_GPC['setting']),
            'store_type' => $_W['store_type']
        );
        if(empty($item)){
            $data['createtime'] = TIMESTAMP;
            $data['uniacid'] = $_W['uniacid'];
            $data['store_id'] = $_W['store_id'];
            $flag = StoreModel::insertStoreShopConfig($data);
        }else{
            $data['updatetime'] = TIMESTAMP;
            $flag = StoreModel::updateStoreShopConfig($data);
        }
        if(!$flag){
            message('设置失败','','error');
        }
        message('设置成功',referer(),'success');
    }
}elseif($do == 'slide'){
    $list = StoreModel::getStoreShopAllSlide();
}elseif($do == 'post_slide'){
    if(trim($_GPC['ac']) == 'delete'){
        $delete_status = StoreModel::deleteStoreSlideByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = StoreModel::getStoreSlideInfoById($id);
    }
    if($_W['isajax']){
        $data = array(
            'store_type' => $_W['store_type'],
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
            $data['store_id'] = $_W['store_id'];
            $data['createtime'] = TIMESTAMP;
            $tip = "添加";
            $flag = StoreModel::insertStoreSlideInfo($data);
        }else{
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = StoreModel::updateStoreSlideInfo($id,$data);
        }
        if(!$flag){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
}

template('shop/config');
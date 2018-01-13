<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/3/16
 * Time: 14:58
 */
load()->model('store');
$do = in_array(trim($_GPC['do']),array('display','post'))?trim($_GPC['do']):'display';
if($do == 'display'){
    $list = getCategoryTree(StoreModel::getStoreGoodsCategoryList(CATEGORY_SHOW_ALL,trim($_GPC['keyword']),floor(trim($_GPC['display_status'])),$pindex,$psize,EXPORT_YES));
}elseif($do == 'post'){
    if(trim($_GPC['ac']) == 'delete'){
        $delete_status = StoreModel::deleteStoreGoodsCategoryByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $parent_category_list = StoreModel::getStoreGoodsCategoryList(CATEGORY_SHOW_PARENT,'',0,0,15,EXPORT_YES);
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = StoreModel::getStoreGoodsCategoryInfoById($id);
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'parent_id' => floor(trim($_GPC['parent_id'])),
            'is_display' => floor(trim($_GPC['is_display'])) == DISPLAY_YES?DISPLAY_YES:DISPLAY_NO,
            'thumb' => trim($_GPC['thumb']),
            'order_by' => floor(trim($_GPC['order_by']))
        );
        if(empty($data['title'])){
            message('请输入分类标题','','error');
        }

        if($data['parent_id'] != 0){
            $parent_category = StoreModel::getStoreGoodsCategoryInfoById($data['parent_id']);
            if(empty($parent_category)){
                message('父级分类不存在','','error');
            }
        }
        if(empty($item)){
            //插入
            $data['uniacid'] = $_W['uniacid'];
            $data['store_id'] = $_W['store_id'];
            $data['store_type'] = $_W['store_type'];
            $data['createtime'] = TIMESTAMP;
            $flag = StoreModel::insertStoreGoodsCategoryInfo($data);
            $tip = "添加";
        }else{
            //修改
            if($item['parent_id'] == 0 && $data['parent_id'] != 0){
                StoreModel::deleteStoreGoodsCategoryByParentId($item['id']);
            }
            $data['updatetime'] = TIMESTAMP;
            $flag = StoreModel::updateStoreGoodsCategoryInfo($id,$data);
            $tip = "修改";
        }
        if(!$flag){
            message($tip.'失败','','error');
        }
        message($tip.'成功',referer(),'success');
    }
}
template('goods/category');
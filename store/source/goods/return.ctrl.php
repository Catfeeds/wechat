<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/12
 * Time: 13:28
 */
$do = in_array(trim($_GPC['do']),array('display','post'))?trim($_GPC['do']):'display';
load()->model('store');
if($do == 'display'){
    $psize = 20;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $list = StoreModel::getStoreReturnGoodsAddressList($pindex,$psize);
    $pager = pagination(StoreModel::getStoreReturnGoodsAddressCount(),floor(trim($_GPC['page'])),$psize);
}elseif($do == 'post'){
    if(trim($_GPC['ac']) == 'delete'){
        $delete_status = StoreModel::deleteStoreReturnAddressByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = StoreModel::getStoreReturnAddressInfoById($id);
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'username' => trim($_GPC['username']),
            'tel' => trim($_GPC['tel']),
            'postage_code' => trim($_GPC['postage_code']),
            'province' => trim($_GPC['area']['province']),
            'city' => trim($_GPC['area']['city']),
            'district' => trim($_GPC['area']['district']),
            'address' => trim($_GPC['address']),
            'is_display' => floor(trim($_GPC['is_display'])) == DISPLAY_YES?DISPLAY_YES:DISPLAY_NO,
            'is_default' => floor(trim($_GPC['is_default'])) == IS_DEFAULT?IS_DEFAULT:NOT_DEFAULT,
            'order_by' => floor(trim($_GPC['order_by']))
        );
        if(empty($data['title'])){
            message('请输入退货地址标题','','error');
        }
        if(empty($item)){
            $data['uniacid'] = $_W['uniacid'];
            $data['store_id'] = $_W['store_id'];
            $data['store_type'] = $_W['store_type'];
            $data['createtime'] = TIMESTAMP;
            $tip = '添加';
            $flag = StoreModel::insertStoreReturnAddressInfo($data);
        }else{
            $tip = '失败';
            $data['updatetime'] = TIMESTAMP;
            $flag = StoreModel::updateStoreReturnAddressInfo($id,$data);
        }
        if(!$flag){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
}
template('goods/return');
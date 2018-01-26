<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/12
 * Time: 13:27
 */
$do = in_array(trim($_GPC['do']),array('display','post'))?trim($_GPC['do']):'display';
load()->model('store');
if($do == 'display'){
    $psize = 20;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $list = StoreModel::getStoreDeliverList($pindex,$psize);
    $pager = pagination(StoreModel::getStoreDeliverCount(),floor(trim($_GPC['page'])),$psize);
}elseif($do == 'post'){
    if(trim($_GPC['ac']) == 'delete'){
        $delete_status = StoreModel::deleteStoreDeliverByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = StoreModel::getStoreDeliverInfoById($id);
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'calc_type' => floor(trim($_GPC['calc_type'])) == CALC_BY_NUM?CALC_BY_NUM:CALC_BY_WEIGHT,
            'first_weight' => doubleval(trim($_GPC['first_weight'])),
            'first_weight_fee' => doubleval(trim($_GPC['first_weight_fee'])),
            'sequel_weight' => doubleval(trim($_GPC['sequel_weight'])),
            'sequel_weight_fee' => doubleval(trim($_GPC['sequel_weight_fee'])),
            'first_num'=> floor(trim($_GPC['first_num'])),
            'first_num_fee' => doubleval(trim($_GPC['first_num_fee'])),
            'sequel_num' => floor(trim($_GPC['sequel_num'])),
            'sequel_num_fee' => doubleval(trim($_GPC['sequel_num_fee'])),
            'is_default' => floor(trim($_GPC['is_default'])) == IS_DEFAULT?IS_DEFAULT:NOT_DEFAULT,
            'is_display' => floor(trim($_GPC['is_display'])) == DISPLAY_YES?DISPLAY_YES:DISPLAY_NO,
            'order_by' => floor(trim($_GPC['order_by']))
        );
        if(empty($data['title'])){
            message('请输入运费模板标题','','error');
        }
        if(empty($item)){
            $data['uniacid'] = $_W['uniacid'];
            $data['store_id'] = $_W['store_id'];
            $data['store_type'] = $_W['store_type'];
            $data['createtime'] = TIMESTAMP;
            $tip = '添加';
            $flag = StoreModel::insertStoreDeliverInfo($data);
        }else{
            $tip = '失败';
            $data['updatetime'] = TIMESTAMP;
            $flag = StoreModel::updateStoreDeliverInfoById($id,$data);
        }
        if(!$flag){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
}
template('goods/deliver');
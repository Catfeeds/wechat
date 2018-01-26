<?php
if(empty($do)){$do = 'display';}
if($do == 'display'){
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $goods_name = trim($_GPC['goods_name']);
    $store_name = trim($_GPC['store_name']);
    $is_check = $_GPC['is_check'];
    $province = $_W['province'];
    $city = $_W['city'];
    $district = $_GPC['area']['district'];
    if($_W['is_big_city']){
        $district = $_W['district'];
    }
    $list = ServicerModel::getOldGoodsList($goods_name,$store_name,$is_check,$province,$city,$district,$pindex,$psize);
    $pager = pagination(ServicerModel::getOldGoodsCount($goods_name,$store_name,$is_check,$province,$city,$district),floor(trim($_GPC['page'])),$psize);
}elseif($do == 'post'){
    if(trim($_GPC['ac']) == 'delete'){
        $delete_status = ServicerModel::deleteOldGoodsByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $id = floor(trim($_GPC['id']));
    $status = ServicerModel::checkOldGoods($id,floor(trim($_GPC['is_check'])) == CHECK_PASS?CHECK_PASS:CHECK_NOT_PASS);
    if(!$status){
        message('操作失败','','error');
    }
    message('操作成功',referer(),'success');
}
template('goods/display');
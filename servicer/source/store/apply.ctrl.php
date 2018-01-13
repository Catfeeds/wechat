<?php
if(empty($do)){$do = 'display';}
if($do == 'display'){
    if($_W['isajax']){
        $id = floor(trim($_GPC['id']));
        //添加、删除、修改商家分类
        if(trim($_GPC['ac']) == 'delete'){
            $delete_status = ServicerModel::deleteStoreApplyById($id);
            if(!$delete_status){
                message('删除失败！','','error');
            }
            message('删除成功！',referer(),'success');
        }
        $is_check = floor(trim($_GPC['is_check'])) == CHECK_PASS?CHECK_PASS:CHECK_NOT_PASS;
        $tip = $is_check == CHECK_PASS?'通过审核':'取消通过';
        $check_status = ServicerModel::updateStoreApplyInfoById($id,array('is_check'=>$is_check));
        if(!$check_status){
            message("{$tip}失败！",'','error');
        }
        message("{$tip}成功！",'','success');
    }
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $province = $_W['province'];
    $city = $_W['city'];
    $district = trim($_GPC['area']['district']);
    if($_W['is_big_city']){
        $district = $_W['district'];
    }
    $is_check = $_GPC['is_check'];
    $starttime = empty($_GPC['createtime']['start']) ? strtotime('-90 days') : strtotime($_GPC['createtime']['start']);
    $endtime = empty($_GPC['createtime']['end']) ? TIMESTAMP + 86399 : strtotime($_GPC['createtime']['end']) + 86399;
    $list = $list = ServicerModel::getStoreApplyList($keyword,$province,$city,$district,$is_check,$starttime,$endtime,$pindex,$psize,EXPORT_NO);
    $pager = pagination(ServicerModel::getStoreApplyCount($keyword,$province,$city,$district,$is_check,$starttime,$endtime),floor(trim($_GPC['page'])),$psize);
}
template('store/apply');
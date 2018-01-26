<?php
load()->model('store');
if(empty($do)){$do = 'display';}
if($do == 'display'){
    $list = StoreModel::getStoreBalanceAccountList();
}elseif($do == 'post'){
    if(trim($_GPC['ac']) == 'delete'){
        $delete_status = StoreModel::deleteStoreBalanceAccountByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $id= floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = StoreModel::getStoreBalanceAccountInfo($id);
    }
    if($_W['isajax']){
        $data = array(
            'username' => trim($_GPC['username']),
            'tel' => trim($_GPC['tel']),
            'method' => floor(trim($_GPC['method'])) == STORE_BALANCE_TYPE_BANK?STORE_BALANCE_TYPE_BANK:STORE_BALANCE_TYPE_ALIPAY,
            'info' => iserializer($_GPC['account'])
        );
        if(empty($data['username'])){
            message('请输入负责人姓名','','error');
        }
        if(empty($data['tel'])){
            message('请输入负责人电话','','error');
        }
        if(empty($_GPC['account']['no'])){
            message('请输入账号','','error');
        }
        if(empty($_GPC['account']['bank']) && $data['method'] == STORE_BALANCE_TYPE_BANK){
            message('请输入开户行','','error');
        }
        if(empty($_GPC['account']['name'])){
            message('请输入账户姓名','','error');
        }
        if(!empty($item) && is_array($item)){
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $status = StoreModel::updateStoreBalanceAccountInfoById($id,$data);
        }else{
            $data['uniacid'] = $_W['uniacid'];
            $data['store_id'] = $_W['store_id'];
            $data['store_type'] = $_W['store_type'];
            $data['createtime'] = TIMESTAMP;
            $tip = "添加";
            $status = StoreModel::insertStoreBalanceAccountInfo($data);
        }
        if(!$status){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
}
template('balance/account');
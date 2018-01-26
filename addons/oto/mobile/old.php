<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/6/7
 * Time: 12:13
 */
load()->func('check');
if($op == 'display'){
    $id = floor(trim($_GPC['id']));
    $goods = pdo_get('old_goods',array(
        'uniacid'=>$_W['uniacid'],
        'id'=>$id
    ));
    if(!check_data($goods)){
        message('商品不存在','','error');
    }
    if($goods['total'] <= 0){
        message('商品库存不足','','error');
    }
    if(check_data($goods)){
        $goods['thumbs'] = json_decode($goods['thumbs'],true);
        $goods['createtime'] = date('Y-m-d H:i',$goods['createtime']);
    }
    $pay_url = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=mc&a=cashier&do=pay&cashier_uid={$goods['uid']}&goods_id={$goods['id']}&auth=".payPersonAuthEncode($goods['uid'],$_W['uniacid']);
}
include $this->template('old');
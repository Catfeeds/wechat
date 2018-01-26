<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/6/23
 * Time: 17:30
 */
if($op == 'display'){
    $item = pdo_get('fangyuanbao_shop_config',array(
        'uniacid' => $_W['uniacid']
    ));
    if(!empty($item['setting'])){
        $item['setting'] = iunserializer($item['setting']);
    }
    if($_W['isajax']){
        $data = array(
            'setting' => iserializer($_GPC['setting'])
        );
        if(empty($item)){
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $status = pdo_insert('fangyuanbao_shop_config',$data);
        }else{
            $data['updatetime'] = TIMESTAMP;
            $status = pdo_update('fangyuanbao_shop_config',$data,array('uniacid'=>$_W['uniacid']));
        }
        if(!$status){
            message('设置失败','','error');
        }
        message('设置成功',referer(),'success');
    }
}
include $this->template('shop');
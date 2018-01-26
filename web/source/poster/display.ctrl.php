<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/5/20
 * Time: 10:31
 */
$item = pdo_get('uni_settings',array(
    'uniacid' => $_W['uniacid']
));
if(!empty($item) && is_array($item)){
    if(!empty($item['poster'])){
        $item['poster'] = iunserializer($item['poster']);
    }
}
if($_W['isajax']){
    if(!empty($item) && is_array($item)){
        $status = pdo_update('uni_settings',array(
            'poster' => iserializer($_GPC['poster']),
            'updatetime' => TIMESTAMP
        ),array(
            'uniacid' => $_W['uniacid']
        ));
    }else{
        $status = pdo_insert('uni_settings',array(
            'uniacid' => $_W['uniacid'],
            'poster' => iserializer($_GPC['poster']),
            'createtime' => TIMESTAMP
        ));
    }
    if(!$status){
        message('设置失败','','error');
    }
    message('设置成功','','success');
}
template('poster/display');
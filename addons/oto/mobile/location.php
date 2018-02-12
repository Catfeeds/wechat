<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/15
 * Time: 10:57
 */
if($op == 'save'){
    if($_W['isajax']){
        $location = array(
            'address' => trim($_GPC['address']),
            'city' => trim($_GPC['city']),
            'district' => trim($_GPC['district']),
            'province' => trim($_GPC['province']),
            'lng' => doubleval(trim($_GPC['lng'])),
            'lat' => doubleval(trim($_GPC['lat'])),
            'is_default' => '2' //标识GPS定位，精准定位
        );
        if(!isset($_SESSION)){
            session_start();
        }
        $_SESSION['__location'] = json_encode($location);
        message($location,$this->createMobileUrl('index'),'success');
    }
    message('请求方式错误','','error');
}
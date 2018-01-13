<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017-09-29
 * Time: 23:16
 */
$do = empty($_GPC['do'])?'display':trim($_GPC['do']);
if($do == 'save'){
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
        message($location,"{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&do=index&m=sj_news",'success');
    }
    message('请求方式错误','','error');
}
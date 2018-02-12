<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/5/25
 * Time: 8:36
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
if($do == 'display'){
    load()->app('tpl');
    load()->model('mc');
    $item = mc_fetch($_W['member']['uid']);
    if($_W['isajax']){
        if(empty($_GPC['area']['city']) || empty($_GPC['area']['province']) || empty($_GPC['area']['district'])){
            message('请选择所在地址','','error');
        }
        if($_GPC['area']['city'] != $_GPC['re_area']['city'] || $_GPC['area']['province'] != $_GPC['re_area']['province'] || $_GPC['area']['district'] != $_GPC['re_area']['district']){
            message('两次选择的地址不一致','','error');
        }
        $status = mc_update($_W['member']['uid'], array(
            'province' => $_GPC['area']['province'],
            'city' => $_GPC['area']['city'],
            'district' => $_GPC['area']['district'],
            'updatetime' => TIMESTAMP
        ));
        if(!$status){
            message('设置失败','','error');
        }
        message('设置成功',referer(),'success');
    }
}
template('set/location');
<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/6/2
 * Time: 12:51
 */
if($do == 'display'){
    load()->func('check');
    $type = floor(trim($_GPC['type']));
    $sys = pdo_get('shop_config',array(
        'uniacid' => $_W['uniacid']
    ));
    if(check_data($sys)){
        $sys['setting'] = iunserializer($sys['setting']);
    }
    if($type == ARTICLE_OLD_PUSH){
        $content = htmlspecialchars_decode($sys['setting']['oto_old_apply_desc']);
        $title = "方源实体店二手货用户协议";
    }else{
        $content = htmlspecialchars_decode($sys['setting']['oto_apply_desc']);
        $title = "方源实体店商家申请协议";
    }
}
template('article/display');
<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017-10-31
 * Time: 16:36
 */
if($op == 'display'){
    $config = pdo_get('sj_news_dis_config',array(
        'uniacid' => $_W['uniacid']
    ));
    $setting = array();
    if(!empty($config['setting'])){
        $config['setting'] = iunserializer($config['setting']);
        if(!empty($config['setting']) && is_array($config['setting'])){
            $setting = $config['setting'];
        }
    }
    if(!check_data($setting)){
        message('系统未进行相关设置','','error');
    }
    if($_W['isajax']){
        $data = array(
            'sj_uniacid' => $_W['uniacid'],
            'sj_uid' => $_W['member']['uid'],
            'order_no' => generateOrderSnByBuyTodayTradeCount(14),
            'price' => $setting['price'],
            'createtime' => TIMESTAMP
        );
    }
    include $this->template('extension');
}

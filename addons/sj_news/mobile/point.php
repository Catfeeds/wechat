<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/7
 * Time: 15:56
 */
if($op == 'display'){
    load()->classs('point');
    $point = new Point();
    $tencent_js_key = $point -> getTencentJsKey();
    if($_W['isajax']){
        $list = $point->getTencentKeywordTips(trim($_GPC['keywords']));
        if(!empty($list) && is_array($list)){
            message($list,'','success');
        }
        message('没有找到相关地点','','error');
    }
}
include $this->template('point');
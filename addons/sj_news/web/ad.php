<?php
if($op == 'display'){

}elseif($op == 'order'){

}elseif($op == 'config'){
    $item = pdo_get('sj_news_ad_config',array(
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
            //添加
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $status = pdo_insert('sj_news_ad_config',$data);
        }else{
            //修改
            $data['updatetime'] = TIMESTAMP;
            $status = pdo_update('sj_news_ad_config',$data,array(
                'uniacid' => $_W['uniacid']
            ));
        }
        if(!$status){
            message('设置失败','','error');
        }
        message('设置成功',referer(),'success');
    }
}
include $this->template('ad');
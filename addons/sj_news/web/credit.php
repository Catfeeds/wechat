<?php
load()->func('check');
if($op == 'display'){
    $item = pdo_get('sj_news_credit_config',array('uniacid'=>$_W['uniacid']));
    if(!empty($item) && is_array($item)){
        if(!empty($item['setting'])){
            $item['setting'] = iunserializer($item['setting']);
        }
    }
    if($_W['isajax']){
        $data = array(
            'setting' => empty($_GPC['setting'])?'':iserializer($_GPC['setting'])
        );
        if(empty($item)){
            $data['createtime'] = TIMESTAMP;
            $data['uniacid'] = $_W['uniacid'];
            $flag = pdo_insert('sj_news_credit_config',$data);
        }else{
            $data['updatetime'] = TIMESTAMP;
            $flag = pdo_update('sj_news_credit_config',$data,array(
                'uniacid'=>$_W['uniacid']
            ));
        }
        if(!$flag){
            message('设置失败','','error');
        }
        message('设置成功',referer(),'success');
    }
}
include $this->template('credit');
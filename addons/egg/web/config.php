<?php
load()->func('check');
if($op == 'display'){
    $config = pdo_get('egg_config',array(
        'uniacid'=>$_W['uniacid']
    ));
    if(check_data($config)){
        if(!empty($config['setting'])){
            $config['setting'] = iunserializer($config['setting']);
        }
    }
    if($_W['isajax']){
        $data = array(
            'uniacid' => $_W['uniacid'],
            'setting' => iserializer($_GPC['setting'])
        );
        if(check_data($config)){
            $data['updatetime'] = TIMESTAMP;
            $status = pdo_update('egg_config',$data,array(
                'uniacid'=>$_W['uniacid']
            ));
        }else{
            $data['createtime'] = TIMESTAMP;
            $status = pdo_insert('egg_config',$data);
        }
        if(!$status){
            message('设置失败','','error');
        }
        message('设置成功',referer(),'success');
    }
}
include $this->template('config');
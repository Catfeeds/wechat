<?php
load()->func('check');
if($op == 'display'){
    $config = pdo_get('vapp_config',array('uniacid'=>$_W['uniacid']));
    if(!empty($config) && is_array($config)){
        if(!empty($config['setting'])){
            $config['setting'] = iunserializer($config['setting']);
        }
    }
    if($_W['isajax']){
        $data = array(
            'setting' => empty($_GPC['setting'])?'':iserializer($_GPC['setting'])
        );
        if(empty($config)){
            $data['createtime'] = TIMESTAMP;
            $data['uniacid'] = $_W['uniacid'];
            $flag = pdo_insert('vapp_config',$data);
        }else{
            $data['updatetime'] = TIMESTAMP;
            $flag = pdo_update('vapp_config',$data,array(
                'uniacid'=>$_W['uniacid']
            ));
        }
        if(!$flag){
            message('设置失败','','error');
        }
        message('设置成功',referer(),'success');
    }
}
include $this->template('config');
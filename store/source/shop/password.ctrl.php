<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/3/14
 * Time: 23:31
 */
load()->model('store');
$do = in_array(trim($_GPC['do']),array('display'))?trim($_GPC['do']):'display';
if($do == 'display'){
    if($_W['isajax']){
        $data = array(
            'password' => $_GPC['password'],
            'new_password' => $_GPC['new_password'],
            're_new_password' => $_GPC['re_new_password'],
            'verify' => $_GPC['verify']
        );
        $error = array(
            'password' => '请输入旧密码',
            'new_password' => '请输入新密码',
            're_new_password' => '请再次输入新密码',
            'verify' => '请输入验证码'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        $new_password_len = mb_strlen($data['new_password'],'utf-8');
        if($new_password_len < 6){
            message('新密码不能少于6位','','error');
        }
        if($data['new_password'] != $data['re_new_password']){
            message('新密码输入不一致','','error');
        }
        $captcha = checkcaptcha($data['verify']);
        if (empty($captcha)) {
            message('验证码错误','','error');
        }
        load()->model('user');
        $store_info = StoreModel::getStoreInfo();
        if(empty($store_info)){
            message('店铺信息不存在','','error');
        }
        $hash_password = user_hash($data['password'],$store_info['salt']);
        if($hash_password != $store_info['password']){
            message('旧密码输入错误','','error');
        }
        $update_status = StoreModel::updateStoreInfo(array('password'=>user_hash($data['new_password'],$store_info['salt'])));
        if(!$update_status){
            message('密码修改失败','','error');
        }
        message('密码修改成功',url('user/logout'),'success');
    }
}
template('shop/password');
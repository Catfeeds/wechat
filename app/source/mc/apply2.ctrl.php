<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/14
 * Time: 10:29
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
load()->app('tpl');
load()->func('check');
if($do == 'display'){
    if($_W['isajax']){
        $data = array(
            'uniacid'=>$_W['uniacid'],
            'uid'=>$_W['member']['uid'],
            'name' => trim($_GPC['name']),
            'mobile' => trim($_GPC['mobile']),
            'province' => $_GPC['area']['province'],
            'city' => $_GPC['area']['city'],
            'district' => $_GPC['area']['district'],
            'weixin' => trim($_GPC['weixin']),
            'ip'=>CLIENT_IP,
            'createtime' => TIMESTAMP
        );
        $error = array(
            'name' => '请输入姓名',
            'mobile' => '请输入手机号',
            'weixin' => '请输入微信号',
            'province' => '请选择省份',
            'city' => '请选择城市',
            'district' => '请选择区县',
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        $code = trim($_GPC['code']);
        if(empty($code)){
            message('请输入验证码','','error');
        }
        $result = checkMobileCode($data['mobile'],$code);
        if(is_error($result)){
            message($result['message'],'','error');
        }
        $status = pdo_insert('agent_apply',$data);
        if(!$status){
            message('提交失败','','error');
        }
        message('提交成功，请耐心等待审核',referer(),'success');
    }
}
template('mc/apply2');
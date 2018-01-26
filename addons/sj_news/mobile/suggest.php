<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/9/24
 * Time: 13:34
 */
checkauth();
load()->model('mc');
$member = mc_fetch($_W['member']['uid']);
if($op == 'display'){
    if($_W['isajax']){
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'name' => trim($_GPC['name']),
            'mobile' => trim($_GPC['mobile']),
            'content' => $_GPC['content'],
            'province' => $_W['location']['province'],
            'city' => $_W['location']['city'],
            'ip' => CLIENT_IP,
            'createtime' => TIMESTAMP
        );
        $error = array(
            'name' => '请输入姓名',
            'mobile' => '请输入手机号',
            'content' => '请输入内容',
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        if(!check_mobile($data['mobile'])){
            message('手机号格式错误','','error');
        }
        $status = pdo_insert('sj_news_suggest',$data);
        if(!$status){
            message('发表失败，请重试','','error');
        }
        message('谢谢您的留言，祝您生活愉快^-^',referer(),'success');
    }
}
include $this->template('suggest');
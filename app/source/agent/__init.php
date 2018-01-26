<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/6/1
 * Time: 16:53
 */
load()->func('check');
load()->model('agent');
load()->app('tpl');
defined('IN_IA') or exit('Access Denied');
$agent = pdo_get('agent_user',array(
    'uniacid' => $_W['uniacid'],
    'uid' => $_W['member']['uid']
));
if(!check_data($agent)){
    message('您还不是代理，不能进入','','error');
}
if($agent['is_check'] != CHECK_PASS){
    message('您的账号已被禁用','','error');
}
$_W['province'] = $agent['province'];
$_W['city'] = $agent['city'];
$_W['district'] = $agent['district'];
$_W['is_big_city'] = false;
$_W['tips'] = "区域：{$agent['province']}{$agent['city']}";
if(in_array($agent['city'],AgentModel::$bigCity)){
    $_W['is_big_city'] = true;
    $_W['tips'] .= "{$agent['district']}";
}


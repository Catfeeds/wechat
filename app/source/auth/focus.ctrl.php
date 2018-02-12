<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/12
 * Time: 19:20
 */
defined('IN_IA') or exit('Access Denied');
$openid = $_W['openid'];
$do = empty($_GPC['do'])?'display':trim($_GPC['do']);
if(!in_array($do,array('display','forget'))){
    message('未知操作','','error');
}
$uniaccount = pdo_get('uni_account',array(
    'uniacid' => $_W['uniacid']
));
template('auth/focus');
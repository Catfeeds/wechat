<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/10
 * Time: 10:10
 */
if(!isset($_SESSION)){
    session_start();
}
if(isset($_SESSION['uid'])){
    unset($_SESSION['uid']);
}
if(isset($_SESSION['openid'])){
    unset($_SESSION['openid']);
}
message("退出成功！",url('auth/login',array(
    'forward' => base64_encode("i={$_W['uniacid']}&c=entry&do=user&m=oto")
)),"success");
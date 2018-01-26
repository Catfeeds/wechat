<?php
/**
 * 
 * 
 */
defined('IN_IA') or exit('Access Denied');
isetcookie('__store_session', '', -10000);

$forward = $_GPC['forward'];
if(empty($forward)) {
	$forward = './?refersh';
}
header('Location:' . url('user/login'));

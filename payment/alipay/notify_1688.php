<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require '../../framework/bootstrap.inc.php';
if($_GPC["code"] && $_GPC['state']){
	load()->func('logging');
	$code = $_GPC['code'];
	exit("success");
}

<?php
require '../../framework/bootstrap.inc.php';
load()->classs("pay.notify");
load()->func('logging');
if (!empty($_GPC['out_trade_no'])) {
	if(md5('AliPayAuthKey'.$_GPC['out_trade_no']) == $_GPC['body']){
		$data = array(
			"out_trade_no"=> $_GPC['out_trade_no'],
			"transaction_id"=>$_GPC['trade_no']
		);
		$notify = new PayNotify($data);
		$status = $notify -> finish();
		if(!$status){
			logging_run('支付失败','ali','aliPay01');
		}
		exit('success');
	}
}
exit("fail");
<?php
require '../../framework/bootstrap.inc.php';
load()->func('logging');
load()->classs("pay.notify");
if(empty($_GPC['out_trade_no'])){
	echo "<script language='javascript'>alert('订单不存在，请返回重试！');history.go(-1);</script>";
	exit;
}
?>
<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<?php
	//计算得出通知验证结果
	if(md5('AliPayAuthKey'.$_GPC['out_trade_no']) == $_GPC['body']){
		$data = array(
			"out_trade_no"=> $_GPC['out_trade_no'],
			"transaction_id"=>$_GPC['trade_no']
		);
		$notify = new PayNotify($data);
		$status = $notify -> finish();
		if(!$status){
			logging_run('支付失败','ali','aliPay02');
		}
		exit('<script>alert("支付成功");history.go(-1);</script>');
	}
	logging_run('验证失败','ali','aliPay03');
	exit('<script>alert("支付失败");history.go(-1);</script>');
	?>
	<title>支付宝手机网站支付接口</title>
</head>
<body></body>
</html>
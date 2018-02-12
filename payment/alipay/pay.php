<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require '../../framework/bootstrap.inc.php';
require '../../app/common/bootstrap.app.inc.php';
load()->app('common');
load()->app('template');
load()->model('payment');
$auth = payAuthDecode($_GPC['auth']);
if (empty($auth)) {
    message("支付参数错误！", "", "error");
}
list($out_trade_no, $uniacid) = explode("_", $auth);
if (empty($out_trade_no)) {
    message("交易号不存在！", "", "error");
}
$_W['uniacid'] = $uniacid;

//获取交易记录信息
$log = pdo_get('pay_log',array('uniacid'=>$uniacid,'out_trade_no'=>$out_trade_no));
if(empty($log)){
    message('支付记录不存在','','error');
}
if($log["pay_price"] <= 0){
    message("支付金额错误", "", "error");
}
if ($log['pay_status'] != PAY_NO) {
    message("订单已经支付，请勿重新付款！","", "info");
}
$setting = uni_setting($_W['uniacid'], array('payment'));
if (!is_array($setting['payment'])) {
    message('没有设定支付参数','','error');
}
load()->model('payment');
$setting = uni_setting($_W['uniacid'], array('payment'));
$alipay = $setting['payment']['alipay'];
if($alipay["switch"] ==0){
	 exit('没有设定支付参数.');
}
require_once(IA_ROOT ."/payment/alipay/alipay.config.php");
require_once(IA_ROOT ."/payment/alipay/lib/alipay_submit.class.php");
$parameter = array(
	"service"       => $alipay_config['service'],
	"partner"       => $alipay_config['partner'],
	"seller_id"  => $alipay_config['seller_id'],
	"payment_type"	=> $alipay_config['payment_type'],
	"notify_url"	=> $alipay_config['notify_url'],
	"return_url"	=> $alipay_config['return_url'],
	"_input_charset"	=> trim(strtolower($alipay_config['input_charset'])),
	"out_trade_no"	=> $out_trade_no,
	"subject"	=> $out_trade_no,
	"total_fee"	=> $log['pay_price'],
	"show_url"	=> getPayBackUrl($log['order_type']),
	"app_pay"	=> "Y",//启用此参数能唤起钱包APP支付宝
	"body"	=> md5('AliPayAuthKey'.$log['out_trade_no'])
);
//建立请求
$alipaySubmit = new AlipaySubmit($alipay_config);
$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
echo $html_text;
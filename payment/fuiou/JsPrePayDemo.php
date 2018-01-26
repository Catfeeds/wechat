<?php

include __DIR__ . DIRECTORY_SEPARATOR . 'JsPosPrepayRe.php';
include __DIR__ . DIRECTORY_SEPARATOR . 'JsPosPrepay.php';
include __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'httpful-master' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Httpful' .DIRECTORY_SEPARATOR . 'Bootstrap.php';
define('API_DOMAIN','http://pay.lcsw.cn/lcsw');
class JsPrePayDemo{
	
	public static function jsposPrePayRe($jsposPrePay) {
		if(!is_a($jsposPrePay, 'JsPosPrepay')){
			return new jsposPrePayRe();
		}
		$prePay_url= API_DOMAIN."/pay/100/jspay";
		$jsposPrePayRe = new JsPosPrepayRe();
		$access_token=$jsposPrePay->getAccess_token();
		$jsonParam = array(
			"pay_ver" => $jsposPrePay->getPay_ver(),
			"pay_type" => $jsposPrePay->getPay_type(),
			"service_id" => $jsposPrePay->getService_id(),
			"merchant_no" => $jsposPrePay->getMerchant_no(),
			"terminal_id" => $jsposPrePay->getTerminal_id(),
			"terminal_trace" => $jsposPrePay->getTerminal_trace(),
			"terminal_time" => $jsposPrePay->getTerminal_time(),
			"total_fee" => $jsposPrePay->getTotal_fee(),
			"open_id" => $jsposPrePay->getOpen_id(),
			"order_body" => $jsposPrePay->getOrder_body(),
			"notify_url" => $jsposPrePay->getNotify_url(),
			"attach" => $jsposPrePay->getAttach()
		);
		$param = http_build_query(array(
			'pay_ver' => $jsonParam['pay_ver'],
			'pay_type' => $jsonParam['pay_type'],
			'service_id' => $jsonParam['service_id'],
			'merchant_no' => $jsonParam['merchant_no'],
			'terminal_id' => $jsonParam['terminal_id'],
			'terminal_trace' => $jsonParam['terminal_trace'],
			'terminal_time' => $jsonParam['terminal_time'],
			'total_fee' => $jsonParam['total_fee'],
			'access_token' => $access_token
		));
		$sign = md5($param);
		$jsonParam['key_sign'] = $sign;
		return JsPrePayDemo::tojson($prePay_url,json_encode($jsonParam));
	}

	public static function getAccessToken($jsposPrePay) {
		if(!is_a($jsposPrePay, 'JsPosPrepay')){
			return new jsposPrePayRe();
		}
		$access_token=$jsposPrePay->getAccess_token();
		$prePay_url=API_DOMAIN."/pay/100/sign";
		$jsposPrePayRe = new JsPosPrepayRe();
		$jsonParam = array(
			"pay_ver" => $jsposPrePay->getPay_ver(),
			"service_id" => $jsposPrePay->getService_id(),
			"merchant_no" => $jsposPrePay->getMerchant_no(),
			"terminal_id" => $jsposPrePay->getTerminal_id(),
			"terminal_trace" => $jsposPrePay->getTerminal_trace(),
			"terminal_time" => $jsposPrePay->getTerminal_time(),
		);
		$param = "pay_ver={$jsonParam['pay_ver']}";
		$param.="&service_id={$jsonParam['service_id']}";
		$param.="&merchant_no={$jsonParam['merchant_no']}";
		$param.="&terminal_id={$jsonParam['terminal_id']}";
		$param.="&terminal_trace={$jsonParam['terminal_trace']}";
		$param.="&terminal_time={$jsonParam['terminal_time']}";
		$jsonParam['key_sign'] = md5($param);
		return JsPrePayDemo::tojson($prePay_url,json_encode($jsonParam));
	}

    public static function tojson($gateway, $jsonParam) {
		Httpful\Bootstrap::init();
		$xmlText = Httpful\Request::post($gateway)
			->sendsJson()
			->body($jsonParam)
			->send();
	    return $xmlText->body;
 	}

}

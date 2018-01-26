<?php
define('IN_MOBILE', true);
require '../../framework/bootstrap.inc.php';
load()->func('logging');
load()->classs("pay.notify");
$input = file_get_contents('php://input');
logging_run($input,'notify','wechat');
if (!empty($input) && empty($_GET['out_trade_no'])) {
	$data = json_decode($input, true);
    if(!empty($data) && is_array($data)){
        if($data['return_code'] == '01' && $data['result_code'] == '01'){
			//校验
			$_W['uniacid'] = $data['attach'];
			$data['transaction_id'] = $data['channel_trade_no'];
			$data['out_trade_no'] = $data['terminal_trace'];
			if(checkSign($data)){
				$notify = new PayNotify($data);
				$status = $notify -> finish();
				if (!$status) {
					exit(json_encode(array(
						'return_code' => '02',
						'return_msg' => $data['return_msg']
					)));
				}
			}
        }
        exit(json_encode(array(
            'return_code' => '01',
            'return_msg' => $data['return_msg']
        )));
    }
}

/**
 * @param $data
 * @return bool
 * 校验回调
 */
function checkSign($data){
	global $_W;
	$_W['uniacid'] = $_W['weid'] = $data['attach'];
	$setting = uni_setting($_W['uniacid'], array('payment'));
	if ($setting['payment']['fuiou']['merchant_no'] == $data['merchant_no'] && $setting['payment']['fuiou']['terminal_id'] == $data['terminal_id']) {
		return true;
	}
	return false;
}
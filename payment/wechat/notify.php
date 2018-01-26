<?php
define('IN_MOBILE', true);
require '../../framework/bootstrap.inc.php';
load()->func('logging');
load()->classs("pay.notify");
$input = file_get_contents('php://input');
logging_run($input,'notify','wechat');
if (!empty($input) && empty($_GET['out_trade_no'])) {
    $obj = isimplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
    $data = json_decode(json_encode($obj), true);
    if (empty($data)) {
        $result = array(
            'return_code' => 'FAIL',
            'return_msg' => ''
        );
        echo array2xml($result);
        exit;
    }
    if ($data['result_code'] != 'SUCCESS' || $data['return_code'] != 'SUCCESS') {
        $result = array(
            'return_code' => 'FAIL',
            'return_msg' => empty($data['return_msg']) ? $data['err_code_des'] : $data['return_msg']
        );
        echo array2xml($result);
        exit;
    }
    if (!checkSign($data)) {
        exit("FAIL");
    }
    $notify = new PayNotify($data);
    $status = $notify -> finish();
    if (!$status) {
        exit("FAIL");
    }
    exit("SUCCESS");
}
/**
 * @param $data
 * @return bool
 * 校验
 */
function checkSign($data) {
    global $_W;
    $_W['uniacid'] = $_W['weid'] = $data['attach'];
    $setting = uni_setting($_W['uniacid'], array('payment'));
    if (is_array($setting['payment'])) {
        $wechat = $setting['payment']['wechat'];
        if (!empty($wechat)) {
            ksort($data);
            $string1 = '';
            foreach ($data as $k => $v) {
                if ($v != '' && $k != 'sign') {
                    $string1 .= "{$k}={$v}&";
                }
            }
            $wechat['signkey'] = ($wechat['version'] == 1) ? $wechat['key'] : $wechat['signkey'];
            $sign = strtoupper(md5($string1 . "key={$wechat['signkey']}"));
            if ($sign == $data['sign']) {
                return true;
            }
        }
    }
    return false;
}

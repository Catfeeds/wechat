<?php
define('IN_MOBILE', true);
require '../../framework/bootstrap.inc.php';
require '../../app/common/bootstrap.app.inc.php';
load()->app('common');
load()->app('template');
load()->model('payment');
if($_W['container'] != 'wechat'){
    message('请在微信中打开','','error');
}
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
$wechat = $setting['payment']['wechat'];
$sql = 'SELECT `key`,`secret` FROM ' . tablename('account_wechats') . ' WHERE `acid`=:acid';
$row = pdo_fetch($sql, array(':acid' => $wechat['account']));
$wechat['appid'] = $row['key'];
$wechat['secret'] = $row['secret'];
$member = pdo_get('mc_mapping_fans',array(
    'uniacid' => $log['uniacid'],
    'uid' => $log['uid']
));
if(empty($member) || !is_array($member)){
    message('会员信息不存在','','error');
}
if(empty($member['openid'])){
    message('微信OpenID不存在','','error');
}
$params = array(
    "title" => "商城支付",
    'uniontid' => $log['out_trade_no'],
    'fee' => $log['pay_price'],
    'user' => $member['openid']
);
$wOpt = wechat_build($params, $wechat);
if (is_error($wOpt)) {
    message("{$wOpt['message']}，请及时联系站点管理员。",'','error');
}
$success_url = getPayBackUrl($log['order_type']);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1,maximum-scale=1, user-scalable=no">
        <title>微信安全支付</title>
        <link rel="stylesheet" type="text/css" href="/assets/common/css/pay.css"/>
        <link rel="stylesheet" type="text/css" href="http://at.alicdn.com/t/font_1463991939_0873933.css?1.68"/>
        <style type="text/css">
            .pay-icon{padding-top:30px;}
            .pay-icon p{display:block;text-align:center;font-size:18px;}
            .pay-icon p span{display:block;text-align:center;font-size:28px;}
            .pay-icon .iconfont{display:block;color:#efcf0c;text-align:center;font-size:10rem;}
            .pay-order{margin: 30px auto;width:100%;height:50px;background-color:#fff;}
            .pay-order span{font-family:"微软雅黑";line-height:50px;}
            .pay-order span:first-child{float:left;display:block;margin-left:10px;}
            .pay-order span:last-child{float:right;display:block;margin-right:10px;}
            button{display:block;margin:10px auto;width:80%;height:40px;border:1px solid #06be04;border-radius:5px;background-color:#06be04;color:#fff;font-size:16px;}
            a{margin-top:5px;color:gray;text-align:center;font-size:14px;display:block;}
        </style>
        <script type="text/javascript">
            var lock = false;
            function jsApiCall() {
                WeixinJSBridge.invoke(
                        'getBrandWCPayRequest',
                        {
                            'appId': '<?php echo $wOpt['appId']; ?>',
                            'timeStamp': '<?php echo $wOpt['timeStamp']; ?>',
                            'nonceStr': '<?php echo $wOpt['nonceStr']; ?>',
                            'package': '<?php echo $wOpt['package']; ?>',
                            'signType': '<?php echo $wOpt['signType']; ?>',
                            'paySign': '<?php echo $wOpt['paySign']; ?>'
                        },
                        function (res) {
                            WeixinJSBridge.log(res.err_msg);
                            if (res.err_msg == 'get_brand_wcpay_request:ok') {
                                window.location.href = "<?php echo $success_url;?>";
                            } else {
                                lock = false;
                            }
                        }
                );
            }

            function callpay() {
                if (lock) {
                    return;
                }
                lock = true;
                if (typeof WeixinJSBridge == "undefined") {
                    if (document.addEventListener) {
                        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                    } else if (document.attachEvent) {
                        document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                    }
                } else {
                    jsApiCall();
                }
            }
            window.onload = function () {
                callpay();
            }
        </script>
    </head>
    <body bgcolor="#f7f7f9">
        <div class="pay-icon">
            <i class="iconfont  icon-rechargefill"></i>
			<p>付款金额<span>￥<?php echo $log['pay_price'] ?></span></p>
        </div>
        <div class="pay-order">
            <span class="order-num">订单号</span>
            <span class="orders"><?php echo $log['out_trade_no'] ?></span>
        </div>
        <button onclick="callpay()">微信支付</button>
    </body>
</html>


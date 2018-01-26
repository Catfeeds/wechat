<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/4
 * Time: 11:21
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($do)?$do:'display';
load()->model('pay');
//检测是否登录
checkauth();
$setting = uni_setting($_W['uniacid'], array('payment'));
if($do == 'display'){
    $id = floor(trim($_GPC['id']));
    if(empty($id)){
        message('支付记录不存在','','error');
    }
    $pay_info = PayModel::getPayLogInfoById($id);
    if(empty($pay_info) || !is_array($pay_info)){
        message('支付信息不存在','','error');
    }
    //处理新晋传媒的订单
    if($pay_info['order_type'] == ORDER_TYPE_SJ_NEWS_AD || $pay_info['order_type'] == ORDER_TYPE_SJ_NEWS_RENEW){
        //加上支付日志里的公众号ID和会员ID
        PayModel::updatePayLogInfoById($id,array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'updatetime' => TIMESTAMP
        ));
        //加上支付日志里的公众号ID和会员ID
        $table = "sj_news_ad_order";
        if($pay_info['order_type'] == ORDER_TYPE_SJ_NEWS_RENEW){
            $table = "sj_news_ad_renew_order";
        }
        pdo_update($table,array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid']
        ),array(
            'id' => $pay_info['order_ids']
        ));
    }



    //处理新晋传媒的订单
    if($pay_info['status'] == PAY_YES){
        message('订单已经支付','','error');
    }
    $back_url = getPayBackUrl($pay_info);
    $order_count = count(explode('-',$pay_info['order_ids']));
}elseif($do == 'post'){
    if(!is_array($setting['payment'])) {
        message('没有设定支付参数','','error');
    }
    $id = floor(trim($_GPC['id']));
    if(empty($id)){
        message('支付记录不存在','','error');
    }
    $pay_info = PayModel::getPayLogInfoById($id);
    if(empty($pay_info) || !is_array($pay_info)){
        message('支付信息不存在','','error');
    }
    if($pay_info['status'] == PAY_YES){
        message('订单已经支付','','error');
    }
    $pay_method = floor(trim($_GPC['pay_method']));
    //富友也合并到微信
    PayModel::updatePayLogInfoById($id,array('pay_method'=>$pay_method));
    //支付传递加密参数
    $auth = payAuthEncode($pay_info['out_trade_no'],$_W['uniacid']);
    if($pay_method == PAY_METHOD_CREDIT){
        //余额支付
        if($setting['payment']['credit']['switch'] != OPEN_STATUS){
            message('余额支付未开启','','error');
        }
        func_pay_credit2_deal($_GPC['password'],$pay_info);
    }elseif($pay_method == PAY_METHOD_WECHAT){
        //微信支付
        if($setting['payment']['wechat']['switch'] != OPEN_STATUS){
            message('微信支付未开启','','error');
        }
        header("location:{$_W['siteroot']}payment/wechat/pay.php?i={$_W['uniacid']}&auth={$auth}");
    }elseif($pay_method == PAY_METHOD_FUIOU){
        //富友支付
        if($setting['payment']['fuiou']['switch'] != OPEN_STATUS){
            message('微信支付未开启','','error');
        }
        header("location:{$_W['siteroot']}payment/fuiou/pay.php?i={$_W['uniacid']}&auth={$auth}");
    }elseif($pay_method == PAY_METHOD_ALIPAY){
        //支付宝支付
        if($setting['payment']['alipay']['switch'] != OPEN_STATUS){
            message('支付宝支付未开启','','error');
        }
        header("location:{$_W['siteroot']}payment/alipay/pay.php?i={$_W['uniacid']}&auth={$auth}");
    }
}

/**
 * @param $password
 * @param $pay_info
 * 余额支付处理函数
 */
function func_pay_credit2_deal($password,$pay_info){
    global $_W;
    load()->model('user');
    $member_info = mc_fetch($_W['member']['uid']);
    if(empty($member_info) || !is_array($member_info)){
        message('会员信息不存在','','error');
    }
    if($member_info['pay_credit2_password'] != md5($password . $member_info['salt'] . $_W['config']['setting']['authkey'])){
        message('支付密码错误！请重新输入！','','error');
    }
    if($pay_info['pay_price'] > $member_info['credit2']){
        message('余额不足！','','error');
    }
    load()->model('mc');
    $status = mc_credit_update($_W['member']['uid'],'credit2',-$pay_info['pay_price'],array($_W['member']['uid'],"购物消费，余额支付{$pay_info['pay_price']}元，模块:{$pay_info['module']}"));
    if($status !== true){
        message('余额扣除失败','','error');
    }
    //处理交易成功后逻辑
    load()->classs('pay.notify');
    $notify = new PayNotify($pay_info);
    $status = $notify -> finish();
    if(!$status){
        message('交易失败','','error');
    }
    message("支付成功",getPayBackUrl($pay_info['order_type']),'success');
}
template('mc/pay');
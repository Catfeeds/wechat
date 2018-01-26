<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017-12-07
 * Time: 17:49
 */


/**
 * @param $arr
 * @return array
 * 数组转化，在其中加key
 */
function arrayTransByKey(&$arr){
    $arr1 = array();
    if(check_data($arr)){
        foreach ($arr as $k1 => &$v1){
            $v1['key'] = $k1;
            array_push($arr1,$v1);
        }
        $arr = $arr1;
    }
}


/**
 * @param $mobile
 * @return array|bool
 * 发送短信,type=0代表验证码
 */
function send($mobile,$params = array(),$type = 0){
    global $_W;
    //短信限制校验
    $config = iunserializer(pdo_fetchcolumn("SELECT setting FROM ".tablename('vapp_config')." WHERE uniacid='{$_W['uniacid']}'"));
    if(empty($config['sms']) || !is_array($config['sms'])){
        return error('-1','短信未配置，请联系客服');
    }
    $smsConfig = $config['sms'];
    if($smsConfig['status'] == 0){
        return error(-1,'短信功能暂未开启');
    }
    if(!check_mobile($mobile)){
        return error('-1','手机号格式错误');
    }
    $mobile_count = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('vapp_sms_log')." WHERE uniacid='{$_W['uniacid']}' AND type={$type} AND mobile='{$mobile}' AND createtime>=".strtotime(date('Y-m-d 00:00:00')));
    if(!empty($smsConfig['mobile_limit']) && $mobile_count >= $smsConfig['mobile_limit']){
        return error('-1','每天最多发送5条信息:-1');
    }
    $ip_count = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('vapp_sms_log')." WHERE uniacid='{$_W['uniacid']}' AND type={$type} AND ip='".CLIENT_IP."' AND createtime>=".strtotime(date('Y-m-d 00:00:00')));
    if(!empty($smsConfig['ip_limit']) && $ip_count >= $smsConfig['ip_limit']){
        return error('-1','每天最多发送5条信息:-2');
    }
    $last_time = pdo_fetchcolumn("SELECT createtime FROM ".tablename('vapp_sms_log')." WHERE uniacid='{$_W['uniacid']}' AND type={$type} AND mobile='{$mobile}' ORDER BY createtime DESC LIMIT 1");
    if(!empty($smsConfig['time_range']) && (TIMESTAMP - $last_time < $smsConfig['time_range'])){
        return error('-1',"操作频繁，请{$smsConfig['time_range']}秒后重试");
    }

    //短信参数
    $random = random(8,true);
    $appid = trim($smsConfig['tencent']['appid']);
    $appkey = trim($smsConfig['tencent']['appkey']);
    $apiUrl = trim($smsConfig['tencent']['apiurl'])."?sdkappid={$appid}&random={$random}";
    $params = array(
        'tel' => array(
            'nationcode' => '86',
            'mobile' => $mobile
        ),
        'sign' => $smsConfig['tencent']['sign'],
        "tpl_id" => $smsConfig['tencent']['tpl_id'],
        "params" => $params,
        'sig' => hash("sha256", http_build_query(array(
            'appkey'=>$appkey,
            'random'=> $random,
            'time'=>TIMESTAMP,
            'mobile'=>$mobile
        ))),
        'time' => TIMESTAMP,
        'extend' => '',
        'ext' => ''
    );
    $res = (array)json_decode(smsCurl($apiUrl,$params));
    if(!check_data($res)){
        return error(-1,'请求失败');
    }
    //发送成功
    if($res['result'] == 0){
        return true;
    }
    return error(-1,$res['errmsg']);
}


//发送验证码
function sendCode($mobile){
    global $_W;
    $type = 0;//代表发送短信
    $code = random(6,true);
    $res = send($mobile,array(
        $code,
        10
    ));
    if(!is_error($res)){
        //成功发送
        $status = pdo_insert('vapp_sms_log',array(
            'uniacid' => $_W['uniacid'],
            'mobile' => $mobile,
            'content' => $code,
            'code' => $code,
            'type' => $type,
            'ip' => CLIENT_IP,
            'createtime' => TIMESTAMP
        ));
        if(!$status){
            return error('-1','验证码记录失败，请联系管理员');
        }
    }
    return $res;
}

//验证手机验证码
function verifyCode($mobile,$code){
    global $_W;
    $config = iunserializer(pdo_fetchcolumn("SELECT setting FROM ".tablename('vapp_config')." WHERE uniacid='{$_W['uniacid']}'"));
    if(empty($config['sms']) || !is_array($config['sms'])){
        return error('-1','短信未配置，请联系客服');
    }
    $smsConfig = $config['sms'];
    if($smsConfig['status'] == 0){
        return error(-1,'短信功能暂未开启');
    }
    if(!check_mobile($mobile)){
        return error(-1,'手机号格式错误');
    }
    if(empty($code)) {
        return error(-2,'未输入验证码');
    }
    $sql_code = pdo_fetch("SELECT * FROM ".tablename('vapp_sms_log')." WHERE uniacid='{$_W['uniacid']}' AND mobile='{$mobile}' AND type=0 ORDER BY createtime DESC LIMIT 0,1");
    if(!check_data($sql_code)){
        return error(-3,'验证码信息不存在');
    }
    if(TIMESTAMP - $sql_code['createtime'] > $smsConfig['effective_time']){
        return error(-4,'验证码已失效，请重新获取');
    }
    if($code != $sql_code['code']){
        return error(-5,'验证码输入不正确');
    }
    return true;
}







/**
 * @param $url
 * @param array $params
 * @return mixed
 * 请求
 */
function smsCurl($url, $params = array()) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    $ret = curl_exec($curl);
    curl_close($curl);
    return $ret;
}


//检查登录信息是否合格,并返回登录信息
function auth_check($token){
    include_once IA_ROOT.'/framework/library/php-jwt-master/src/JWT.php';
    global $_W,$_GPC;
    $userInfo = JWT::decode($token, JWT_AUTH_KEY, array('HS256'));
    if(is_error($userInfo)){
        return null;
    }
    return (array)$userInfo;
}

//加密登录信息
function auth_login($data){
    include_once IA_ROOT.'/framework/library/php-jwt-master/src/JWT.php';
    return JWT::encode($data,JWT_AUTH_KEY);
}

/* md5加密密码 */
function md5_password($password,$salt){
    return md5(md5($password).md5($salt));
}

/**
 * @param $sql_password
 * @param $password
 * @param $salt
 * @return bool
 * 检查md5加密的密码是否正确
 */
function check_md5_password($sql_password,$password,$salt){
    return $sql_password == md5_password($password,$salt);
}

/**
 * @param array $data
 * @param string $message
 * @param int $code
 * json格式返回数据
 */
function to_json($code = 0,$message = '访问成功',$data = array()){
    exit(json_encode(array(
        'data' => $data,
        'message' => $message,
        'code' => $code
    )));
}


/**
 * @param int $length
 * @return string
 * 获取订单编号
 */
function get_order_no($length = ORDER_NO_LENGTH){
    $pay_log_count = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('pay_log')." WHERE createtime BETWEEN ".strtotime(date('Y-m-d').' 00:00:00')." AND ".strtotime(date('Y-m-d').' 23:59:59'));
    return date('Ymd').str_pad($pay_log_count+1,$length - 8,0,STR_PAD_LEFT);
}

/**
 * @param array $goods
 * 处理商品信息
 */
function get_parse_goods(&$goods = array()){

}

/**
 * @param $password
 * @param $pay_info
 * 余额支付处理函数
 */
function pay_credit2_deal($uid,$password,$pay_info){
    global $_W;
    $member = pdo_get('vapp_member',array(
        'uniacid' => $_W['uniacid'],
        'uid' => $uid
    ));
    if(!check_data($member)){
        to_json(1,'会员信息不存在');
    }
    if(empty($member['pay_credit2_password'])){
        to_json('-2','请先设置余额支付密码');
    }
    if($member['pay_credit2_password'] != md5_password($password,$member['salt'])){
        to_json(1,'支付密码错误！请重新输入！');
    }
    if($pay_info['pay_price'] > $member['credit2']){
       to_json(1,'余额不足！');
    }
    pdo_begin();
    $status = pdo_update('vapp_member',array(
        'credit2' => $member['credit2'] - $pay_info['pay_price'],
        'updatetime' => TIMESTAMP
    ),array(
        'uniacid' => $_W['uniacid'],
        'uid' => $uid
    ));
    if(!$status){
        pdo_rollback();
        to_json(1,'余额扣除失败');
    }
    //处理交易成功后逻辑
    load()->classs('pay.notify');
    $notify = new PayNotify($pay_info);
    $status = $notify -> finish();
    if(!$status){
        pdo_rollback();
        to_json(1,'交易失败');
    }
    pdo_commit();
    to_json(0,"支付成功");
}
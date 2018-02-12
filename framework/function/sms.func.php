<?php

/**
 * @param $mobile
 * @param string $content
 * @return array
 * 发送短信
 */
function sms_send($mobile, $content="",$nickname = ''){
    global $_W;
    load()->func('check');
    load()->func('communication');
    $sms = pdo_fetchcolumn("SELECT sms FROM ".tablename('uni_settings')." WHERE uniacid='{$_W['uniacid']}'");
    $sms = iunserializer($sms);
    if(empty($sms) || !is_array($sms)){
        return error('-1','短信未配置，请联系客服');
    }
    if(!check_mobile($mobile)){
        return error('-1','手机号格式错误');
    }
    if(empty($content)){
        return error('-1','发送内容不能为空');
    }
    $mobile_count = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('core_sendsms_log')." WHERE uniacid='{$_W['uniacid']}' AND type=0 AND mobile='{$mobile}' AND createtime>=".strtotime(date('Y-m-d 00:00:00')));
    if($mobile_count >= 5){
        return error('-1','每天最多发送5条信息:-1');
    }
    $ip_count = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('core_sendsms_log')." WHERE uniacid='{$_W['uniacid']}' AND type=0 AND ip='".CLIENT_IP."' AND createtime>=".strtotime(date('Y-m-d 00:00:00')));
    if($ip_count >= 5){
        return error('-1','每天最多发送5条信息:-2');
    }
    $last_time = pdo_fetchcolumn("SELECT createtime FROM ".tablename('core_sendsms_log')." WHERE uniacid='{$_W['uniacid']}' AND type=0 AND mobile='{$mobile}' ORDER BY createtime DESC LIMIT 1");
    if(TIMESTAMP - $last_time < 30){
        return error('-1','操作频繁，请30秒后重试');
    }
    $json = array();
    if($sms['type'] == 1){//聚合
        $result = ihttp_post('http://v.juhe.cn/sms/send',array(
            'mobile' => $mobile,
            'tpl_id' => $sms['juhe_code_tp_id'],
            'key' => $sms['juhe_appkey'],
            'tpl_value' => urlencode("#code#={$content}&#name#={$nickname}")
        ));
        if(!empty($result['content'])){
            $json = json_decode($result['content'],true);
        }
        if(empty($json) || !is_array($json)){
            return error(-1,'接口返回数据错误');
        }
        if($json['error_code'] != 0){
            return error(-1,'短信发送失败（错误码：'.$json['error_code'].'）');
        }
    }else{ //美圣
        $params = array(
            'username' => $sms['meisheng_username'],
            'password' => $sms['meisheng_password'],
            'veryCode' => $sms['meisheng_sms_password'],
            'mobile' => $mobile,
            'content' =>"@1@={$content}",
            'msgtype' => 2,
            'tempid' => $sms['meisheng_tempid']
        );
        $content = file_get_contents("http://{$sms['meisheng_serve_ip']}:{$sms['meisheng_port']}/service/httpService/httpInterface.do?method=sendUtf8Msg&".http_build_query($params));
        if(!is_string($content) || strlen($content) <=3){
            return error(-1,'短信发送失败（错误码：'.$content.'）');
        }
    }
    return true;
}

/* 发送验证码 */
function sms_code($mobile,$nickname = ''){
    global $_W;
    $code = random(4,true);
    $info = sms_send($mobile,$code,$nickname);
    if(is_error($info)){
        return error(-1,$info['message']);
    }
    $status = pdo_insert('core_sendsms_log',array(
        'uniacid' => $_W['uniacid'],
        'mobile' => $mobile,
        'content' => "您的验证码是：{$code}。请不要把短信验证码泄露给他人，如非本人操作可不用理会!",
        'code' => $code,
        'ip' => CLIENT_IP,
        'type' => 0,
        'createtime' => TIMESTAMP
    ));
    if(!$status){
        return error(-2,'验证码记录失败，请重试');
    }
    return true;
}
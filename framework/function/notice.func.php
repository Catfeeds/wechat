<?php
/**
 * @param int $uid
 * @param $text
 * @return bool
 * 向指定会员发送微信消息
 */
function notice_send_simple_text($uid = 0,$text){
    global $_W;
    $fansInfo = pdo_get('mc_mapping_fans',array(
        'uniacid' => $_W['uniacid'],
        'uid' => $uid
    ));
    if(!empty($fansInfo) && is_array($fansInfo)){
        if(!empty($fansInfo['acid'])) {
            $acc = WeAccount::create($fansInfo['acid']);
            $acc->sendCustomNotice(array(
                'touser' => $fansInfo['openid'],
                'msgtype' => "text",
                'text' => array(
                    'content' => urlencode($text)
                )
            ));
        }
    }
    return false;
}
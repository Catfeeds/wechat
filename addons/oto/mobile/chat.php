<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/6/7
 * Time: 12:13
 */
load()->func('check');
load()->func('notice');
$chat_uid = floor(trim($_GPC['chat_uid']));
$ac = trim($_GPC['ac']);
$member = pdo_get('mc_members',array(
    'uniacid' => $_W['uniacid'],
    'uid' =>$chat_uid
));
if(!check_data($member)){
    message('会员信息不存在','','error');
}
$memberAvatar = tomedia($member['avatar']);
//发送消息页面
if($_W['isajax']){
    //插入记录
    if($ac == 'log'){
        $data = array(
            'uniacid' => $_W['uniacid'],
            'send_uid' => $_W['member']['uid'],
            'receive_uid' => $chat_uid,
            'content' => trim($_GPC['content']),
            'createtime' => TIMESTAMP
        );
        if(empty($data['content'])){
            message('请输入内容','','error');
        }
        $status = pdo_insert('oto_chat',$data);
        if(!$status){
            message('记录失败','','error');
        }
        $data['time_desc'] = date('Y年m月d日 H:i:s',$data['createtime']);
        //建立别人与自己的聊天链接
        if(!isset($_SESSION)){
            session_start();
        }
        if(empty($_SESSION['chat_log_'.$chat_uid])){
            //每小时通知一次
            $_SESSION['chat_log_'.$chat_uid] = random(8);
            $chat_href = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&chat_uid={$_W['member']['uid']}&do=chat&m=oto";
            notice_send_simple_text($chat_uid,"会员ID：{$_W['member']['uid']},正在与您聊天\n详情点击：<a href='{$chat_href}'>立即打开对话</a>");
        }
        message($data,'','success');
    }elseif($ac == 'getall'){
        //获取记录，每次初始化100条，并且降序，prepend
        $where = "uniacid='{$_W['uniacid']}' AND ((receive_uid='{$_W['member']['uid']}' AND send_uid='{$chat_uid}') OR (send_uid='{$_W['member']['uid']}' AND receive_uid='{$chat_uid}'))";
        $list = pdo_fetchall("SELECT * FROM ".tablename('oto_chat')." WHERE {$where} ORDER BY id DESC LIMIT 0,100");
        if(!check_data($list)){
            message('没有更多聊天记录','','error');
        }
        foreach($list as $k => &$v){
            $v['time_desc'] = date('Y年m月d日 H:i:s',$v['createtime']);
        }
        message($list,'','success');
    }
    //获取记录
    $last_time = floor(trim($_GPC['last_time']));
    $where = "uniacid='{$_W['uniacid']}' AND receive_uid='{$_W['member']['uid']}' AND send_uid='{$chat_uid}'";
    if(!empty($last_time)){
        $where .= " AND createtime>{$last_time}";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('oto_chat')." WHERE {$where} ORDER BY id ASC");
    if(!check_data($list)){
        message('没有更多聊天记录','','error');
    }
    foreach($list as $k => &$v){
        $v['time_desc'] = date('Y年m月d日 H:i:s',$v['createtime']);
    }
    message($list,'','success');
}
$userAvatar = tomedia(pdo_fetchcolumn("SELECT avatar FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'"));
$page_title = "与会员{$chat_uid}聊天中";
if(!empty($member['nickname'])){
    $page_title = "与{$member['nickname']}聊天中";
}elseif(!empty($member['realname'])){
    $page_title = "与{$member['realname']}聊天中";
}
include $this->template('chat');
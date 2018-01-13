<?php
/* 详情显示 */
load()->model('mc');
load()->func('check');
load()->func('notice');
if($op == 'display'){
    $id = floor(trim($_GPC['id']));
    $where = "uniacid='{$_W['uniacid']}' AND id='{$id}'";
    $item = pdo_fetch("SELECT * FROM ".tablename('sj_news_list')." WHERE {$where}");
    $_share['title'] = $item['title'];
    if(!empty($item['detail'])){
        $item['detail'] = htmlspecialchars_decode($item['detail']);
    }
    if(empty($item)){
        message('此新闻已删除','','error');
    }
    if($item['uid'] != $_W['member']['uid'] && $item['is_display'] == 0 && $item['type'] !=0){
        message('尚未通过审核，不能查看','','error');
    }
    //评论新闻
    if($_W['isajax']){
        if(empty($_W['member']['uid'])){
            message('请先登录',url('auth/login', array('forward' => base64_encode($_SERVER['QUERY_STRING']))),'error');
        }
        $data = array(
            'uniacid' => $_W['uniacid'],
            'news_id' => $id,
            'uid' => $_W['member']['uid'],
            'author_uid' => $item['uid'],
            'content' => $_GPC['content'],
            'is_display' => 0,
            'createtime' => TIMESTAMP
        );
        if(empty($data['content'])){
            message('请输入评论内容','','error');
        }
        $status = pdo_insert('sj_news_talk',$data);
        if(!$status){
            message('评论失败','','error');
        }
        //增加新闻表的评论数目
        pdo_update('sj_news_list',array(
            'talk_num' => $item['talk_num']+1
        ),array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
        //评论赠积分
        $config = pdo_get('sj_news_credit_config',array(
            'uniacid' => $_W['uniacid']
        ));
        if(check_data($config)){
            if(!empty($config['setting'])){
                $setting = iunserializer($config['setting']);
                //获取积分奖励设置
                if(check_data($setting)){
                    //获取今日总奖励积分
                    $starttime = strtotime(date('Y-m-d').' 00:00:00');
                    $endtime = strtotime(date('Y-m-d').' 23:59:59');
                    //获取今日转发和评论的总积分
                    $total_credit = pdo_fetchcolumn("SELECT SUM(credit1) FROM ".tablename('sj_news_rebate')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND `type` IN (4,5) AND createtime BETWEEN {$starttime} AND {$endtime}");
                    //今日总奖励不超过设置的上限
                    if(($total_credit+$setting['talk'] <= $setting['share_limit'] && $setting['talk'] > 0) || empty($setting['share_limit'])){
                        //增加积分并记录
                        mc_credit_update($_W['member']['uid'],'credit1',$setting['talk'],array($_W['member']['uid'],"评论发表成功，赠送{$setting['talk']}积分。"));
                        pdo_insert('sj_news_rebate',array(
                            'uniacid' => $_W['uniacid'],
                            'uid' => $_W['member']['uid'],
                            'credit1' => $setting['talk'],
                            'type' => 4,
                            'createtime' => TIMESTAMP
                        ));
                        notice_send_simple_text($_W['member']['uid'],"文章评论发表成功，恭喜您获得{$setting['talk']}积分奖励。【新晋传媒】");
                    }
                }
            }
        }

        message('感谢您的评论，祝您生活愉快！',referer(),'success');
    }

    //获取评论列表
    $talks = pdo_fetchall("SELECT a.*,b.nickname,b.avatar FROM ".tablename('sj_news_talk')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE a.uniacid='{$_W['uniacid']}' AND a.news_id='{$id}' AND a.is_display=1 ORDER BY a.id DESC LIMIT 0,100");
    if(!empty($talks) && is_array($talks)){
        foreach($talks as $k1 => &$v1){
            $v1['createtime'] = date('Y年m月d日 H:i',$v1['createtime']);
            $v1['avatar'] = tomedia($v1['avatar']);
        }
    }
    if(!empty($item['thumbs'])){
        $item['thumbs'] = iunserializer($item['thumbs']);
        if(is_array($item['thumbs'])){
            foreach($item['thumbs'] as $k => &$v3){
                $v3 = tomedia($v3);
            }
        }
    }
    if(!empty($item['audio_src'])){
        $item['audio_src'] = tomedia($item['audio_src']);
    }
    if(!empty($item['video_src'])){
        $item['video_src'] = tomedia($item['video_src']);
    }
    $pre = array();
    $next = array();
    if(!empty($item)){
        pdo_update('sj_news_list',array('look_num'=>$item['look_num']+1),array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
        $pre = pdo_fetch("SELECT id,title FROM ".tablename('sj_news_list')." WHERE uniacid='{$_W['uniacid']}' AND (is_display='1' OR type=0) AND id<{$item['id']} ORDER BY id DESC LIMIT 0,1");
        if(!empty($pre)){
            $pre['href'] = $this->createMobileUrl('detail',array('id'=>$pre['id']));
        }
        $next = pdo_fetch("SELECT id,title FROM ".tablename('sj_news_list')." WHERE uniacid='{$_W['uniacid']}' AND (is_display='1' OR type=0) AND id>{$item['id']} ORDER BY id ASC LIMIT 0,1");
        if(!empty($next)){
            $next['href'] = $this->createMobileUrl('detail',array('id'=>$next['id']));
        }
    }

    //获取一则广告，根据浏览量最小排序
    if($item['type'] == 0){
        //如果是官方专题
        $ad = pdo_fetch("SELECT * FROM ".tablename('sj_news_ad')." WHERE uniacid='{$_W['uniacid']}' AND package_id IN (7,8) AND is_display='1' AND last_time>".TIMESTAMP." ORDER BY look_num ASC LIMIT 0,1");
    }else{
        $ad = pdo_fetch("SELECT * FROM ".tablename('sj_news_ad')." WHERE uniacid='{$_W['uniacid']}' AND package_id IN (5,6) AND is_display='1' AND last_time>".TIMESTAMP." ORDER BY look_num ASC LIMIT 0,1");
    }
    if(!empty($ad) && is_array($ad)){
        pdo_update('sj_news_ad',array(
            'look_num' => $ad['look_num']+1,
            'updatetime' => TIMESTAMP
        ),array(
            'uniacid' => $_W['uniacid'],
            'id' => $ad['id']
        ));
    }

    //获取会员列表
    $members = pdo_fetchall("SELECT * FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' ORDER BY uid DESC LIMIT 0,14");
    $members_total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}'");
}


/* 交互信息 */
if($op == 'interaction'){
    $id = floor(trim($_GPC['id']));
    $item = pdo_get('sj_news_list',array(
        'uniacid' => $_W['uniacid'],
        'id' =>$id
    ));
    if(!empty($item['detail'])){
        $item['detail'] = htmlspecialchars_decode($item['detail']);
    }
    if(empty($item)){
        message('内容不存在或者已删除','','error');
    }
    $type = floor(trim($_GPC['type']));
    $type = in_array($type,array(1,2,3))?$type:1;
    $endtime = strtotime(date('Y-m-d 23:59:59'));
    $starttime = $endtime - 24*3600;

    $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('sj_news_interaction')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND content_id='{$id}' AND type='{$type}' AND createtime BETWEEN {$starttime} AND {$endtime}");
    if($total >= 5){
        message('今日次数达到5次','','error');
    }
    $field = "look_num";
    switch($type){
        case 1:$field = "zan_num";break;
        case 2:$field = "hate_num";break;
        case 3:$field = "share_num";break;
    }
    $status = pdo_query("UPDATE ".tablename('sj_news_list')." SET {$field}={$field}+1 WHERE uniacid='{$_W['uniacid']}' AND id='{$id}'");
    if(!$status){
        message('发表失败','','error');
    }
    pdo_insert('sj_news_interaction',array(
        'uniacid' => $_W['uniacid'],
        'uid' => $_W['member']['uid'],
        'content_id' => $id,
        'type' => $type,
        'ip'=>CLIENT_IP,
        'createtime' => TIMESTAMP
    ));
    message('发表成功','','success');
}


include $this->template('detail');
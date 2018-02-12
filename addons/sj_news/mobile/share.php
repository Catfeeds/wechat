<?php
/* 详情显示 */
load()->model('mc');
load()->func('check');
load()->func('notice');
if($op == 'display'){
    if($_W['isajax']){
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
                    if(($total_credit+$setting['share'] <= $setting['share_limit'] && $setting['share'] > 0) || empty($setting['share_limit'])){
                        //增加积分并记录
                        mc_credit_update($_W['member']['uid'],'credit1',$setting['share'],array($_W['member']['uid'],"链接转发成功，赠送{$setting['share']}积分。"));
                        pdo_insert('sj_news_rebate',array(
                            'uniacid' => $_W['uniacid'],
                            'uid' => $_W['member']['uid'],
                            'credit1' => $setting['share'],
                            'type' => 5,
                            'createtime' => TIMESTAMP
                        ));
                        notice_send_simple_text($_W['member']['uid'],"平台链接转发成功，恭喜您获得{$setting['share']}积分奖励。【新晋传媒】");
                    }
                }
            }
        }
        exit();
    }
}
message('访问错误','','error');
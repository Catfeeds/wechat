<?php
load()->func('check');
load()->func('notice');
if(empty($do)){$do = 'display';}
if($do == 'display'){
    if($_W['isajax']){
        $config = pdo_get('distribution_config',array(
            'uniacid' => $_W['uniacid']
        ));
        if(!check_data($config)){
            message('平台未设置兑换参数','','error');
        }
        $setting = iunserializer($config['setting']);
        if(!check_data($setting)){
            message('平台未设置兑换参数','','error');
        }
        $rebate_credit1 = empty($setting['fangyuanbao']['rebate_credit1'])?0:floatval($setting['fangyuanbao']['rebate_credit1']);
        $uid = floor(trim($_GPC['uid']));
        $send_count = floor(trim($_GPC['count']));
        $member = pdo_get('mc_members',array(
            'uniacid'=>$_W['uniacid'],
            'uid'=>$uid
        ));
        if(!check_data($member)){
            message('会员信息不存在','','error');
        }
        if($send_count<=0){
            message('请输入发放个数','','error');
        }
        $count = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$uid}' AND status=".NO_STATUS);
        if($send_count > $count){
            message('发放个数不能超过未兑换数量','','error');
        }
        $list = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$uid}' AND status=".NO_STATUS." ORDER BY createtime ASC");
        if(!check_data($list)){
            message('方圆宝队列不存在','','error');
        }
        pdo_begin();
        load()->model('mc');
        foreach($list as $k => $fyb){
            if(($k+1) > $send_count)break;
            $send_status = pdo_update('fangyuanbao_queue',array(
                'status'=>IS_STATUS,
                'updatetime'=>TIMESTAMP,
                'rebatetime' => TIMESTAMP
            ),array(
                'uniacid'=>$_W['uniacid'],
                'uid'=>$uid,
                'status'=>NO_STATUS,
                'id'=>$fyb['id']
            ));
            if(!$send_status){
                pdo_rollback();
                message('兑换失败:-1','','error');
            }
            if($rebate_credit1 > 0){
                $result = mc_credit_update($uid,'credit1',$rebate_credit1,array($uid,"方圆宝兑换，获得{$rebate_credit1}积分"));
                if(is_error($result)){
                    pdo_rollback();
                    message('兑换失败:-3','','error');
                }
            }
            $log = array(
                'uniacid' => $_W['uniacid'],
                'uid' => $uid,
                'queue_id' => $fyb['id'],
                'credit1'=>$rebate_credit1,
                'birthtime' => strtotime(trim($_GPC['birthtime'])),
                'createtime' => TIMESTAMP
            );
            $insert = pdo_insert('fangyuanbao_rebate',$log);
            if(!$insert){
                pdo_rollback();
                message('兑换失败','','error');
            }
        }
        pdo_commit();
        notice_send_simple_text($uid,"您的积分已兑换，请注意查收");
        message('兑换成功','','success');
    }
    $page = getApartPageNo('page');
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $province = trim($_GPC['area']['province']);
    $city = trim($_GPC['area']['city']);
    $district = trim($_GPC['area']['district']);
    $where = "a.uniacid='{$_W['uniacid']}'";
    if(!empty($province)){
        $where .= " AND a.province='{$province}'";
    }
    if(!empty($city)){
        $where .= " AND a.city='{$city}'";
    }
    if(!empty($district)){
        $where .= " AND a.district='{$district}'";
    }
    $keyword = trim($_GPC['keyword']);
    if(!empty($keyword)){
        $where .= " AND (a.nickname LIKE '%{$keyword}%' OR a.realname LIKE '%{$keyword}%')";
    }
    $uid = floor(trim($_GPC['uid']));
    if(!empty($uid)){
        $where .= " AND a.uid='{$uid}'";
    }
    //根据支付日志表计算消费价格和消费次数
    $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('mc_members')." a LEFT JOIN (SELECT uid,SUM(pay_price+use_credit1) AS total_money,COUNT(id) AS total_count FROM ".tablename('pay_log')." WHERE uniacid='{$_W['uniacid']}' AND pay_status=1 AND order_type!=".ORDER_TYPE_OLD_FEE." AND createtime BETWEEN {$starttime} AND {$endtime} GROUP BY uid) b ON a.uid=b.uid WHERE {$where} AND total_money!=''");
    $total_price = pdo_fetchcolumn("SELECT SUM(b.total_money) FROM ".tablename('mc_members')." a LEFT JOIN (SELECT uid,SUM(pay_price+use_credit1) AS total_money,COUNT(id) AS total_count FROM ".tablename('pay_log')." WHERE uniacid='{$_W['uniacid']}' AND pay_status=1 AND order_type!=".ORDER_TYPE_OLD_FEE." AND createtime BETWEEN {$starttime} AND {$endtime} GROUP BY uid) b ON a.uid=b.uid WHERE {$where} AND total_money!=''");
    $total_credit1 = pdo_fetchcolumn("SELECT SUM(b.total_money) FROM ".tablename('mc_members')." a LEFT JOIN (SELECT uid,SUM(use_credit1) AS total_money,COUNT(id) AS total_count FROM ".tablename('pay_log')." WHERE uniacid='{$_W['uniacid']}' AND pay_status=1 AND order_type!=".ORDER_TYPE_OLD_FEE." AND createtime BETWEEN {$starttime} AND {$endtime} GROUP BY uid) b ON a.uid=b.uid WHERE {$where} AND total_money!=''");
    $pager = pagination($total,$page,$psize);
    $list = pdo_fetchall("SELECT a.*,b.total_money,b.total_count FROM ".tablename('mc_members')." a LEFT JOIN (SELECT uid,SUM(pay_price+use_credit1) AS total_money,COUNT(id) AS total_count FROM ".tablename('pay_log')." WHERE uniacid='{$_W['uniacid']}' AND pay_status=1 AND order_type!=".ORDER_TYPE_OLD_FEE."  AND createtime BETWEEN {$starttime} AND {$endtime} GROUP BY uid) b ON a.uid=b.uid WHERE {$where} ORDER BY total_money DESC,total_count DESC LIMIT {$pindex},{$psize}");
}elseif($do == 'get'){ //获取方圆宝信息
    $uid = floor(trim($_GPC['uid']));
    $member = pdo_get('mc_members',array(
        'uniacid'=>$_W['uniacid'],
        'uid'=>$uid
    ),array('uid','nickname','realname'));
    if(!check_data($member)){
        message('会员信息不存在','','error');
    }
    $total_fyb_count = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$uid}'");
    $total_fyb_exchange_count = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$uid}' AND status=".IS_STATUS);
    $total_fyb_not_exchange_count = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$uid}' AND status=".NO_STATUS);
    $member['total_fyb_count'] = empty($total_fyb_count)?0:$total_fyb_count;
    $member['total_fyb_exchange_count'] = empty($total_fyb_exchange_count)?0:$total_fyb_exchange_count;
    $member['total_fyb_not_exchange_count'] = empty($total_fyb_not_exchange_count)?0:$total_fyb_not_exchange_count;
    message($member,'','success');
}elseif($do == 'old'){
    if($_W['isajax']){
        $log = array(
            'uniacid' => $_W['uniacid'],
            'uid' => floor(trim($_GPC['uid'])),
            'queue_id' => 0,
            'credit1'=>60,
            'type' => 1,
            'old_nickname' => trim($_GPC['nickname']),
            'old_province' => $_GPC['area']['province'],
            'old_city' => $_GPC['area']['city'],
            'old_district' => $_GPC['area']['district'],
            'birthtime' => strtotime(trim($_GPC['birthtime'])),
            'createtime' => TIMESTAMP
        );
        $send_count = floor(trim($_GPC['count']));
        pdo_begin();
        for($i = 0;$i<$send_count;$i++){
            $insert = pdo_insert('fangyuanbao_rebate',$log);
            if(!$insert){
                pdo_rollback();
                message('兑换失败','','error');
            }
        }
        pdo_commit();
        message('兑换成功','','success');
    }
}elseif($do == 'search'){
    if($_W['isajax']){
        $info = pdo_get('a_mc_members',array(
            'uniacid' => 306,
            'uid' => floor(trim($_GPC['uid']))
        ));
        if(!check_data($info)){
            message('会员信息不存在','','error');
        }
        message($info,'','success');
    }

}
template('fyb/send');
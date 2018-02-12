<?php
load()->func('check');
load()->func('notice');
if(empty($do)){$do = 'display';}
if($do == 'display'){//商家结算
    if($_W['isajax']){
        $id = floor(trim($_GPC['id']));
        $info = pdo_get('store_balance_apply',array(
            'uniacid' => $_W['uniacid'],
            'id'=>$id
        ));
        if(!check_data($info)){
            message('结算信息不存在','','error');
        }
        if($info['is_check'] != CHECK_PASS){
            message('代理未审核，不能结算','','error');
        }
        $status = pdo_update('store_balance_apply',array(
            'status' => IS_STATUS,
            'thumb' => trim($_GPC['thumb']),
            'updatetime'=>TIMESTAMP
        ),array(
            'uniacid'=>$_W['uniacid'],
            'id'=>$id
        ));
        if(!$status){
            message('结算失败','','error');
        }
        //发送通知
        $saler_uid = pdo_fetchcolumn("SELECT saler_uid FROM ".tablename('store_list')." WHERE uniacid='{$_W['uniacid']}' AND id='{$info['store_id']}'");
        if(!empty($saler_uid)){
            $message = "您的结算申请已通过审核，到帐金额：￥{$info['money']},请查收";
            notice_send_simple_text($saler_uid,$message);
        }
        message('结算成功',referer(),'success');
    }
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page - 1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $is_check = $_GPC['is_check'];
    $status = $_GPC['status'];
    $where = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    if(!empty($keyword)){
        $where .= " AND b.title LIKE '%{$keyword}%'";
    }
    if(!empty($province)){
        $where .= " AND b.province='{$province}'";
    }
    if(!empty($city)){
        $where .= " AND b.city='{$city}'";
    }
    if(!empty($district)){
        $where .= " AND b.district='{$district}'";
    }
    if(!empty($is_check)){
        $where .= " AND a.is_check IN (".implode(',',$is_check).")";
    }
    if(!empty($status)){
        $where .= " AND a.status IN (".implode(',',$status).")";
    }
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('store_balance_apply')." a LEFT JOIN ".tablename('store_list')." b ON a.store_id=b.id WHERE {$where}");
    $total_balance_money = pdo_fetchcolumn("SELECT SUM(a.money) FROM ".tablename('store_balance_apply')." a LEFT JOIN ".tablename('store_list')." b ON a.store_id=b.id WHERE {$where}");
    $where .= " ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.title,c.withdraw_account FROM ".tablename('store_balance_apply')." a LEFT JOIN ".tablename('store_list')." b ON a.store_id=b.id LEFT JOIN ".tablename('mc_members')." c ON b.saler_uid=c.uid WHERE {$where}");
    if(check_data($list)){
        foreach($list as $k => &$v){
            if(!empty($v['withdraw_account'])){
                $v['withdraw_account'] = iunserializer($v['withdraw_account']);
            }
        }
    }
    $pager = pagination($total,$page,$psize);
}elseif($do == 'mc_withdraw'){//会员结算
    if($_W['isajax']){
        $id = floor(trim($_GPC['id']));
        $info = pdo_get('mc_withdraw_apply',array(
            'uniacid'=>$_W['uniacid'],
            'id'=>$id
        ));
        if(!check_data($info)){
            message('结算信息不存在','','error');
        }
        $status = pdo_update('mc_withdraw_apply',array(
            'is_check'=>CHECK_PASS,
            'status'=>IS_STATUS,
            'thumb' => trim($_GPC['thumb']),
            'updatetime'=>TIMESTAMP
        ),array(
            'uniacid'=>$_W['uniacid'],
            'id'=>$id
        ));
        if(!$status){
            message('结算失败','','error');
        }
        notice_send_simple_text($info['uid'],"您申请的提现已通过审核，到帐金额：￥{$info['money']}元，请查收");
        message('结算成功',referer(),'success');
    }
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page - 1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $is_check = $_GPC['is_check'];
    $status = $_GPC['status'];
    $where = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    if(!empty($keyword)){
        $where .= " AND (a.uid='{$keyword}' OR b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
    }
    if(!empty($province)){
        $where .= " AND b.province='{$province}'";
    }
    if(!empty($city)){
        $where .= " AND b.city='{$city}'";
    }
    if(!empty($district)){
        $where .= " AND b.district='{$district}'";
    }
    if(!empty($is_check)){
        $where .= " AND a.is_check IN (".implode(',',$is_check).")";
    }
    if(!empty($status)){
        $where .= " AND a.status IN (".implode(',',$status).")";
    }
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('mc_withdraw_apply')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $total_withdraw_money = pdo_fetchcolumn("SELECT SUM(a.money) FROM ".tablename('mc_withdraw_apply')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $where .= " ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.realname,b.withdraw_account FROM ".tablename('mc_withdraw_apply')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    if(check_data($list)){
        foreach($list as $k => &$v){
            if(!empty($v['withdraw_account'])){
                $v['withdraw_account'] = iunserializer($v['withdraw_account']);
            }
        }
    }
    $pager = pagination($total,$page,$psize);
}elseif($do == 'person'){//个人收款二维码
    if($_W['isajax']){
        $id = floor(trim($_GPC['id']));
        $info = pdo_get('person_balance',array(
            'uniacid'=>$_W['uniacid'],
            'id'=>$id
        ));
        if(!check_data($info)){
            message('结算信息不存在','','error');
        }
        $status = pdo_update('person_balance',array(
            'is_check'=>CHECK_PASS,
            'status'=>IS_STATUS,
            'thumb' => trim($_GPC['thumb']),
            'updatetime'=>TIMESTAMP
        ),array(
            'uniacid'=>$_W['uniacid'],
            'id'=>$id
        ));
        if(!$status){
            message('结算失败','','error');
        }
        notice_send_simple_text($info['uid'],"您申请的提现已通过审核，到帐金额：￥{$info['money']}元，请查收");
        message('结算成功',referer(),'success');
    }
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page - 1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $is_check = $_GPC['is_check'];
    $status = $_GPC['status'];
    $where = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
    if(!empty($keyword)){
        $where .= " AND (a.uid='{$keyword}' OR b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
    }
    if(!empty($province)){
        $where .= " AND b.province='{$province}'";
    }
    if(!empty($city)){
        $where .= " AND b.city='{$city}'";
    }
    if(!empty($district)){
        $where .= " AND b.district='{$district}'";
    }
    if(!empty($is_check)){
        $where .= " AND a.is_check IN (".implode(',',$is_check).")";
    }
    if(!empty($status)){
        $where .= " AND a.status IN (".implode(',',$status).")";
    }
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('person_balance')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $total_withdraw_money = pdo_fetchcolumn("SELECT SUM(a.money) FROM ".tablename('person_balance')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $where .= " ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.realname,b.withdraw_account FROM ".tablename('person_balance')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    if(check_data($list)){
        foreach($list as $k => &$v){
            if(!empty($v['withdraw_account'])){
                $v['withdraw_account'] = iunserializer($v['withdraw_account']);
            }
        }
    }
    $pager = pagination($total,$page,$psize);
}
template('withdraw/display');
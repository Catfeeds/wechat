<?php
load()->func('check');
if(empty($do)){$do = 'display';}
if($do == 'display'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $uid = floor(trim($_GPC['uid']));
    $keyword = trim($_GPC['keyword']);
    $province = trim($_GPC['area']['province']);
    $city = trim($_GPC['area']['city']);
    $district = trim($_GPC['area']['district']);
    $where1 = "uniacid='{$_W['uniacid']}'";
    if(!empty($uid)){
        $where1 .= " AND uid='{$uid}'";
    }
    if(!empty($province)){
        $where1 .= " AND province LIKE '%{$keyword}%'";
    }
    if(!empty($city)){
        $where1 .= " AND city LIKE '%{$keyword}%'";
    }
    if(!empty($district)){
        $where1 .= " AND district LIKE '%{$keyword}%'";
    }
    $where = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime} AND a.uid IN (SELECT uid FROM ".tablename('agent_user')." WHERE {$where1})";
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.realname FROM ".tablename('staff_income')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('staff_income')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}elseif($do == 'send'){
    if($_W['isajax']){
        $uid = floor(trim($_GPC['uid']));
        if(empty($uid)){
            message('请输入会员编号','','error');
        }
        $money = floatval(trim($_GPC['money']));
        if($money <= 0){
            message('输入金额有误','','error');
        }
        $member = pdo_get('mc_members',array(
            'uniacid'=>$_W['uniacid'],
            'uid'=>$uid
        ));
        if(!check_data($member)){
            message('会员信息不存在','','error');
        }
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $uid,
            'money' => $money,
            'createtime' => TIMESTAMP
        );
        load()->model('mc');
        pdo_begin();
        $result = mc_credit_update($uid,'credit4',$money,array($uid,"发放代理佣金，金额：{$money}元"));
        if(is_error($result)){
            pdo_rollback();
            message('发放失败','','error');
        }
        $status = pdo_insert('staff_income',$data);
        if(!$status){
            pdo_rollback();
            message('发放失败','','error');
        }
        pdo_commit();
        message('发放成功',referer(),'success');
    }
}
template('agent/display');
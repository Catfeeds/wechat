<?php
load()->func('check');
load()->func('notice');
if(empty($do)){$do = 'display';}
if($do == 'display'){
    $province = $_W['province'];
    $city = $_W['city'];
    $district = $_GPC['area']['district'];
    if($_W['is_big_city']){
        $district = $_W['district'];
    }
    if($_W['isajax']){
        $id = floor(trim($_GPC['id']));
        $info = pdo_get('store_balance_apply',array(
            'uniacid'=>$_W['uniacid'],
            'id'=>$id
        ));
        if(!check_data($info)){
            message('申请信息不存在','','error');
        }
        if($info['is_check'] == CHECK_PASS){
            message('已通过审核，请勿重新操作','','error');
        }
        $store = pdo_get('store_list',array(
            'uniacid'=>$_W['uniacid'],
            'id'=>$info['store_id']
        ));
        if(!check_data($store)){
            message('店铺信息不存在','','error');
        }
        if($store['province'] != $_W['province'] || $store['city'] != $_W['city'] || ($_W['is_big_city'] && $store['district'] != $_W['district'])){
            message('申请信息不属于您的管辖区','','error');
        }
        $status = pdo_update('store_balance_apply',array(
            'is_check'=>CHECK_PASS,
            'updatetime'=>TIMESTAMP
        ),array(
            'uniacid'=>$_W['uniacid'],
            'id'=>$id
        ));
        if(!$status){
            message('审核失败','','error');
        }
        message('审核成功',referer(),'success');
    }
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $keyword = trim($_GPC['keyword']);
    $is_check = $_GPC['is_check'];
    $where = "uniacid='{$_W['uniacid']}'";
    if(!empty($province)){
        $where .= " AND province='{$province}'";
    }
    if(!empty($city)){
        $where .= " AND city='{$city}'";
    }
    if(!empty($district)){
        $where .= " AND district='{$district}'";
    }
    if(!empty($keyword)){
        $where .= " AND title LIKE '%{$keyword}%'";
    }

    $where2 = "a.uniacid='{$_W['uniacid']}' AND a.store_id IN (SELECT id FROM ".tablename('store_list')." WHERE {$where})";
    if(check_data($is_check)){
        $where2 .= " AND a.is_check IN (".implode(',',$is_check).")";
    }
    if(!empty($starttime)){
        $where2 .=" AND a.createtime>='{$starttime}'";
    }
    if(!empty($endtime)){
        $where2 .= " AND a.createtime<='{$endtime}'";
    }
    $total_balance_money = pdo_fetchcolumn("SELECT SUM(a.money) FROM ".tablename('store_balance_apply')." a WHERE {$where2}");
    $list = AgentModel::getStoreBalanceApplyList($keyword,$is_check,$province,$city,$district,$starttime,$endtime,$pindex,$psize);
    $total = AgentModel::getStoreBalanceApplyCount($keyword,$is_check,$province,$city,$district,$starttime,$endtime);
    $pager = pagination($total,$page,$psize);
}elseif($do == 'old'){
    $province = $_W['province'];
    $city = $_W['city'];
    $district = $_GPC['area']['district'];
    if($_W['is_big_city']){
        $district = $_W['district'];
    }
    if($_W['isajax']){
        $id = floor(trim($_GPC['id']));
        $info = pdo_get('person_balance',array(
            'uniacid'=>$_W['uniacid'],
            'id'=>$id
        ));
        if(!check_data($info)){
            message('申请信息不存在','','error');
        }
        if($info['is_check'] == CHECK_PASS){
            message('已通过审核，请勿重新操作','','error');
        }
        $member = pdo_get('mc_members',array(
            'uniacid'=>$_W['uniacid'],
            'uid'=>$info['uid']
        ));
        if(!check_data($member)){
            message('会员信息不存在','','error');
        }
        if($member['province'] != $_W['province'] || $member['city'] != $_W['city'] || ($_W['is_big_city'] && $member['district'] != $_W['district'])){
            message('申请信息不属于您的管辖区','','error');
        }
        $status = pdo_update('person_balance',array(
            'is_check'=>CHECK_PASS,
            'updatetime'=>TIMESTAMP
        ),array(
            'uniacid'=>$_W['uniacid'],
            'id'=>$id
        ));
        if(!$status){
            message('审核失败','','error');
        }
        message('审核成功',referer(),'success');
    }
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $uid = trim($_GPC['uid']);
    $keyword = trim($_GPC['keyword']);
    $is_check = $_GPC['is_check'];
    $where = "uniacid='{$_W['uniacid']}'";
    if(!empty($province)){
        $where .= " AND province='{$province}'";
    }
    if(!empty($city)){
        $where .= " AND city='{$city}'";
    }
    if(!empty($district)){
        $where .= " AND district='{$district}'";
    }
    if(!empty($keyword)){
        $where .= " AND (nickname LIKE '%{$keyword}%' OR realname LIKE '%{$keyword}%')";
    }

    $where2 = "a.uniacid='{$_W['uniacid']}' AND a.uid IN (SELECT uid FROM ".tablename('mc_members')." WHERE {$where})";
    if(check_data($is_check)){
        $where2 .= " AND a.is_check IN (".implode(',',$is_check).")";
    }
    if(!empty($starttime)){
        $where2 .=" AND a.createtime>='{$starttime}'";
    }
    if(!empty($endtime)){
        $where2 .= " AND a.createtime<='{$endtime}'";
    }
    $total_balance_money = pdo_fetchcolumn("SELECT SUM(a.money) FROM ".tablename('person_balance')." a WHERE {$where2}");
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.realname FROM ".tablename('person_balance')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where2}");
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('person_balance')." a WHERE {$where2}");
    $pager = pagination($total,$page,$psize);
}
template('account/display');
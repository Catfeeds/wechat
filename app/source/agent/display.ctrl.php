<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/6/1
 * Time: 18:34
 */
defined('IN_IA') or exit('Access Denied');
if($do == 'display'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $is_check = floor(trim($_GPC['is_check'])) == CHECK_PASS?CHECK_PASS:CHECK_NOT_PASS;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $list = AgentModel::getMobileStoreBalanceApplyList($keyword,$is_check,$starttime,$endtime,$pindex,$psize);
    if($_W['isajax']){
        if(trim($_GPC['ac']) == 'balance'){
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
            message('审核成功','','success');
        }
        if(!check_data($list)){
            message('没有更多结算信息','','error');
        }
        message($list,'','success');
    }
}elseif($do == 'person'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $is_check = floor(trim($_GPC['is_check'])) == CHECK_PASS?CHECK_PASS:CHECK_NOT_PASS;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $list = AgentModel::getMobilePersonBalanceApplyList($keyword,$is_check,$starttime,$endtime,$pindex,$psize);
    if($_W['isajax']){
        if(trim($_GPC['ac']) == 'balance'){
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
            $store = pdo_get('mc_members',array(
                'uniacid'=>$_W['uniacid'],
                'uid'=>$info['uid']
            ));
            if(!check_data($store)){
                message('会员信息不存在','','error');
            }
            if($store['province'] != $_W['province'] || $store['city'] != $_W['city'] || ($_W['is_big_city'] && $store['district'] != $_W['district'])){
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
            message('审核成功','','success');
        }
        if(!check_data($list)){
            message('没有更多结算信息','','error');
        }
        message($list,'','success');
    }
}elseif($do == 'withdraw'){
    $user = pdo_get('mc_members',array(
        'uniacid' => $_W['uniacid'],
        'uid'=>$_W['member']['uid']
    ));
    if(!check_data($user)){
        message('会员信息不存在','','error');
    }
    if($_W['isajax']){
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'tel' => trim($_GPC['tel']),
            'username' => trim($_GPC['username']),
            'money' => floatval(trim($_GPC['money'])),
            'withdraw_method' => PAY_METHOD_WECHAT,
            'createtime' => TIMESTAMP
        );
        if($data['money'] <= 0){
            message('提现金额有误，请重新输入','','error');
        }
        if($data['money'] < 1){
            message('提现金额不能小于1元','','error');
        }
        if($data['money'] >= $user['credit4']){
            message('提现金额不能超过实际金额','','error');
        }
        pdo_begin();
        load()->model('mc');
        $result = mc_credit_update($data['uid'],'credit4',-$data['money'],array($data['uid'],"提现申请，扣除{$data['money']}佣金"));
        if(is_error($result)){
            pdo_rollback();
            message('申请失败','','error');
        }
        $status = pdo_insert('agent_balance',$data);
        if(!$status){
            pdo_rollback();
            message("申请失败！", "", "error");
        }
        pdo_commit();
        message('申请成功，请耐心等待平台审核^-^',referer(),'success');
    }
}elseif($do == 'withdraw_log'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $list = pdo_fetchall("SELECT * FROM ".tablename('agent_balance')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    if(check_data($list)){
        foreach($list  as $k => &$v){
            $v['createtime'] = date('Y-m-d H:i',$v['createtime']);
        }
    }
    if($_W['isajax']){
        if(check_data($list)){
            message($list,'','success');
        }
        message('没有更多记录','','error');
    }
}
template('agent/display');
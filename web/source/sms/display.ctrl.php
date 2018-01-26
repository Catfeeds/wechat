<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/5/20
 * Time: 10:31
 */
load()->func('check');
$do = !empty($_GPC['do'])?$_GPC['do']:'display';
if($do == 'display'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $where = "uniacid='{$_W['uniacid']}' AND createtime BETWEEN {$starttime} AND {$endtime}";
    $mobile = trim($_GPC['mobile']);
    if(!empty($mobile)){
        $where .= " AND mobile LIKE '%{$mobile}%'";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('core_sendsms_log')." WHERE {$where} ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('core_sendsms_log')." WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}elseif($do == 'config'){
    $uni_setting = pdo_fetch("SELECT * FROM ".tablename('uni_settings')." WHERE uniacid='{$_W['uniacid']}'");
    if(!empty($uni_setting) && is_array($uni_setting)){
        $item = iunserializer($uni_setting['sms']);
    }
    if($_W['isajax']){
        if(!check_data($_GPC['setting'])){
            message('请填写参数','','error');
        }
        foreach($_GPC['setting'] as $k => &$v){
            $v = trim($v);
        }
        $data = array(
            'sms' => iserializer($_GPC['setting'])
        );
        if(!empty($uni_setting) && is_array($uni_setting)){
            //修改
            $data['updatetime'] = TIMESTAMP;
            $status = pdo_update('uni_settings',$data,array('uniacid'=>$_W['uniacid']));
        }else{
            $data['createtime'] = TIMESTAMP;
            $data['uniacid'] = $_W['uniacid'];
            $status = pdo_insert('uni_settings',$data);
        }
        if(!$status){
            message('设置失败','','error');
        }
        message('设置成功',referer(),'success');
    }
}
template('sms/display');
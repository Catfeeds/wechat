<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/3/29
 * Time: 19:45
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
if($do == 'display'){
    if($_W['isajax']){
        $id = floor(trim($_GPC['id']));
        if(trim($_GPC['op_type'] == 'delete')){
            $delete_status = pdo_delete('mc_member_address',array('uniacid'=>$_W['uniacid'],'uid'=>$_W['member']['uid'],'id'=>$id));
            if(!$delete_status){
                message('删除失败','','error');
            }
            message('删除成功',referer(),'success');
        }else{
            //将默认的设为非默认
            pdo_update('mc_member_address',array(
                'isdefault' => NOT_DEFAULT,
                'updatetime' => TIMESTAMP
            ),array(
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['member']['uid'],
                'isdefault' => IS_DEFAULT
            ));
            //设为默认
            $set_status = pdo_update('mc_member_address',array(
                'isdefault'=>IS_DEFAULT,
                'updatetime'=>TIMESTAMP
            ),array(
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['member']['uid'],
                'id' => $id
            ));
            if(!$set_status){
                message('设置失败','','error');
            }
            message('设置成功',referer(),'success');
        }
        message('未知操作','','error');
    }
    $psize = 50;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$pszie;
    $list = pdo_fetchall("SELECT * FROM ".tablename('mc_member_address')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' ORDER BY createtime DESC LIMIT {$pindex},{$psize}");

}elseif($do == 'post'){
    load()->app('tpl');
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = pdo_get('mc_member_address',array('uniacid'=>$_W['uniacid'],'uid'=>$_W['member']['uid'],'id'=>$id));
    }
    if($_W['isajax']){
        $data = array(
            'username' => trim($_GPC['username']),
            'mobile' => trim($_GPC['mobile']),
            'zipcode' => trim($_GPC['zipcode']),
            'province' => trim($_GPC['area']['province']),
            'city' => trim($_GPC['area']['city']),
            'district' => trim($_GPC['area']['district']),
            'address' => trim($_GPC['address']),
            'isdefault' => floor(trim($_GPC['isdefault'])) == IS_DEFAULT?IS_DEFAULT:NOT_DEFAULT
        );
        $error = array(
            'username' => '请输入姓名',
            'mobile' => '请输入手机号',
            'province' => '请选择省份',
            'city' => '请选择城市',
            'district' => '请选择区县',
            'address' => '请填写详细地址'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        if(empty($item)){
            //添加
            $data['uniacid'] = $_W['uniacid'];
            $data['uid'] = $_W['member']['uid'];
            $data['createtime'] = TIMESTAMP;
            $tip = '添加';
            if($data['isdefault'] == IS_DEFAULT){
                pdo_update('mc_member_address',array('isdefault'=>NOT_DEFAULT),array('uniacid'=>$_W['uniacid'],'uid'=>$_W['member']['uid'],'isdefault'=>IS_DEFAULT));
            }
            $status = pdo_insert('mc_member_address',$data);
        }else{
            //修改
            $data['updatetime'] = TIMESTAMP;
            $tip = '修改';
            if($data['isdefault'] == IS_DEFAULT){
                pdo_update('mc_member_address',array('isdefault'=>NOT_DEFAULT),array('uniacid'=>$_W['uniacid'],'uid'=>$_W['member']['uid'],'isdefault'=>IS_DEFAULT));
            }
            $status = pdo_update('mc_member_address',$data,array('uniacid'=>$_W['uniacid'],'uid'=>$_W['member']['uid'],'id'=>$id));
        }
        if(!$status){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
}
template('set/address');
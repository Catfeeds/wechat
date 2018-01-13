<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/20
 * Time: 16:57
 */
global  $_W,$_GPC;

//会员列表
if($op == 'display'){
    if($_W['isajax']){
        if(!check_data($_GPC['ids'])){
            message('请选择要删除的信息','','error');
        }
        $delete_status = pdo_query("DELETE FROM ".tablename('vapp_member')." WHERE uniacid='{$_W['uniacid']}' AND uid IN (".implode(',',$_GPC['ids']).")");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $page = getApartPageNo();
    $psize = 20;
    $pindex = ($page - 1)*$psize;
    $where = "uniacid='{$_W['uniacid']}'";
    $uid = floor(trim($_GPC['uid']));
    if(check_id($uid)){
        $where .= " AND uid='{$uid}'";
    }
    $keyword = trim($_GPC['keyword']);
    if(!empty($keyword) && is_string($keyword)){
        $where .= " AND (mobile LIKE '%{$keyword}%' OR nickname LIKE '%{$keyword}%' OR realname LIKE '%{$keyword}%')";
    }
    $is_check = $_GPC['is_check'];
    if(check_data($is_check)){
        $where .= " AND is_check IN (".implode(',',$is_check).")";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('vapp_member')." WHERE {$where} ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('vapp_member')." WHERE {$where}"),$page,$psize);
}


//添加删除会员
if($op == 'post'){
    $id = floor(trim($_GPC['id']));
    if(!empty($id)) {
        $item = pdo_get('vapp_member',array(
            'uniacid' => $_W['uniacid'],
            'uid' => $id
        ));
    }
    if($_W['isajax']){
        $data = array(
            'uniacid' => $_W['uniacid'],
            'parent_uid' => intval(trim($_GPC['parent_uid'])),
            'parent_last_time' => !empty($_GPC['parent_last_time'])?strtotime(trim($_GPC['parent_last_time'])):TIMESTAMP,
            'mobile' => trim($_GPC['mobile']),
            'age' => floor(trim($_GPC['age'])),
            'gender' => floor(trim($_GPC['gender']))== 1?1:(floor(trim($_GPC['gender']))== 2?2:0),
            'avatar' => trim($_GPC['avatar']),
            'level' => floor(trim($_GPC['level'])),
            'salt' => !empty($item['salt'])?$item['salt']:random(8),
            'realname' => trim($_GPC['realname']),
            'nickname' => trim($_GPC['nickname']),
            'province' => trim($_GPC['area']['province']),
            'city' => trim($_GPC['area']['city']),
            'district' => trim($_GPC['area']['district']),
            'credit1' => floor(trim($_GPC['credit1'])),
            'credit2' => floor(trim($_GPC['credit2'])),
            'is_agent' => floor(trim($_GPC['is_agent'])) == 1?1:0,
            'agent_level' => floor(trim($_GPC['agent_level'])),
            'agent_province' => trim($_GPC['agent_area']['province']),
            'agent_city' => trim($_GPC['agent_area']['city']),
            'agent_district' => trim($_GPC['agent_area']['district']),
            'is_check' => floor(trim($_GPC['is_check'])) == 1?1:0
        );
        $error = array(
            'mobile' => '请输入手机号'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
                break;
            }
            if($k == 'mobile' && !check_mobile($data[$k])){
                message('手机号格式错误','','error');
            }
        }
        if(!empty($_GPC['password'])){
            if($_GPC['password'] != $_GPC['repassword']){
                message('两次密码输入不一致！','','error');
            }
            $data['password'] = md5_password($_GPC['password'],$data['salt']);
        }
        $is_exist_username = pdo_get('vapp_member',array(
            'uniacid' => $_W['uniacid'],
            'mobile' => $data['mobile']
        ));
        if(empty($item)){ //添加
            if(!empty($is_exist_username)){
                message('该手机号已被占用！请更换重试！','','error');
            }
            if(empty($data['password'])){
                message('请输入会员密码！','','error');
            }
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $tip = "添加";
            $flag = pdo_insert('vapp_member',$data);
        }else{ //修改
            if(!empty($is_exist_username['mobile']) && $item['mobile'] != $data['mobile']){
                message('该手机号已被占用！请更换重试！','','error');
            }
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = pdo_update('vapp_member',$data,array(
                'uniacid' => $_W['uniacid'],
                'uid' => $id
            ));
        }
        if(!$flag){
            message("{$tip}失败！请重试！",'','error');
        }
        message("{$tip}成功！",referer(),'success');
    }
}



include $this->template('member');
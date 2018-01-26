<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/5/29
 * Time: 15:21
 */
if($op == 'display'){
    if($_W['isajax']){
        if(trim($_GPC['ac']) == 'delete'){
            $delete_status = OtoModel::deleteAgentByIds($_GPC['ids']);
            if(!$delete_status){
                message('删除失败！','','error');
            }
            message('删除成功！',referer(),'success');
        }
    }
    $psize = 20;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $is_check = $_GPC['is_check'];
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $list = OtoModel::getAgentList($keyword,$is_check,$province,$city,$district,$pindex,$psize);
    $total = OtoModel::getAgentCount($keyword,$is_check,$province,$city,$district);
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);

}elseif($op == 'post'){
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = OtoModel::getAgentInfoById($id);
    }
    if($_W['isajax']){
        $data = array(
            'uid' => floor(trim($_GPC['uid'])),
            'province' => $_GPC['area']['province'],
            'city' => $_GPC['area']['city'],
            'district' => $_GPC['area']['district']
        );
        $error = array(
            'uid' => '请输入会员ID',
            'province' =>'请选择省份',
            'city' => '请选择城市'
        );
        if(!empty($data['city'])){
            if(in_array($data['city'],OtoModel::$bigCity)){
                $error['district'] = '请选择区县';
            }
        }
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }

        $exist_agent = OtoModel::getAgentInfoByUid($data['uid']);
        if(!empty($item)){
            $data['updatetime'] = TIMESTAMP;
            if(!empty($exist_agent) && is_array($exist_agent)){
               if( $exist_agent['uid'] != $data['uid']){
                   message('代理信息已存在','','error');
               }
            }
            $status = OtoModel::updateAgentInfoById($id,$data);
            $tip = "修改";
        }else{
            if(!empty($exist_agent) && is_array($exist_agent)){
                message('代理信息已存在','','error');
            }
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $status = OtoModel::insertAgentInfo($data);
            $tip = "添加";
        }
        if(!$status){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
}elseif($op == 'servicer'){
    if($_W['isajax']){
        if(trim($_GPC['ac']) == 'delete'){
            $delete_status = OtoModel::deleteServicerByIds($_GPC['ids']);
            if(!$delete_status){
                message('删除失败！','','error');
            }
            message('删除成功！',referer(),'success');
        }
    }
    $psize = 20;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $is_check = $_GPC['is_check'];
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $list = OtoModel::getServicerList($keyword,$is_check,$province,$city,$district,$pindex,$psize);
    $total = OtoModel::getAgentCount($keyword,$is_check,$province,$city,$district);
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);

}elseif($op == 'post_servicer'){
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = OtoModel::getServicerInfoById($id);
    }
    if($_W['isajax']){
        $data = array(
            'username' => trim($_GPC['username']),
            'salt' => isset($item['salt'])?$item['salt']:random(8),
            'province' => trim($_GPC['area']['province']),
            'city' => trim($_GPC['area']['city']),
            'district' => trim($_GPC['area']['district'])
        );
        $error = array(
            'username' => '请输入登录帐号',
            'province' => "请选择省份",
            'city' => "请选择城市"
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
                break;
            }
        }
        if(in_array($data['city'],OtoModel::$bigCity)){
            if(empty($data['district'])){
                message('请选择区县','','error');
            }
        }
        if(!empty($_GPC['password'])){
            if($_GPC['password'] != $_GPC['repassword']){
                message('两次密码输入不一致！','','error');
            }
            load()->model('user');
            $data['password'] = user_hash($_GPC['password'],$data['salt']);
        }
        if(empty($item)){ //添加
            $is_exist_username = OtoModel::getServicerInfoByUsername($data['username']);
            if(!empty($is_exist_username)){
                message('该登录账号已被占用！请更换重试！','','error');
            }
            if(empty($data['password'])){
                message('请输入客服登录密码！','','error');
            }
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $tip = "添加";
            $flag = OtoModel::insertServicerInfo($data);
        }else{ //修改
            if($item['username'] != $data['username']){
                $is_exist_username =OtoModel::getServicerInfoByUsername($data['username']);
                if(!empty($is_exist_username)){
                    message('该客服登录账号已被占用！请更换重试！','','error');
                }
            }
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = OtoModel::updateServicerInfoById($id,$data);
        }
        if(!$flag){
            message("{$tip}失败！请重试！",'','error');
        }
        message("{$tip}成功！",referer(),'success');
    }
}elseif($op == 'account'){
    if($_W['isajax']){
        if(trim($_GPC['ac']) == 'delete'){
            $delete_status = $delete_status = pdo_query("DELETE FROM ".tablename('account_user')." WHERE uniacid='{$_W['uniacid']}' AND id IN (".implode(',',$_GPC['ids']).")");
            if(!$delete_status){
                message('删除失败！','','error');
            }
            message('删除成功！',referer(),'success');
        }
    }
    $psize = 20;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $is_check = $_GPC['is_check'];
    $where = "uniacid='{$_W['uniacid']}'";
    if(!empty($keyword)){
        $where .= " AND username LIKE '%{$keyword}%'";
    }
    if(!empty($is_check) && is_array($is_check)){
        $where .= " AND is_check IN (".implode(',',$is_check).")";
    }
    $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('account_user')." WHERE {$where}");
    $where .= " ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT * FROM ".tablename('account_user')." WHERE {$where}");
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);
}elseif($op == 'post_account'){
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = pdo_get('account_user',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    if($_W['isajax']){
        $data = array(
            'username' => trim($_GPC['username']),
            'salt' => isset($item['salt'])?$item['salt']:random(8)
        );
        $error = array(
            'username' => '请输入登录帐号'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
                break;
            }
        }
        if(!empty($_GPC['password'])){
            if($_GPC['password'] != $_GPC['repassword']){
                message('两次密码输入不一致！','','error');
            }
            load()->model('user');
            $data['password'] = user_hash($_GPC['password'],$data['salt']);
        }
        if(empty($item)){ //添加
            $is_exist_username = pdo_get('account_user',array(
                'uniacid' => $_W['uniacid'],
                'username' => $data['username']
            ));
            if(!empty($is_exist_username)){
                message('该会计登录账号已被占用！请更换重试！','','error');
            }
            if(empty($data['password'])){
                message('请输入会计登录密码！','','error');
            }
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $tip = "添加";
            $flag = pdo_insert('account_user',$data);
        }else{ //修改
            if($item['username'] != $data['username']){
                $is_exist_username =pdo_get('account_user',array(
                    'uniacid' => $_W['uniacid'],
                    'username' => $data['username']
                ));
                if(!empty($is_exist_username)){
                    message('该会计登录账号已被占用！请更换重试！','','error');
                }
            }
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = pdo_update('account_user',$data,array(
                'uniacid'=>$_W['uniacid'],
                'id' => $id
            ));
        }
        if(!$flag){
            message("{$tip}失败！请重试！",'','error');
        }
        message("{$tip}成功！",referer(),'success');
    }
}

include $this->template('staff');
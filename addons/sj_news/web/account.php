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
            $delete_status = $delete_status = pdo_query("DELETE FROM ".tablename('sj_news_ad_account')." WHERE uniacid='{$_W['uniacid']}' AND id IN (".implode(',',$_GPC['ids']).")");
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
    $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('sj_news_ad_account')." WHERE {$where}");
    $where .= " ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT * FROM ".tablename('sj_news_ad_account')." WHERE {$where}");
    $pager = pagination($total,floor(trim($_GPC['page'])),$psize);
}elseif($op == 'post'){
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = pdo_get('sj_news_ad_account',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    if($_W['isajax']){
        $data = array(
            'username' => trim($_GPC['username']),
            'type' => floor(trim($_GPC['type'])) == 1?1:0,
            'province' => trim($_GPC['area']['province']),
            'city' => trim($_GPC['area']['city']),
            'district' => trim($_GPC['area']['district']),
            'salt' => isset($item['salt'])?$item['salt']:random(8)
        );
        $error = array(
            'username' => '请输入登录帐号',
            'province' => '请选择省份',
            'city' => '请选择城市',
            'district' => '请选择区县'
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
            $is_exist_username = pdo_get('sj_news_ad_account',array(
                'uniacid' => $_W['uniacid'],
                'username' => $data['username']
            ));
            if(!empty($is_exist_username)){
                message('该管理员登录账号已被占用！请更换重试！','','error');
            }
            if(empty($data['password'])){
                message('请输入管理员登录密码！','','error');
            }
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $tip = "添加";
            $flag = pdo_insert('sj_news_ad_account',$data);
        }else{ //修改
            if($item['username'] != $data['username']){
                $is_exist_username =pdo_get('sj_news_ad_account',array(
                    'uniacid' => $_W['uniacid'],
                    'username' => $data['username']
                ));
                if(!empty($is_exist_username)){
                    message('该管理员登录账号已被占用！请更换重试！','','error');
                }
            }
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = pdo_update('sj_news_ad_account',$data,array(
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

include $this->template('account');
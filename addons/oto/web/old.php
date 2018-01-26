<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/6/3
 * Time: 0:25
 */
load()->func('check');
if($op == 'display'){
    $item = pdo_get('fangyuanbao_old_config',array(
        'uniacid' => $_W['uniacid']
    ));
    if(!empty($item['setting'])){
        $item['setting'] = iunserializer($item['setting']);
    }
    if($_W['isajax']){
       $data = array(
           'setting' => iserializer($_GPC['setting'])
       );
       if(empty($item)){
           $data['uniacid'] = $_W['uniacid'];
           $data['createtime'] = TIMESTAMP;
           $status = pdo_insert('fangyuanbao_old_config',$data);
       }else{
           $data['updatetime'] = TIMESTAMP;
           $status = pdo_update('fangyuanbao_old_config',$data,array('uniacid'=>$_W['uniacid']));
       }
        if(!$status){
            message('设置失败','','error');
        }
        message('设置成功',referer(),'success');
    }
}elseif($op == 'order'){
    $keyword = trim($_GPC['keyword']);
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $product = $_GPC['product'];
    $pay_status = $_GPC['pay_status'];
    $pay_methods = $_GPC['pay_methods'];
    $where = "a.uniacid='{$_W['uniacid']}'";
    if(!empty($keyword)){
        $where .= " AND (a.uid='{$keyword}' OR a.order_no LIKE '%{$keyword}%' OR b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
    }
    if(!empty($product)){
        $where .= " AND product_key IN (".implode(',',$product).")";
    }
    if(!empty($pay_status)){
        $where .= " AND pay_status IN (".implode(',',$pay_status).")";
    }
    if(!empty($pay_methods)){
        if(in_array(PAY_METHOD_WECHAT,$pay_methods)){
            array_push($pay_methods,PAY_METHOD_FUIOU);
        }
        $where .= " AND pay_method IN (".implode(',',$pay_methods).")";
    }
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('fangyuanbao_user_order')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $total_pay_price = pdo_fetchcolumn("SELECT SUM(a.pay_price) FROM ".tablename('fangyuanbao_user_order')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $where .=" ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.nickname FROM ".tablename('fangyuanbao_user_order')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}elseif($op == 'rebate'){
    $keyword = trim($_GPC['keyword']);
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $where = "a.uniacid='{$_W['uniacid']}'";
    if(!empty($keyword)){
        $where .= " AND (a.uid='{$keyword}' OR a.buy_uid='{$keyword}' OR a.order_no LIKE '%{$keyword}%' OR b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
    }
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('fangyuanbao_user_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $where .=" ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.nickname FROM ".tablename('fangyuanbao_user_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}elseif($op == 'user'){
    $keyword = trim($_GPC['keyword']);
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $product = $_GPC['product'];
    $where = "a.uniacid='{$_W['uniacid']}'";
    if(!empty($keyword)){
        $where .= " AND (a.uid='{$keyword}'  OR b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
    }
    if(!empty($product)){
        $where .= " AND product_key IN (".implode(',',$product).")";
    }
    $total = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('fangyuanbao_user')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $where .=" ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.nickname FROM ".tablename('fangyuanbao_user')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}elseif($op == 'post_user'){
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = pdo_get('fangyuanbao_user',array(
            'uniacid'=>$_W['uniacid'],
            'id' => $id
        ));
    }
    if($_W['isajax']){
        $data = array(
            'product_key' => floor(trim($_GPC['product_key'])),
            'uid' => floor(trim($_GPC['uid']))
        );
        if(empty($data['uid'])){
            message('请输入会员编号','','error');
        }
        if(empty($data['product_key'])){
            message('请选择套餐','','error');
        }
        if(empty($item)){//添加
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $data['price'] = 0;
            $is_exist = pdo_get('fangyuanbao_user',array(
                'uniacid' => $_W['uniacid'],
                'uid' => $data['uid']
            ));
            if(check_data($is_exist)){
                message('会员信息已存在','','error');
            }
            $tip = "添加";
            $status = pdo_insert('fangyuanbao_user',$data);
        }else{//修改
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            if($data['uid'] != $item['uid']){
                $is_exist = pdo_get('fangyuanbao_user',array(
                    'uniacid' => $_W['uniacid'],
                    'uid' => $data['uid']
                ));
                if(check_data($is_exist)){
                    message('会员信息已存在','','error');
                }
            }
            $status = pdo_update('fangyuanbao_user',$data,array(
                'uniacid' => $_W['uniacid'],
                'uid' => $data['uid']
            ));
        }
        if(!$status){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
}
include $this->template('old');
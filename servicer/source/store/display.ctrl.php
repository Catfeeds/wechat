<?php
if(empty($do)){$do = 'display';}
if($do == 'display'){
//商家列表页
    $categoryList = ServicerModel::getStoreCategoryList();
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $is_display = $_GPC['is_display'];
    $province = $_W['province'];
    $city = $_W['city'];
    $district = trim($_GPC['area']['district']);
    if($_W['is_big_city']){
        $district = $_W['district'];
    }
    $list = ServicerModel::getStoreList($keyword,$is_display,$province,$city,$district,$pindex,$psize,EXPORT_NO);
    $pager = pagination(ServicerModel::getStoreCount($keyword,$is_display,$province,$city,$district),floor(trim($_GPC['page'])),$psize);
}elseif($do == 'post'){
    if($_W['isajax']){
        $order_by = $_GPC['order_by'];
        if(trim($_GPC['ac']) == 'update_no'){
            if(!empty($_GPC['order_by_ids']) && is_array($_GPC['order_by_ids'])){
                pdo_begin();
                foreach($_GPC['order_by_ids'] as $k => $id){
                    $status = ServicerModel::updateStoreInfoById($id,array(
                        'order_by' => $order_by[$id],
                        'updatetime' => TIMESTAMP
                    ));
                    if(!$status){
                        pdo_rollback();
                        message('更新失败','','error');
                    }
                }
                pdo_commit();
                message('更新成功',referer(),'success');
            }
        }
    }
    if(trim($_GPC['ac']) == 'delete'){
        $delete_status = ServicerModel::deleteStoreInfoByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    //修改、添加、删除商家
    $categoryList = ServicerModel::getStoreCategoryList();
    $id = floor(trim($_GPC['id']));
    if(!empty($id)) {
        $item = ServicerModel::getStoreInfoById($id);
    }
    if($_W['isajax']){
        $data = array(
            'apply_uid' => intval(trim($_GPC['apply_uid'])),
            'saler_uid' => intval(trim($_GPC['saler_uid'])),
            'referrer_uid' => intval(trim($_GPC['referrer_uid'])),
            'category_id' => intval(trim($_GPC['category_id'])),
            'type' => STORE_TYPE_OTO,
            'logo' => trim($_GPC['logo']),
            'title' => trim($_GPC['title']),
            'username' => trim($_GPC['username']),
            'salt' => isset($item['salt'])?$item['salt']:random(8),
            'desc' => $_GPC['desc'],
            'province' => trim($_GPC['area']['province']),
            'city' => trim($_GPC['area']['city']),
            'district' => trim($_GPC['area']['district']),
            'address' => trim($_GPC['address']),
            'lng' => doubleval(trim($_GPC['map']['lng'])),
            'lat' => doubleval(trim($_GPC['map']['lat'])),
            'contacts' => trim($_GPC['contacts']),
            'tel' => trim($_GPC['tel']),
            'email' => trim($_GPC['email']),
            'qq' => trim($_GPC['qq']),
            'weixin' => trim($_GPC['weixin']),
            'notice' => trim($_GPC['notice']),
            'is_display' => floor(trim($_GPC['is_display'])) == DISPLAY_YES?DISPLAY_YES:DISPLAY_NO,
            'is_check' => floor(trim($_GPC['is_check'])) == CHECK_PASS?CHECK_PASS:CHECK_NOT_PASS,
            'order_by' => floor(trim($_GPC['order_by']))
        );
        $error = array(
            'username' => '请输入商家登录用户名！',
            'category_id' => '请选择行业分类！',
            'logo' => '请选择商家logo!',
            'title' => '请输入店铺名称！',
            'province' => '请选择店铺所在省份！',
            'city' => '请选择店铺所在城市！',
            'district' => '请选择店铺所在区/县！',
            'address' => '请输入店铺详细地址！',
            'lng' => '请输入店铺所在经度！',
            'lat' => '请输入店铺所在纬度！',
            'contacts' => '请输入店铺联系人！',
            'tel' => '请输入店铺联系人电话！'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
                break;
            }
        }
        if($data['province'] != $_W['province'] || $data['city'] != $_W['city']){
            message('店铺地址请选择您所管辖的地区','','error');
        }
        if($_W['is_big_city'] && $data['district'] != $_W['district']){
            message('店铺地址请选择您所管辖的区县','','error');
        }
        if(!empty($_GPC['password'])){
            if($_GPC['password'] != $_GPC['repassword']){
                message('两次密码输入不一致！','','error');
            }
            load()->model('user');
            $data['password'] = user_hash($_GPC['password'],$data['salt']);
        }
        if(empty($item)){ //添加
            $is_exist_username = ServicerModel::getStoreInfoByUsername($data['username']);
            if(!empty($is_exist_username)){
                message('该用户名已被占用！请更换重试！','','error');
            }
            if(empty($data['password'])){
                message('请输入商家登录密码！','','error');
            }
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $tip = "添加";
            $flag = ServicerModel::insertStoreInfo($data);
        }else{ //修改
            if($item['username'] != $data['username']){
                $is_exist_username =ServicerModel::getStoreInfoByUsername($data['username']);
                if(!empty($is_exist_username)){
                    message('该用户名已被占用！请更换重试！','','error');
                }
            }
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = ServicerModel::updateStoreInfoById($id,$data);
        }
        if(!$flag){
            message("{$tip}失败！请重试！",'','error');
        }
        message("{$tip}成功！",referer(),'success');
    }
}

template('store/display');
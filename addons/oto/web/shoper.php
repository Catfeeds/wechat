<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/15
 * Time: 10:58
 */
if($op == 'display'){
    //商家列表页
    $categoryList = OtoModel::getStoreCategoryList(0,0,0,100,EXPORT_YES);
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $is_display = $_GPC['is_display'];
    if(checksubmit('export_submit', true)){
        $list = OtoModel::getStoreList($keyword,$is_display,0,0,EXPORT_YES);
        export_store_list($list,$categoryList);
    }
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $list = OtoModel::getStoreList($keyword,$is_display,$province,$city,$district,$pindex,$psize,EXPORT_NO);
    $pager = pagination(OtoModel::getStoreCount($keyword,$is_display,$province,$city,$district),floor(trim($_GPC['page'])),$psize);
}elseif($op == 'post_shop'){
    if($_W['isajax']){
        $order_by = $_GPC['order_by'];
        if(trim($_GPC['ac']) == 'update_no'){
            if(!empty($_GPC['order_by_ids']) && is_array($_GPC['order_by_ids'])){
                pdo_begin();
                foreach($_GPC['order_by_ids'] as $k => $id){
                    $status = OtoModel::updateStoreInfoById($id,array(
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
        $delete_status = OtoModel::deleteStoreInfoByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    //修改、添加、删除商家
    $categoryList = OtoModel::getStoreCategoryList(0,0,0,100,EXPORT_NO);
    if(empty($categoryList)){
        message('请先添加分类！',$this->createWebUrl('shoper',array('op'=>'post_category')),'error');
    }
    $id = floor(trim($_GPC['id']));
    if(!empty($id)) {
        $item = OtoModel::getStoreInfoById($id);
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
            'is_free' => floor(trim($_GPC['is_free'])) == 1?1:0,
            'is_check' => floor(trim($_GPC['is_check'])) == CHECK_PASS?CHECK_PASS:CHECK_NOT_PASS,
            'order_by' => floor(trim($_GPC['order_by']))
        );
        $error = array(
            'category_id' => '请选择行业分类！',
            'logo' => '请选择商家logo!',
            'title' => '请输入店铺名称！',
            'username' => '请输入商家登录用户名！',
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
        if(!empty($_GPC['password'])){
            if($_GPC['password'] != $_GPC['repassword']){
                message('两次密码输入不一致！','','error');
            }
            load()->model('user');
            $data['password'] = user_hash($_GPC['password'],$data['salt']);
        }
        if(empty($item)){ //添加
            $is_exist_username = OtoModel::getStoreInfoByUsername($data['username']);
            if(!empty($is_exist_username)){
                message('该用户名已被占用！请更换重试！','','error');
            }
            if(empty($data['password'])){
                message('请输入商家登录密码！','','error');
            }
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $tip = "添加";
            $flag = OtoModel::insertStoreInfo($data);
        }else{ //修改
            if($item['username'] != $data['username']){
                $is_exist_username =OtoModel::getStoreInfoByUsername($data['username']);
                if(!empty($is_exist_username)){
                    message('该用户名已被占用！请更换重试！','','error');
                }
            }
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = OtoModel::updateStoreInfoById($id,$data);
        }
        if(!$flag){
            message("{$tip}失败！请重试！",'','error');
        }
        message("{$tip}成功！",referer(),'success');
    }
}elseif($op == 'category'){
    //商家分类列表
    $keyword = trim($_GPC['keyword']);
    $is_display = $_GPC['is_display'];
    if(checksubmit('export_submit', true)){
        $list = OtoModel::getStoreCategoryList($keyword,$is_display,'','',EXPORT_YES);
        export_store_category($list);
    }
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $list = OtoModel::getStoreCategoryList($keyword,$is_display,$pindex,$psize,EXPORT_NO);
    $pager = pagination(OtoModel::getStoreCategoryCount($keyword,$is_display),floor(trim($_GPC['page'])),$psize);
}elseif( $op == 'post_category'){
    //添加、删除、修改商家分类
    if(trim($_GPC['ac']) == 'delete'){
        $delete_status = OtoModel::deleteStoreCategoryInfoByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = OtoModel::getStoreCategoryInfoById($id);
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'store_type' => STORE_TYPE_OTO,
            'is_display' => floor(trim($_GPC['is_display'])) == DISPLAY_YES?DISPLAY_YES:DISPLAY_NO,
            'thumb' => trim($_GPC['thumb']),
            'order_by' => floor(trim($_GPC['order_by']))
        );
        if(empty($data['title'])){
            message('请输入分类标题！','','error');
        }
        if(empty($item)){
            $is_exist_category = OtoModel::getStoreCategoryInfoByTitle($data['title']);
            if(!empty($is_exist_category)){
                message('分类已经存在！','','error');
            }
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $flag = OtoModel::insertStoreCategoryInfo($data);
            $tip = "添加";
        }else{
            $data['updatetime'] = TIMESTAMP;
            $flag = OtoModel::updateStoreCategoryInfoById($id,$data);
            $tip = "修改";
        }
        if(!$flag){
            message("{$tip}失败！",'','error');
        }
        message("{$tip}成功！",referer(),'success');
    }
}elseif($op == 'apply'){
    if($_W['isajax']){
        $id = floor(trim($_GPC['id']));
        //添加、删除、修改商家分类
        if(trim($_GPC['ac']) == 'delete'){
            $delete_status = OtoModel::deleteStoreApplyById($id);
            if(!$delete_status){
                message('删除失败！','','error');
            }
            message('删除成功！',referer(),'success');
        }
        $is_check = floor(trim($_GPC['is_check'])) == CHECK_PASS?CHECK_PASS:CHECK_NOT_PASS;
        $tip = $is_check == CHECK_PASS?'通过审核':'取消通过';
        $check_status = OtoModel::updateStoreApplyInfoById($id,array('is_check'=>$is_check));
        if(!$check_status){
            message("{$tip}失败！",'','error');
        }
        message("{$tip}成功！",'','success');
    }
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $province = trim($_GPC['area']['province']);
    $city = trim($_GPC['area']['city']);
    $district = trim($_GPC['area']['district']);
    $is_check = $_GPC['is_check'];
    $starttime = empty($_GPC['createtime']['start']) ? strtotime('-90 days') : strtotime($_GPC['createtime']['start']);
    $endtime = empty($_GPC['createtime']['end']) ? TIMESTAMP + 86399 : strtotime($_GPC['createtime']['end']) + 86399;
    if(checksubmit('export_submit', true)){
        $list = OtoModel::getStoreApplyList($keyword,$province,$city,$district,$is_check,$starttime,$endtime,0,0,EXPORT_YES);
        export_store_apply_list($list);
    }
    $list = $list = OtoModel::getStoreApplyList($keyword,$province,$city,$district,$is_check,$starttime,$endtime,$pindex,$psize,EXPORT_NO);
    $pager = pagination(OtoModel::getStoreApplyCount($keyword,$province,$city,$district,$is_check,$starttime,$endtime),floor(trim($_GPC['page'])),$psize);
}

/* 导出 店铺 列表 */
function export_store_apply_list($list = array()){
    if(!empty($list) && is_array($list)){
        $index = 1;
        foreach($list as $k => &$v){
            $v['no'] = $index++;
            $v['type'] = $v['type'] == APPLY_TYPE_PERSON?'个人':'公司';
            $v['shop_type'] = STORE_TYPE_OTO;
            $v['is_check'] = $v['is_check'] == DISPLAY_YES?'通过':'未通过';
            if(!empty($v['updatetime'])){
                $v['updatetime'] = date('Y-m-d H:i:s',$v['updatetime']);
            }
            if(!empty($v['createtime'])){
                $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
            }
        }
    }else{
        message('导出的数据不存在！','','error');
    }
    load()->classs('export');
    $excel = new ExcelTool();
    $columns =array(
        array(
            "title" => '序号',
            "field" => "no",
            "width" => 10
        ) ,
        array(
            "title" => '申请会员',
            "field" => "uid",
            "width" => 10
        ) ,
        array(
            "title" => '联系人',
            "field" => "contacts",
            "width" => 10
        ) ,
        array(
            "title" => '联系电话',
            "field" => "tel",
            "width" => 20
        ) ,
        array(
            "title" => '经营内容',
            "field" => "manage_content",
            "width" => 40
        ) ,
        array(
            "title" => '店长会员ID',
            "field" => "saler_uid",
            "width" => 10
        ) ,
        array(
            "title" => '推荐人会员ID',
            "field" => "referrer_uid",
            "width" => 10
        ) ,
        array(
            "title" => '用户类型',
            "field" => "type",
            "width" => 10
        ) ,
        array(
            "title" => '店铺类型',
            "field" => "apply.php",
            "width" => 10
        ) ,
        array(
            "title" => '审核状态',
            "field" => "is_check",
            "width" => 10
        ) ,
        array(
            "title" => '省份',
            "field" => "province",
            "width" => 20
        ) ,
        array(
            "title" => '城市',
            "field" => "city",
            "width" => 20
        ) ,
        array(
            "title" => '区县',
            "field" => "district",
            "width" => 20
        ) ,
        array(
            "title" => '显示状态',
            "field" => "is_display",
            "width" => 12
        ) ,
        array(
            "title" => '详细地址',
            "field" => "address",
            "width" => 12
        ) ,
        array(
            "title" => 'IP地址',
            "field" => "ip",
            "width" => 20
        ) ,
        array(
            "title" => '修改时间',
            "field" => "updatetime",
            "width" => 12
        ) ,
        array(
            "title" => '添加时间',
            "field" => "createtime",
            "width" => 20
        )
    );
    $excel->export(
        $list,//导出的数据数组
        array(
            "title" => "OTO商家申请数据-" . date("Y-m-d-H-i", time()) , //标题
            "columns" => $columns //数据列
        )
    );
}

/* 导出 店铺 分类 */
function export_store_category($list = array()){
    if(!empty($list) && is_array($list)){
        $index = 1;
        foreach($list as $k => &$v){
            $v['no'] = $index++;
            $v['is_display'] = $v['is_display'] == DISPLAY_YES?'显示':'隐藏';
            if(!empty($v['updatetime'])){
                $v['updatetime'] = date('Y-m-d H:i:s',$v['updatetime']);
            }
            if(!empty($v['createtime'])){
                $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
            }
        }
    }else{
        message('导出的数据不存在！','','error');
    }
    load()->classs('export');
    $excel = new ExcelTool();
    $columns =array(
        array(
            "title" => '序号',
            "field" => "no",//和数组的key对应
            "width" => 12
        ) ,
        array(
            "title" => '标题',
            "field" => "title",//和数组的key对应
            "width" => 40
        ) ,
        array(
            "title" => '状态',
            "field" => "is_display",//和数组的key对应
            "width" => 12
        ) ,
        array(
            "title" => '排序',
            "field" => "order_by",//和数组的key对应
            "width" => 12
        ) ,
        array(
            "title" => '修改时间',
            "field" => "updatetime",//和数组的key对应
            "width" => 40
        ) ,
        array(
            "title" => '添加时间',
            "field" => "createtime",//和数组的key对应
            "width" => 40
        )
    );
    $excel->export(
        $list,//导出的数据数组
        array(
            "title" => "多用户商城（BBC）分类数据-" . date("Y-m-d-H-i", time()) , //标题
            "columns" => $columns //数据列
        )
    );
}

/* 导出 店铺 列表 */
function export_store_list($list = array(),$category = array()){
    if(!empty($list) && is_array($list)){
        $index = 1;
        foreach($list as $k => &$v){
            $v['no'] = $index++;
            $v['type'] = '多用户商城（BBC）';
            $v['category'] = isset($category[$v['category_id']])?$category[$v['category_id']]:'';
            $v['is_display'] = $v['is_display'] == DISPLAY_YES?'显示':'隐藏';
            $v['is_check'] = $v['is_check'] == DISPLAY_YES?'通过':'未通过';
            if(!empty($v['updatetime'])){
                $v['updatetime'] = date('Y-m-d H:i:s',$v['updatetime']);
            }
            if(!empty($v['createtime'])){
                $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
            }
        }
    }else{
        message('导出的数据不存在！','','error');
    }
    load()->classs('export');
    $excel = new ExcelTool();
    $columns =array(
        array(
            "title" => '序号',
            "field" => "no",//和数组的key对应
            "width" => 12
        ) ,
        array(
            "title" => '店主会员ID',
            "field" => "saler_uid",//和数组的key对应
            "width" => 12
        ) ,
        array(
            "title" => '推荐人会员ID',
            "field" => "referrer_uid",//和数组的key对应
            "width" => 12
        ) ,
        array(
            "title" => '行业分类',
            "field" => "category",//和数组的key对应
            "width" => 40
        ) ,
        array(
            "title" => '店铺名称',
            "field" => "title",//和数组的key对应
            "width" => 40
        ) ,
        array(
            "title" => '省份',
            "field" => "province",//和数组的key对应
            "width" => 20
        ) ,
        array(
            "title" => '城市',
            "field" => "city",//和数组的key对应
            "width" => 20
        ) ,
        array(
            "title" => '区县',
            "field" => "district",//和数组的key对应
            "width" => 20
        ) ,
        array(
            "title" => '详细地址',
            "field" => "address",//和数组的key对应
            "width" => 40
        ) ,
        array(
            "title" => '联系人',
            "field" => "contacts",//和数组的key对应
            "width" => 20
        ) ,
        array(
            "title" => '联系电话',
            "field" => "tel",//和数组的key对应
            "width" => 20
        ) ,
        array(
            "title" => 'Email',
            "field" => "email",//和数组的key对应
            "width" => 20
        ) ,
        array(
            "title" => 'QQ号码',
            "field" => "qq",//和数组的key对应
            "width" => 20
        ) ,
        array(
            "title" => '微信',
            "field" => "weixin",//和数组的key对应
            "width" => 20
        ) ,
        array(
            "title" => '显示状态',
            "field" => "is_display",//和数组的key对应
            "width" => 12
        ) ,
        array(
            "title" => '审核状态',
            "field" => "is_check",//和数组的key对应
            "width" => 12
        ) ,
        array(
            "title" => '排序',
            "field" => "order_by",//和数组的key对应
            "width" => 12
        ) ,
        array(
            "title" => '修改时间',
            "field" => "updatetime",//和数组的key对应
            "width" => 20
        ) ,
        array(
            "title" => '添加时间',
            "field" => "createtime",//和数组的key对应
            "width" => 20
        )
    );
    $excel->export(
        $list,//导出的数据数组
        array(
            "title" => "本地商城（OTO）店铺数据-" . date("Y-m-d-H-i", time()) , //标题
            "columns" => $columns //数据列
        )
    );
}
include $this->template('shoper');
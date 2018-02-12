<?php
if(empty($do)){$do = 'display';}

//公司列表
if($do == 'display'){
    if($_W['isajax']){
        if(!check_data($_GPC['ids'])){
            message('请选择要删除的信息','','error');
        }
        $delete_status = pdo_query("DELETE FROM ".tablename('vapp_company')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['user_id']}' AND id IN (".implode(',',$_GPC['ids']).")");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $categories = pdo_fetchall("SELECT * FROM ".tablename('vapp_company_category')." WHERE uniacid='{$_W['uniacid']}' ORDER BY order_by DESC LIMIT 0,50",array(),'id');
    $list = pdo_fetchall("SELECT * FROM ".tablename('vapp_company')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['user_id']}' LIMIT 0,50");
}

//添加公司
if($do== 'post'){
    $categories = pdo_fetchall("SELECT * FROM ".tablename('vapp_company_category')." WHERE uniacid='{$_W['uniacid']}' ORDER BY order_by DESC LIMIT 0,50");
    if(empty($categories)){
        message('请先添加分类！',$this->createWebUrl('company',array('op'=>'post_category')),'error');
    }
    $id = floor(trim($_GPC['id']));
    if(!empty($id)) {
        $item = pdo_get('vapp_company',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    if($_W['isajax']){
        $data = array(
            'category_id' => intval(trim($_GPC['category_id'])),
            'title' => trim($_GPC['title']),
            'desc' => trim($_GPC['desc']),
            'logo' => trim($_GPC['logo']),
            'thumb' => trim($_GPC['thumb']),
            'business_license' => trim($_GPC['business_license']),
            'legal_person' => trim($_GPC['legal_person']),
            'contact' => trim('contact'),
            'id_card' => trim($_GPC['id_card']),
            'tel' => trim($_GPC['tel']),
            'mobile' => trim($_GPC['mobile']),
            'email' => trim($_GPC['email']),
            'wechat' => trim($_GPC['wechat']),
            'province' => trim($_GPC['area']['province']),
            'city' => trim($_GPC['area']['city']),
            'district' => trim($_GPC['area']['district']),
            'address' => trim($_GPC['address']),
            'lat' => trim($_GPC['map']['lat']),
            'lng' => trim($_GPC['map']['lng']),
            'is_display' => floor(trim($_GPC['is_display'])) == 1?1:0,
//            'is_recommend' => floor(trim($_GPC['is_recommend'])) == 1?1:0,
//            'order_by' => floor(trim($_GPC['order_by'])),
            'copyright' => trim($_GPC['copyright'])
        );
        $error = array(
            'category_id' => '请选择行业分类',
            'title' => '请输入公司名称',
            'desc' => '请输入公司简介',
            'logo' => '请上传公司logo',
            'thumb' => '请上传公司封面图',
            'province' => '请选择省份',
            'city' => '请选择城市',
            'district' => '请选择区县',
            'address' => '请输入详细地址'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
                break;
            }
        }
        if(empty($item)){ //添加
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $data['uid'] = $_W['user_id'];
            $tip = "添加";
            $flag = pdo_insert('vapp_company',$data);
        }else{ //修改
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = pdo_update('vapp_company',$data,array(
                'uniacid' => $_W['uniacid'],
                'id' => $id,
                'uid' => $_W['user_id']
            ));
        }
        if(!$flag){
            message("{$tip}失败！请重试！",'','error');
        }
        message("{$tip}成功！",referer(),'success');
    }
}

template('order/display');
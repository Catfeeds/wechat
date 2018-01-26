<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017-12-06
 * Time: 14:05
 */

//公司列表
if($op == 'display'){
    if($_W['isajax']){
        if(!check_data($_GPC['ids'])){
            message('请选择要删除的信息','','error');
        }
        $delete_status = pdo_query("DELETE FROM ".tablename('vapp_company')." WHERE uniacid='{$_W['uniacid']}' AND id IN (".implode(',',$_GPC['ids']).")");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $categories = pdo_fetchall("SELECT * FROM ".tablename('vapp_company_category')." WHERE uniacid='{$_W['uniacid']}' ORDER BY order_by DESC LIMIT 0,50",array(),'id');
    $page = getApartPageNo();
    $psize = 15;
    $pindex = ($page-1)*$psize;
    $where = "uniacid='{$_W['uniacid']}'";
    $keyword = trim($_GPC['keyword']);
    if(!empty($keyword)){
        $where .= " AND title LIKE '%{$keyword}%'";
    }
    $is_display = $_GPC['is_display'];
    if(check_data($is_display)){
        $where .= " AND is_display IN (".implode(',',$is_display).")";
    }
    $is_recommend = $_GPC['is_recommend'];
    if(check_data($is_recommend)){
        $where .= " AND is_recommend IN (".implode(',',$is_recommend).")";
    }
    $province = $_GPC['area']['province'];
    if(!empty($province)){
        $where .= " AND province='{$province}'";
    }
    $city = $_GPC['area']['city'];
    if(!empty($city)){
        $where .= " AND city='{$city}'";
    }
    $district = $_GPC['area']['district'];
    if(!empty($district)){
        $where .= " AND district='{$district}'";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('vapp_company')." WHERE {$where}");
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('vapp_company')." WHERE {$where}"),$page,$psize);
}


//添加公司
if($op == 'post'){
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
            'uid' => intval(trim($_GPC['uid'])),
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
            'is_recommend' => floor(trim($_GPC['is_recommend'])) == 1?1:0,
            'order_by' => floor(trim($_GPC['order_by'])),
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
            $tip = "添加";
            $flag = pdo_insert('vapp_company',$data);
        }else{ //修改
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = pdo_update('vapp_company',$data,array(
                'uniacid' => $_W['uniacid'],
                'id' => $id
            ));
        }
        if(!$flag){
            message("{$tip}失败！请重试！",'','error');
        }
        message("{$tip}成功！",referer(),'success');
    }
}

//分类列表
if($op == 'category'){
    if($_W['isajax']){
        if(!check_data($_GPC['ids'])){
            message('请选择要删除的分类','','error');
        }
        $delete_status = pdo_query("DELETE FROM ".tablename('vapp_company_category')." WHERE uniacid='{$_W['uniacid']}' AND id IN (".implode(',',$_GPC['ids']).")");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('vapp_company_category')." WHERE uniacid='{$_W['uniacid']}' ORDER BY order_by DESC LIMIT 0,50");
}


//提交分类
if($op == 'post_category'){
    $id = floor(trim($_GPC['id']));
    $category = array();
    if(!empty($id)){
        $category = pdo_get('vapp_company_category',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'is_display' => floor(trim($_GPC['is_display'])) == 1?1:0,
            'order_by' => floor(trim($_GPC['order_by']))
        );
        if(empty($data['title'])){
            message('请输入分类标题！','','error');
        }
        if(empty($category)){
            $is_exist_category = pdo_get('vapp_company_category',array(
                'uniacid' => $_W['uniacid'],
                'title' => $data['title']
            ));
            if(!empty($is_exist_category)){
                message('分类已经存在！','','error');
            }
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $flag = pdo_insert('vapp_company_category',$data);
            $tip = "添加";
        }else{
            $data['updatetime'] = TIMESTAMP;
            $flag = pdo_update('vapp_company_category',$data,array(
                'uniacid' => $_W['uniacid'],
                'id' => $id
            ));
            $tip = "修改";
        }
        if(!$flag){
            message("{$tip}失败！",'','error');
        }
        message("{$tip}成功！",referer(),'success');
    }
}
include $this->template('company');
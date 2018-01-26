<?php
if(empty($do)){$do = 'display';}

$company_where = "AND company_id IN (SELECT id FROM ".tablename('vapp_company')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['user_id']}')";
$a_company_where = "AND a.company_id IN (SELECT id FROM ".tablename('vapp_company')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['user_id']}')";
//商品列表
if($do == 'display'){
    if($_W['isajax']){
        $ids = $_GPC['ids'];
        if(!check_data($ids)){
            to_json(1,'请选择要删除的商品');
        }
        $ids = implode(',',$ids);
        $delete_status = pdo_query("DELETE FROM ".tablename('vapp_goods')." WHERE uniacid='{$_W['uniacid']}' {$company_where} AND id IN ($ids)");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $categoryList = pdo_fetchall("SELECT * FROM ".tablename('vapp_goods_category')." WHERE uniacid='{$_W['uniacid']}' LIMIT 0,100",array(),'id');
    $category = getCategoryTree($categoryList);
    $page = getApartPageNo();
    $psize = 15;
    $pindex = ($page-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $category_id = floor(trim($_GPC['category_id']));
    $is_check = $_GPC['is_check'];
    $is_display = $_GPC['is_display'];
    $where = "a.uniacid='{$_W['uniacid']}' {$a_company_where}";
    if(!empty($keywords)){
        $where .= " AND (a.title LIKE '%{$keywords}%')";
    }
    if(!empty($category_id)){
        $where .= " AND (a.category_id='{$category_id}' OR a.sub_category_id='{$category_id}')";
    }
    if(!empty($is_display) && is_array($is_display)){
        $where .= " AND a.is_display IN (".implode(',',$is_display).")";
    }
    if(!empty($is_check) && is_array($is_check)){
        $where .= " AND a.is_check IN (".implode(',',$is_check).")";
    }
    $where .= " ORDER BY a.order_by DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.title AS store_name FROM ".tablename('vapp_goods')." a LEFT JOIN ".tablename('vapp_company')." b ON a.company_id=b.id WHERE {$where}");
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('vapp_goods')." a LEFT JOIN ".tablename('vapp_company')." b ON a.company_id=b.id WHERE {$where}"),$page,$psize);
}

//添加商品
if($do == 'post'){
    $id = floor(trim($_GPC['id']));
    $item = pdo_fetch("SELECT * FROM ".tablename('vapp_goods')." WHERE uniacid='{$_W['uniacid']}' {$company_where} AND id='{$id}'");
    if(!empty($item) && is_array($item)){
        if(!empty($item['attr_list'])){
            $item['attr_list'] = iunserializer($item['attr_list']);
        }
        if(!empty($item['spec_list'])){
            $item['spec_list'] = iunserializer($item['spec_list']);
        }
        if(!empty($item['sku_list'])){
            $item['sku_list'] = iunserializer($item['sku_list']);
        }
        if(!empty($item['thumbs'])){
            $item['thumbs'] = iunserializer($item['thumbs']);
        }
    }
    $companyList = pdo_fetchall("SELECT * FROM ".tablename('vapp_company')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['user_id']}' ORDER BY order_by DESC LIMIT 0,20",array(),'id');
    $categoryList = pdo_fetchall("SELECT * FROM ".tablename('vapp_goods_category')." WHERE uniacid='{$_W['uniacid']}' LIMIT 0,100",array(),'id');
    $category = getCategoryTree($categoryList);
    if($_W['isajax']){
        if(!empty($_GPC['spec'][1]['value'])){
            $_GPC['spec'][1]['value'] = explode("\n",$_GPC['spec'][1]['value']);
        }
        if(!empty($_GPC['spec'][2]['value'])){
            $_GPC['spec'][2]['value'] = explode("\n",$_GPC['spec'][2]['value']);
        }
        $data = array(
            'title' => trim($_GPC['title']),
            'company_id' => floor(trim($_GPC['company_id'])),
            'category_id' => floor(trim($_GPC['category_id'])),
            'type' => floor(trim($_GPC['type'])) == GOODS_TYPE_ACTUAL?GOODS_TYPE_ACTUAL:GOODS_TYPE_VIRTUAL,
            'product_code' => trim($_GPC['product_code']),
            'desc' => $_GPC['desc'],
            'thumb'=>trim($_GPC['thumb']),
            'thumbs' => empty($_GPC['thumbs'])?'':iserializer($_GPC['thumbs']),
            'cost_price' => doubleval(trim($_GPC['sale_price'])),
            'market_price' => doubleval(trim($_GPC['sale_price'])),
            'sale_price' => doubleval(trim($_GPC['sale_price'])),
//            'give_credit1' => trim($_GPC['give_credit1']),
//            'max_buy_num' => floor(trim($_GPC['max_buy_num'])),
//            'user_max_buy_num' => floor(trim($_GPC['user_max_buy_num'])),
//            'is_open_limit_time_buy' => floor(trim($_GPC['is_open_limit_time_buy'])) == OPEN_STATUS?OPEN_STATUS:CLOSE_STATUS,
//            'limit_time_price' => doubleval(trim($_GPC['limit_time_price'])),
//            'limit_time_buy_start' => $_GPC['limit_buy_time']['start'],
//            'limit_time_buy_end' => $_GPC['limit_buy_time']['end'],
            'unit' => trim($_GPC['unit']),
            'weight' => doubleval(trim($_GPC['weight'])),
            'total' => doubleval(trim($_GPC['total'])),
            'is_display' => floor(trim($_GPC['is_display'])) == DISPLAY_YES?DISPLAY_YES:DISPLAY_NO,
            'is_check' => 1,
            'is_open_spec' => floor(trim($_GPC['is_open_spec'])) == OPEN_STATUS?OPEN_STATUS:CLOSE_STATUS,
            'attr_list' => empty($_GPC['attr'])?'':iserializer($_GPC['attr']),
            'spec_list' => empty($_GPC['spec'])?'':iserializer($_GPC['spec']),
            'sku_list' => empty($_GPC['sku'])?'':iserializer($_GPC['sku']),
            'detail' => $_GPC['detail']
        );
        $error = array(
            'title' => '请输入商品名称',
            'company_id' => '请选择公司',
            'category_id' => '请选择分类',
            'thumb'=>'请上传商品图片',
            'sale_price' => '请输入商品售价/现价',
            'unit' => '请输入商品的单位',
            'total' => '请输入商品的库存',
            'detail' => '请输入商品详情内容'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
                break;
            }
        }
        if(empty($companyList[$data['company_id']])){
            message('选择的公司不存在','','error');
        }
        if(check_data($item)){
            $tip = "修改";
            $data['updatetime'] = TIMESTAMP;
            $flag = pdo_update('vapp_goods',$data,array(
                'uniacid' => $_W['uniacid'],
                'id' => $id
            ));
        }else{
            $tip = "添加";
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $flag = pdo_insert('vapp_goods',$data);
        }

        if(!$flag){
            message("修改失败",'','error');
        }
        message("修改成功",referer(),'success');
    }
}

template('goods/display');
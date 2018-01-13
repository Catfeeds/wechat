<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/15
 * Time: 10:58
 */
if($op == 'display'){
    if($_W['isajax']){
        $ids = $_GPC['ids'];
        if(!check_data($ids)){
            to_json(1,'请选择要删除的商品');
        }
        $ids = implode(',',$ids);
        $delete_status = pdo_query("DELETE FROM ".tablename('vapp_goods')." WHERE uniacid='{$_W['uniacid']}' AND id IN ($ids)");
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
    $where = "a.uniacid='{$_W['uniacid']}'";
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

if($op == 'post'){
    $id = floor(trim($_GPC['id']));
    $item = pdo_get('vapp_goods',array(
        'uniacid'=>$_W['uniacid'],
        'is_display'=>DISPLAY_YES,
        'is_check'=>CHECK_PASS,
        'id'=>$id
    ));
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
    $companyList = pdo_fetchall("SELECT * FROM ".tablename('vapp_company')." WHERE uniacid='{$_W['uniacid']}' ORDER BY order_by DESC LIMIT 0,20");
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
            'give_credit1' => trim($_GPC['give_credit1']),
            'max_buy_num' => floor(trim($_GPC['max_buy_num'])),
            'user_max_buy_num' => floor(trim($_GPC['user_max_buy_num'])),
            'is_open_limit_time_buy' => floor(trim($_GPC['is_open_limit_time_buy'])) == OPEN_STATUS?OPEN_STATUS:CLOSE_STATUS,
            'limit_time_price' => doubleval(trim($_GPC['limit_time_price'])),
            'limit_time_buy_start' => $_GPC['limit_buy_time']['start'],
            'limit_time_buy_end' => $_GPC['limit_buy_time']['end'],
            'unit' => trim($_GPC['unit']),
            'weight' => doubleval(trim($_GPC['weight'])),
            'total' => doubleval(trim($_GPC['total'])),
            'is_display' => floor(trim($_GPC['is_display'])) == DISPLAY_YES?DISPLAY_YES:DISPLAY_NO,
            'is_check' => floor(trim($_GPC['is_check'])) == CHECK_PASS?CHECK_PASS:CHECK_NOT_PASS,
            'is_recommend' => floor(trim($_GPC['is_recommend'])) == RECOMMEND_YES?RECOMMEND_YES:RECOMMEND_NO,
            'is_open_spec' => floor(trim($_GPC['is_open_spec'])) == OPEN_STATUS?OPEN_STATUS:CLOSE_STATUS,
            'attr_list' => empty($_GPC['attr'])?'':iserializer($_GPC['attr']),
            'spec_list' => empty($_GPC['spec'])?'':iserializer($_GPC['spec']),
            'sku_list' => empty($_GPC['sku'])?'':iserializer($_GPC['sku']),
            'detail' => $_GPC['detail'],
            'order_by' => floor(trim($_GPC['order_by'])),
            'share_title' => trim($_GPC['share_title']),
            'share_desc' => $_GPC['share_desc'],
            'share_image' => trim($_GPC['share_image']),
            'is_free_post' => floor(trim($_GPC['is_free_post'])) == POST_FREE_YES?POST_FREE_YES:POST_FREE_NO,
            'postage_type' => floor(trim($_GPC['postage_type'])) == POSTAGE_TYPE_TEMPLATE?POSTAGE_TYPE_TEMPLATE:POSTAGE_TYPE_MONEY,
            'postage_money' => doubleval(trim($_GPC['postage_money'])),
            'postage_id' => floor(trim($_GPC['postage_id']))
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
        if($data['is_open_limit_time_buy'] == OPEN_STATUS){
             if(empty($data['limit_time_price'])){
                 message('请输入限时购价格','','error');
             }
            if($data['limit_time_buy_start'] > $data['limit_time_buy_end']){
                message('限时购的结束时间应大于开始时间','','error');
            }
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

if($op == 'category'){
    if($_W['isajax']){
        if(!check_data($_GPC['ids'])){
            message('请选择要删除的分类','','error');
        }
        $ids = implode(',',$_GPC['ids']);
        $delete_status = pdo_query("DELETE FROM ".tablename('vapp_goods_category')." WHERE uniacid='{$_W['uniacid']}' AND (id IN ({$ids}) OR parent_id IN ({$ids}))");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
   $list = getCategoryTree(pdo_fetchall("SELECT * FROM ".tablename('vapp_goods_category')." WHERE uniacid='{$_W['uniacid']}' LIMIT 0,100",array(),'id'));
}

if($op == 'post_category'){
    $s_category_id = floor(trim($_GPC['s_category_id']));
    $where = "uniacid='{$_W['uniacid']}'";
    $where .= " AND is_display=".DISPLAY_YES;
    $where2 = $where." AND parent_id=0";
    $where .= " AND (parent_id=0 OR parent_id IN (SELECT id FROM ".tablename('vapp_goods_category')." WHERE {$where2}))";
    $where .= " ORDER BY order_by DESC";
    $category_list = pdo_fetchall("SELECT * FROM ".tablename('vapp_goods_category')." WHERE {$where}");
    $parent_category_list = getCategoryTree($category_list);
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = pdo_get('vapp_goods_category',array('uniacid'=>$_W['uniacid'],'id'=>$id));
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'parent_id' => floor(trim($_GPC['parent_id'])),
            'is_display' => floor(trim($_GPC['is_display'])) == DISPLAY_YES?DISPLAY_YES:DISPLAY_NO,
            'order_by' => floor(trim($_GPC['order_by']))
        );
        if(empty($data['title'])){
            message('请输入分类标题','','error');
        }
        if($data['parent_id'] != 0){
            $parent_category = pdo_get('vapp_goods_category',array('uniacid'=>$_W['uniacid'],'id'=>$data['parent_id']));
            if(empty($parent_category)){
                message('父级分类不存在','','error');
            }
        }
        if(empty($item)){
            //插入
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $flag = pdo_insert('vapp_goods_category',$data);
            $tip = "添加";
        }else{
            //修改
            if($item['parent_id'] == 0 && $data['parent_id'] != 0){
                pdo_delete('vapp_goods_category',array('uniacid'=>$_W['uniacid'],'parent_id'=>$item['id']));
            }
            $data['updatetime'] = TIMESTAMP;
            $flag = pdo_update('vapp_goods_category',$data,array('uniacid'=>$_W['uniacid'],'id'=>$id));
            $tip = "修改";
        }
        if(!$flag){
            message($tip.'失败','','error');
        }
        message($tip.'成功',referer(),'success');
    }
}
include $this->template('goods');
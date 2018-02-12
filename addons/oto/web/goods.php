<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/15
 * Time: 10:58
 */
if($op == 'display'){
    if($_W['isajax']){
        $order_by = $_GPC['order_by'];
        if(trim($_GPC['ac']) == 'update_no'){
            if(!empty($_GPC['order_by_ids']) && is_array($_GPC['order_by_ids'])){
                pdo_begin();
                foreach($_GPC['order_by_ids'] as $k => $id){
                    $status = OtoModel::updateGoodsInfoById($id,array(
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
    $categoryList = OtoModel::getGoodsCategoryList(CATEGORY_SHOW_ALL,'',0,0,15,EXPORT_YES);
    $category = getCategoryTree($categoryList);
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $category_id = floor(trim($_GPC['category_id']));
    $is_check = $_GPC['is_check'];
    $is_display = $_GPC['is_display'];
    $list = OtoModel::getGoodsList($keyword,$category_id,$is_display,$is_check,$pindex,$psize,EXPORT_NO);
    $pager = pagination(OtoModel::getGoodsCount($keyword,$category_id,$is_display,$is_check),floor(trim($_GPC['page'])),$psize);
}elseif($op == 'post_goods'){
    if(trim($_GPC['ac']) == 'delete'){
        $delete_status = OtoModel::deleteGoodsByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $id = floor(trim($_GPC['id']));
    if(empty($id)){
        message('商品id不能存在','','error');
    }
    $item = OtoModel::getGoodsInfoById($id);
    if(empty($item)){
        message('商品信息不存在','','error');
    }
    $categoryList = OtoModel::getGoodsCategoryList(CATEGORY_SHOW_ALL,'',0,0,15,EXPORT_YES);
    $category = getCategoryTree($categoryList);
    $store_categoryList = OtoModel::getStoreGoodsCategoryListByStoreId($item['store_id']);
    $store_category = getCategoryTree($store_categoryList);
    if($_W['isajax']){
        if(!empty($_GPC['spec'][1]['value'])){
            $_GPC['spec'][1]['value'] = explode("\n",$_GPC['spec'][1]['value']);
        }
        if(!empty($_GPC['spec'][2]['value'])){
            $_GPC['spec'][2]['value'] = explode("\n",$_GPC['spec'][2]['value']);
        }
        $data = array(
            'title' => trim($_GPC['title']),
            'category_id' => floor(trim($_GPC['category_id'])),
            'store_category_id' => floor(trim($_GPC['store_category_id'])),
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
            'is_platform_recommend' => floor(trim($_GPC['is_platform_recommend'])) == RECOMMEND_YES?RECOMMEND_YES:RECOMMEND_NO,
            'is_open_spec' => floor(trim($_GPC['is_open_spec'])) == OPEN_STATUS?OPEN_STATUS:CLOSE_STATUS,
            'attr_list' => empty($_GPC['attr'])?'':iserializer($_GPC['attr']),
            'spec_list' => empty($_GPC['spec'])?'':iserializer($_GPC['spec']),
            'sku_list' => empty($_GPC['sku'])?'':iserializer($_GPC['sku']),
            'detail' => $_GPC['detail'],
            'platform_order_by' => floor(trim($_GPC['platform_order_by'])),
            'share_title' => trim($_GPC['share_title']),
            'share_desc' => $_GPC['share_desc'],
            'share_image' => trim($_GPC['share_image']),
             'updatetime' => TIMESTAMP,
            'store_type' => STORE_TYPE_OTO,
            'is_recommend' =>RECOMMEND_YES,
            'is_free_post' => floor(trim($_GPC['is_free_post'])) == POST_FREE_YES?POST_FREE_YES:POST_FREE_NO,
            'postage_type' => floor(trim($_GPC['postage_type'])) == POSTAGE_TYPE_TEMPLATE?POSTAGE_TYPE_TEMPLATE:POSTAGE_TYPE_MONEY,
            'postage_money' => doubleval(trim($_GPC['postage_money'])),
            'postage_id' => floor(trim($_GPC['postage_id'])),
            'order_by' => floor(trim($_GPC['order_by']))
        );
        $error = array(
            'title' => '请输入商品名称',
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
        $flag = OtoModel::updateGoodsInfoById($id,$data);
        if(!$flag){
            message("修改失败",'','error');
        }
        message("修改成功",referer(),'success');
    }
}elseif($op == 'category'){
   $list = getCategoryTree(OtoModel::getGoodsCategoryList(CATEGORY_SHOW_ALL,'',0,0,0,EXPORT_YES));
}elseif($op == 'post_category'){
    $s_category_id = floor(trim($_GPC['s_category_id']));
    if(trim($_GPC['ac']) == 'delete'){
        $delete_status = OtoModel::deleteGoodsCategoryInfoByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $parent_category_list = getCategoryTree(OtoModel::getMaxTwoLevelCategory());
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = OtoModel::getGoodsCategoryInfoById($id);
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'parent_id' => floor(trim($_GPC['parent_id'])),
            'is_display' => floor(trim($_GPC['is_display'])) == DISPLAY_YES?DISPLAY_YES:DISPLAY_NO,
            'thumb' => trim($_GPC['thumb']),
            'store_type' => STORE_TYPE_OTO,
            'order_by' => floor(trim($_GPC['order_by']))
        );
        if(empty($data['title'])){
            message('请输入分类标题','','error');
        }
        if($data['parent_id'] != 0){
            $parent_category = OtoModel::getGoodsCategoryInfoById($data['parent_id']);
            if(empty($parent_category)){
                message('父级分类不存在','','error');
            }
        }
        if(empty($item)){
            //插入
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $flag = OtoModel::insertGoodsCategory($data);
            $tip = "添加";
        }else{
            //修改
            if($item['parent_id'] == 0 && $data['parent_id'] != 0){
                OtoModel::deleteGoodsCategoryInfoByParentId($item['id']);
            }
            $data['updatetime'] = TIMESTAMP;
            $flag = OtoModel::updateGoodsCategoryInfoById($id,$data);
            $tip = "修改";
        }
        if(!$flag){
            message($tip.'失败','','error');
        }
        message($tip.'成功',$this->createWebUrl('goods',array('op'=>'post_category','s_category_id'=>$data['parent_id'])),'success');
    }
}
include $this->template('goods');
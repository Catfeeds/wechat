<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/3/16
 * Time: 14:58
 */
load()->model('store');
$do = in_array(trim($_GPC['do']),array('display','post'))?trim($_GPC['do']):'display';
if($do == 'display'){
    //商家列表页
    $postageTemplateList = StoreModel::getStoreDeliverList(0,20,EXPORT_YES);
    $categoryList = StoreModel::getStoreGoodsCategoryList(CATEGORY_SHOW_ALL,'',0,0,15,EXPORT_YES);
    $category = getCategoryTree($categoryList);
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $category_id = floor(trim($_GPC['category_id']));
    $display_status = $_GPC['display_status'];
    $list = StoreModel::getStoreGoodsList($keyword,$category_id,$display_status,$pindex,$psize,EXPORT_NO);
    $pager = pagination(StoreModel::getStoreGoodsCount($keyword,$category_id,$display_status),floor(trim($_GPC['page'])),$psize);
}elseif($do == 'post'){
    if(trim($_GPC['ac']) == 'delete'){
        $delete_status = StoreModel::deleteStoreGoodsInfoByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = StoreModel::getStoreGoodsInfoById($id);
    }
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
            'cost_price' => doubleval(trim($_GPC['cost_price'])),
            'market_price' => doubleval(trim($_GPC['market_price'])),
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
            'is_check' => CHECK_PASS,
            'is_platform_recommend' => RECOMMEND_YES,
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
            'store_type' => $_W['store_type'],
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
            'sale_price' => '请输入商品售价',
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
        if(empty($item)){
            $data['uniacid'] = $_W['uniacid'];
            $data['store_id'] = $_W['store_id'];
            $data['createtime'] = TIMESTAMP;
            $tip = "添加";
            $flag = StoreModel::insertStoreGoodsInfo($data);
        }else{
            $tip  = "修改";
            $data['updatetime'] = TIMESTAMP;
            $flag = StoreModel::updateStoreGoodsInfo($id,$data);
        }
        if(!$flag){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
    $category = getCategoryTree(StoreModel::getPlatformGoodsCategory());
    $store_category = getCategoryTree(StoreModel::getStoreGoodsCategoryList());
}
template('goods/display');
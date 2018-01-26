<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/7
 * Time: 18:05
 */
if($op == 'display'){
    $shop_setting = OtoModel::getPlatformIndexSetting();
    $distance_range = 0;
    if(isset($shop_setting['setting']['distance_range'])){
        if($shop_setting['setting']['distance_range'] > 0){
            $distance_range = $shop_setting['setting']['distance_range'];
        }
    }
    $page = 1;
    if($_W['isajax']){
        $page = max(1,floor(trim($_GPC['page'])));
    }
    $category_id = floor(trim($_GPC['category_id']));
    $search_type = floor(trim($_GPC['search_type'])) == SEARCH_TYPE_STORE?SEARCH_TYPE_STORE:SEARCH_TYPE_GOODS;
    $sort = floor(trim($_GPC['sort']));
    $keyword = trim($_GPC['keyword']);
    $psize = 25;
    $pindex = ($page-1)*$psize;
    if($search_type == SEARCH_TYPE_STORE){
        $tip = "店铺";
        //搜索同城店铺
        $list = OtoModel::getLocationCityPlatformStoreList($category_id,$keyword,$sort,$pindex,$psize,$distance_range);
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['logo'] = tomedia($v['logo']);
                $v['distance'] = getDistanceByLocations($v['lat'],$v['lng'],$_W['location']['lat'],$_W['location']['lng']);
                $v['distance'] = $v['distance'] >= 1000?(round(floatval($v['distance']/1000),1)).'km':$v['distance'].'m';
            }
        }
    }else{
        $tip = "商品";
        $store_id = floor(trim($_GPC['store_id']));
        $s_cid = floor(trim($_GPC['s_cid']));
        $s_sub_cid = floor(trim($_GPC['s_sub_cid']));
        $list = OtoModel::getLocationCityPlatformGoodsList($keyword,$store_id,$sort,$category_id,$s_cid,$s_sub_cid,$pindex,$psize,$distance_range);
    }
    if($_W['isajax']){
        if(!empty($list) && is_array($list)){
            message($list,'','success');
        }
        message('没有更多'.$tip,'','error');
    }

}
include $this->template('search');
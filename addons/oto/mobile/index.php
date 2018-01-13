<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/15
 * Time: 10:57
 */
load()->func('check');
$categoryList = OtoModel::getStoreCategoryList('',DISPLAY_YES,0,15,EXPORT_YES);
$setting = OtoModel::getPlatformIndexSetting();
if($op == 'display') {
    $_share['link'] = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&do=index&parent_uid={$_W['member']['uid']}&m=oto";
    if(!empty($_GPC['parent_uid'])){
        $parent_uid = floor(trim($_GPC['parent_uid']));
        updateRelation($_W['member']['uid'],$parent_uid);
    }
    if($_GPC['login_type'] == 'free' && empty($_W['member']['uid'])){
        load()->model('mc');
        $free_user = pdo_get('mc_members',array('uniacid'=>$_W['uniacid'],'mobile'=>'18833722273'));
        _mc_login($free_user);
    }
    load()->classs('point');
    $tencent_js_key = (new Point())->getTencentJsKey();
    $slides = OtoModel::getServicerSlideList();
    if(empty($slides) || !is_array($slides)){
        $slides = OtoModel::getPlatformSlideList();
    }
    $category = array();
    if(!empty($categoryList) && is_array($categoryList)){
        $key = 0;
        $i = 1;
        foreach($categoryList as $k => $v){
            $category[$key][] = $v;
            if($i%10 == 0){
                $key++;
            }
            $i++;
        }
    }
    $store_list = OtoModel::getNearbyStoreList(0,20,$setting['setting']['distance_range']);
    if(check_data($store_list)){
        foreach($store_list as $k1 => &$v1){
            $v1['logo'] = tomedia($v1['logo']);
            $v1['link'] = $this->createMobileUrl('store',array('id'=>$v1['id']));
            $v1['category'] = $categoryList[$v1['category_id']]['title'];
        }
    }
    $credit_store_list = OtoModel::getNearbyCreditStoreList(0,20,$setting['setting']['distance_range']);
    if(check_data($credit_store_list)){
        foreach($credit_store_list as $k2 => &$v2){
            $v2['logo'] = tomedia($v2['logo']);
            $v2['link'] = $this->createMobileUrl('store',array('id'=>$v2['id']));
            $v2['category'] = $categoryList[$v2['category_id']]['title'];
        }
    }
    $old_goods_list = OtoModel::getOldGoodsList('',SEARCH_CITY,0,20);
    if(check_data($old_goods_list)){
        foreach($old_goods_list as $k3 => &$v3){
            $v3['thumb'] = tomedia($v3['thumb']);
            $v3['link'] = $this->createMobileUrl('old',array('id'=>$v3['id']));
        }
    }
    $news_list = pdo_fetchall("SELECT id,title FROM ".tablename('sj_news_list')." WHERE uniacid='10' ORDER BY id DESC LIMIT 0,100");
    if(check_data($news_list)){
        foreach($news_list as $k => &$v){
            $v['href'] = "{$_W['siteroot']}app/index.php?i=11&c=entry&id={$v['id']}&do=detail&m=sj_news";
        }
    }
    $sj_news_index = "{$_W['siteroot']}app/index.php?i=11&c=entry&do=index&m=sj_news";
}elseif($op == 'ajax_store'){
    $page = (max(1,floor(trim($_GPC['page']))));
    $psize = 20;
    $pindex = ($page - 1)*$psize;
    $list = OtoModel::getNearbyStoreList($pindex,$psize,$setting['setting']['distance_range']);
    if(check_data($list)){
        foreach($list as $k => &$v){
            $v['logo'] = tomedia($v['logo']);
            $v['link'] = $this->createMobileUrl('store',array('id'=>$v['id']));
            $v['category'] = $categoryList[$v['category_id']]['title'];
        }
        message($list,'','success');
    }
    message('没有更多店铺','','error');
}elseif($op == 'ajax_credit_store'){
    $page = (max(1,floor(trim($_GPC['page']))));
    $psize = 20;
    $pindex = ($page - 1)*$psize;
    $list = OtoModel::getNearbyCreditStoreList($pindex,$psize,$setting['setting']['distance_range']);
    if(check_data($list)){
        foreach($list as $k => &$v){
            $v['logo'] = tomedia($v['logo']);
            $v['link'] = $this->createMobileUrl('store',array('id'=>$v['id']));
            $v['category'] = $categoryList[$v['category_id']]['title'];
        }
        message($list,'','success');
    }
    message('没有更多店铺','','error');
}elseif($op == 'ajax_credit_goods'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page - 1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $search_type = floor(trim($_GPC['search_type'])) == SEARCH_COUNTRY?SEARCH_COUNTRY:SEARCH_CITY;
    $list = OtoModel::getCreditGoodsList($keyword,$search_type,$pindex,$psize);
    if(check_data($list)){
        foreach($list as $k => &$v){
            $v['thumb'] = tomedia($v['thumb']);
            $v['link'] = $this->createMobileUrl('detail',array('id'=>$v['id']));
        }
        message($list,'','success');
    }
    message('没有更多积分商品','','error');
}elseif($op == 'ajax_old_goods'){
    $page = (max(1,floor(trim($_GPC['page']))));
    $psize = 20;
    $pindex = ($page - 1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $search_type = floor(trim($_GPC['search_type'])) == SEARCH_COUNTRY?SEARCH_COUNTRY:SEARCH_CITY;
    $list = OtoModel::getOldGoodsList($keyword,$search_type,$pindex,$psize);
    if(check_data($list)){
        foreach($list as $k => &$v){
            $v['thumb'] = tomedia($v['thumb']);
            $v['link'] = $this->createMobileUrl('old',array('id'=>$v['id']));
        }
        message($list,'','success');
    }
    message('没有更多二手物品','','error');
}
include $this->template('index');
<?php
$id = floor(trim($_GPC['id']));
$store_info = OtoModel::getStoreInfoById($id);
if(empty($store_info) || !is_array($store_info)){
    message('店铺信息不存在','','error');
}
$auth = payOfflineAuthEncode($id,$_W['uniacid']);
$gps_url = get_gps_keyword($store_info['address']);
$config = OtoModel::getStoreShopConfigByStoreId($id);
//是否收藏
$is_collect = '';
if(!empty($_W['member']['uid'])){
    $is_collect = OtoModel::getMemberCollectInfo($id,COLLECT_TYPE_STORE,STORE_TYPE_OTO);
}
if($op == 'display'){
    $_share['link'] = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&id={$id}&do=store&parent_uid={$_W['member']['uid']}&m=oto";
    if(!empty($_GPC['parent_uid'])){
        $parent_uid = floor(trim($_GPC['parent_uid']));
        updateRelation($_W['member']['uid'],$parent_uid);
    }
    //累计浏览量
    pdo_update('store_list',array(
        'look_count' => $store_info['look_count']+1
    ),array(
        'uniacid'=>$_W['uniacid'],
        'id'=>$id
    ));
    $page = floor(trim($_GPC['page']));
    $psize = 20;
    $pindex = (max(1,$page)-1)*$psize;
    $goods_list = OtoModel::getStoreGoodsList($id,$pindex,$psize);
    if($_W['isajax']){
        if(!empty($goods_list) && is_array($goods_list)){
            foreach($goods_list as $k => &$v){
                $v['link'] = $this->createMobileUrl('detail',array('id'=>$v['id']));
                $v['thumb'] = tomedia($v['thumb']);
            }
            message($goods_list,'','success');
        }
        message('没有更多商品','','error');
    }
    $categories = getCategoryTreeArray(pdo_fetchall("SELECT * FROM ".tablename('store_goods_category')." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$id}'",array(),'id'));
}
if($op == 'collect'){
    $store_id = floor(trim($_GPC['id']));
    if($_W['isajax']){
        //检查是否登录
        if(empty($_W['member']['uid'])){
            message('请先登录',url('auth/login',array(
                'forward' => base64_encode("i={$_W['uniacid']}&c=entry&id={$store_id}&op=display&do=store&m=oto")
            )),'error');
        }
        $collect = OtoModel::getMemberCollectInfo($store_id,COLLECT_TYPE_STORE,STORE_TYPE_OTO);
        if(!empty($collect) && is_array($collect)){
            $tip = "取消";
            $status = OtoModel::deleteMemberCollectInfo($store_id,COLLECT_TYPE_STORE,STORE_TYPE_OTO);
        }else{
            $tip = "收藏";
            $status = OtoModel::insertMemberCollectInfo(array(
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['member']['uid'],
                'flag_id' => $store_id,
                'type' => COLLECT_TYPE_STORE,
                'store_type' => STORE_TYPE_OTO,
                'createtime' => TIMESTAMP
            ));
        }
        if(!$status){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",'','success');
    }
    message('请求方式错误 ','','error');
}
include $this->template('store');
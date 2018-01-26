<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/27
 * Time: 15:59
 */
if($op == 'display'){
    $id = floor(trim($_GPC['id']));
    $_share['link'] = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&id={$id}&do=detail&parent_uid={$_W['member']['uid']}&m=oto";
    if(!empty($_GPC['parent_uid'])){
        $parent_uid = floor(trim($_GPC['parent_uid']));
        updateRelation($_W['member']['uid'],$parent_uid);
    }
    if(empty($id)){
        message('商品ID不存在','','error');
    }
    $goods = OtoModel::getGoodsDetailById($id);
    if(empty($goods) || !is_array($goods)){
        message('商品信息不存在','','error');
    }
    //店铺支付验证
    $auth = payOfflineAuthEncode($goods['store_id'],$_W['uniacid']);

    //插入我的足迹
    if(!empty($_W['member']['uid'])){
        $foot_mark = OtoModel::getMemberFootMark($id);
        if(empty($foot_mark)){
            OtoModel::insertMemberFootMark(array(
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['member']['uid'],
                'goods_id' => $id,
                'store_id' => $goods['store_id'],
                'store_type' => $goods['store_type'],
                'createtime' => TIMESTAMP
            ));
        }
    }

    //是否收藏
    $is_collect = '';
    if(!empty($_W['member']['uid'])){
        $is_collect = OtoModel::getMemberCollectInfo($id,COLLECT_TYPE_GOODS,STORE_TYPE_OTO);
    }
    $goods['true_price'] = getGoodsTruePrice($goods);
    if(!empty($goods['sku_list']) && is_array($goods['sku_list'])){
        foreach($goods['sku_list'] as $sku_key => &$v){
            $v['sale_price'] = getGoodsTruePrice($goods,$sku_key);
        }
    }
    $goodsCount = OtoModel::getStoreGoodsCount($goods['store_id']);
    $goods['sku_list'] = json_encode($goods['sku_list']);
    $store_info = OtoModel::getStoreInfoById($goods['store_id']);
//    $store_recommend_goods = OtoModel::getStoreRecommendGoods($goods['store_id']);
}elseif($op == 'cart'){
    if($_W['isajax']){
        $goods_id = floor(trim($_GPC['goods_id']));
        //检查是否登录
        if(empty($_W['member']['uid'])){
            message('请先登录',url('auth/login',array(
                'forward' => base64_encode("i={$_W['uniacid']}&c=entry&id={$goods_id}&do=detail&m=oto")
            )),'error');
        }
        $member = pdo_fetch("SELECT * FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'");
        if(empty($member['city']) || empty($member['province']) ||empty($member['district'])){
            message('请先设置所在地',url('set/location/display'),'error');
        }
        $sku_key = trim($_GPC['sku_key']);
        $sku_desc = trim($_GPC['sku_desc']);
        $buy_num = floor(trim($_GPC['buy_num']));
        $already_buy_num = 0;
        //判断相同规格的产品是否已经存在购物车，如果存在数目相加
        $cart_info = OtoModel::getShoppingCartInfoBySkuGoodsId($goods_id,$sku_key,$sku_desc);
        if(!empty($cart_info) && is_array($cart_info)){
            $already_buy_num = $cart_info['buy_num'];
        }
        if($buy_num < 1){
            message('购买数量不能小于1','','error');
        }

        $goods_info = OtoModel::getGoodsInfoById($goods_id);
        if(empty($goods_info) || !is_array($goods_info)){
            message('商品不存在','','error');
        }
        $sale_price = $goods_info['sale_price'];
        //限时购价格
        if($goods_info['is_open_limit_time_buy'] == OPEN_STATUS){
            if($goods_info['limit_time_buy_start'] < TIMESTAMP && $goods_info['limit_time_buy_end'] > TIMESTAMP){
                $sale_price = $goods_info['limit_time_price'];
            }
        }

        if($goods_info['is_open_spec'] == OPEN_STATUS){
            if($sku_key == ''){
                message('请选择规格','','error');
            }

            if(!in_array($sku_key,array_keys($goods_info['sku_list']))){
                message('所选规格不存在','','error');
            }
            if($buy_num + $already_buy_num > $goods_info['sku_list'][$sku_key]['total']){
                message('购买数目不能超过库存量','','error');
            }
            if($goods_info['is_open_limit_time_buy'] == OPEN_STATUS){
                if($goods_info['limit_time_buy_start'] < TIMESTAMP && $goods_info['limit_time_buy_end'] > TIMESTAMP){
                    $sale_price = $goods_info[$sku_key]['limit_time_price'];
                }
            }
        }else{
            if($goods_info['total'] < $buy_num + $already_buy_num){
                message('购买数目不能超过库存量','','error');
            }
        }
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'store_id' => $goods_info['store_id'],
            'store_type' => STORE_TYPE_OTO,
            'goods_id' => $goods_id,
            'sku_key' => $sku_key,
            'sku_desc' => $sku_desc,
        );
        if(!empty($cart_info) && is_array($cart_info)){
            $data['updatetime'] = TIMESTAMP;
            $data['buy_num'] = $already_buy_num + $buy_num;
            $status = OtoModel::updateShoppingCartInfoBySkuGoodsId($data,$goods_id,$sku_key,$sku_desc);
        }else{
            $data['buy_num'] = $buy_num;
            $data['createtime'] = TIMESTAMP;
            $status = OtoModel::insertShoppingCart($data);
        }
        if(!$status){
            message('加入购物车失败','','error');
        }
        message('加入购物车成功','','success');
    }
    message('请求方式错误 ','','error');
}elseif($op == 'collect'){
    if($_W['isajax']){
        $goods_id = floor(trim($_GPC['id']));
        //检查是否登录
        if(empty($_W['member']['uid'])){
            message('请先登录',url('auth/login',array(
                'forward' => base64_encode("i={$_W['uniacid']}&c=entry&id={$goods_id}&do=detail&m=oto")
            )),'error');
        }
        $collect = OtoModel::getMemberCollectInfo($goods_id,COLLECT_TYPE_GOODS,STORE_TYPE_OTO);
        if(!empty($collect) && is_array($collect)){
            $tip = "取消收藏";
            $status = OtoModel::deleteMemberCollectInfo($goods_id,COLLECT_TYPE_GOODS,STORE_TYPE_OTO);
        }else{
            $tip = "收藏";
            $status = OtoModel::insertMemberCollectInfo(array(
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['member']['uid'],
                'flag_id' => $goods_id,
                'type' => COLLECT_TYPE_GOODS,
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
include $this->template('detail');
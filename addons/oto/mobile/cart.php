<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/20
 * Time: 19:36
 */
if($op == 'display'){
    $list = OtoModel::getShoppingCartList(50);
    $cartList = array();
    if(!empty($list) && is_array($list)){
        foreach($list as $k => $cart){
            $cartList[$cart['store_id']]['id'] = $cart['store_id'];
            $cartList[$cart['store_id']]['title'] = $cart['title'];
            $cart['sku_change'] = getGoodsSkuChange($cart,$cart['sku_desc'],$cart['sku_key']);
            $cart['sale_price'] = getGoodsTruePrice($cart,$cart['sku_key']);
            unset($cart['sku_list']);
            $cartList[$cart['store_id']]['carts'][] = $cart;
        }
    }
}elseif($op == 'delete'){
    if($_W['isajax']){
        $id = $_GPC['id'];
        if(!empty($id) && is_array($id)){
            $status  = OtoModel::deleteShoppingCartByIds($id);
            if(!$status){
                message('删除失败','','error');
            }
            message('删除成功',referer(),'success');
        }else{
            message('请选择要删除的商品','','error');
        }
    }
    message('请求方式错误','','error');
}elseif($op == 'update_buy_num'){
    if($_W['isajax']){
        $cart_id = floor(trim($_GPC['cart_id']));
        $buy_num = floor(trim($_GPC['buy_num']));
        $cart = OtoModel::getShoppingCartInfoById($cart_id);
        if(empty($cart)){
            message('购物车信息不存在','','error');
        }
        $goods_info = OtoModel::getGoodsInfoById($cart['goods_id']);
        if(empty($goods_info)){
            message('商品信息不存在','','error');
        }
        if($buy_num > getGoodsTrueTotal($goods_info,$cart['sku_key'])){
            message('商品数量不足','','error');
        }
        $data = array(
            'buy_num' => $buy_num,
            'updatetime' => TIMESTAMP
        );
        $status = OtoModel::updateShoppingCartInfoById($cart_id,$data);
        if(!$status){
            message('修改失败','','error');
        }
        message('修改成功','','success');
    }
    message('请求方式错误','','error');
}
include $this->template('cart');
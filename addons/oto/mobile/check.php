<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/3/29
 * Time: 13:06
 */
$member = pdo_fetch("SELECT * FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'");
if(empty($member['city']) || empty($member['province']) ||empty($member['district'])){
    message('请先设置所在地',url('set/location/display'),'error');
}
if($op == 'display'){
    $user_credit1 = pdo_fetchcolumn("SELECT credit1 FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'");
    load()->app('tpl');
    $address = OtoModel::getDefaultDeliverAddress();
    $orders = array();
    $ids = $_GPC['id'];
    $total_num = 0; //总数目
    $total_price = 0;//总价格
    $postage_price = 0;//总邮费
    if(!empty($ids) && is_array($ids)){
        $post_type = ORDER_PUSH_CART;
        $list = OtoModel::getGoodsInfoByCartIds($ids);
    }else{
        $post_type = ORDER_PUSH_NOW;
        $goods_id = floor(trim($_GPC['goods_id']));
        $sku_key = trim($_GPC['sku_key']);
        $sku_desc = trim($_GPC['sku_desc']);
        $buy_num = trim($_GPC['buy_num']);
        $info = OtoModel::getGoodsStoreInfoByGoodsId($goods_id);
        $list = array();
        if(!empty($info) && is_array($info)){
            $info['buy_num'] = $buy_num;
            $info['sku_key'] = $sku_key;
            $info['sale_price'] = getGoodsTruePrice($info,$sku_key);
            array_push($list,$info);
        }
    }
    if(!empty($list) && is_array($list)){
        foreach($list as $k1 => $v1){
            $orders[$v1['store_id']]['id'] = $v1['store_id'];
            $orders[$v1['store_id']]['title'] = $v1['store_title'];
            $orders[$v1['store_id']]['logo'] = $v1['store_logo'];
            if(!isset($orders[$v1['store_id']]['goodsList'])){
                $orders[$v1['store_id']]['goodsList'] = array();
            }
            $v1['sale_price'] = getGoodsTruePrice($v1,$v1['sku_key']);
            $v1['postage_price'] = getGoodsPostPrice($v1,$v1['sku_key'],$v1['buy_num']);
            array_push($orders[$v1['store_id']]['goodsList'],$v1);
            $total_num += $v1['buy_num'];
            $postage_price += $v1['postage_price'];
            $total_price += ($v1['buy_num']*$v1['sale_price']+$v1['postage_price']);
        }
    }
    if(empty($orders)){
        message('请选择需要下单的商品','','error');
    }
}elseif($op == 'post'){
    $push_type = floor(trim($_GPC['push_type']));
    $address_id = floor(trim($_GPC['address_id']));
    if(empty($address_id)){
        message('请选择收货地址','','error');
    }
    $words = $_GPC['words'];
    $address = OtoModel::getDeliverAddressById($address_id);
    if(empty($address) || !is_array($address)){
        message('请选择收货地址','','error');
    }
    $order_no = generateOrderSnByBuyTodayTradeCount();
    if($push_type == ORDER_PUSH_CART){ //购物车提交
        $cart_ids = $_GPC['id'];
        if(empty($cart_ids) || !is_array($cart_ids)){
            message('请选择需要下单的商品','','error');
        }
        $order_ids = array();//记录订单ID
        $pay_total_price = 0;//累计支付价格
        $err_num = 0;
        $err_message = '提交失败';
        //开启事务
        pdo_begin();
        foreach($cart_ids as $k => $id){
            $cart_info = OtoModel::getShoppingCartInfoById($id);
            if(empty($cart_info) || !is_array($cart_info)){
                $err_num++;
                $err_message = '购物车数据异常';
                break;
            }
            $goods_info = OtoModel::getGoodsInfoById($cart_info['goods_id']);
            if(empty($goods_info) || !is_array($goods_info)){
                $err_num++;
                $err_message = '商品信息不存在';
                break;
            }
            if(!empty($cart_info['sku_key']) && $goods_info['is_open_spec']){
                if(!in_array($cart_info['sku_key'],array_keys($goods_info['sku_list']))){
                    $err_num++;
                    $err_message = "商品：{$goods_info['title']},{$cart_info['sku_desc']}不存在";
                    break;
                }
                if($cart_info['sku_desc'] != $goods_info['sku_list'][$cart_info['sku_key']]['filed_1'].'-'.$goods_info['sku_list'][$cart_info['sku_key']]['filed_2']){
                    $err_num++;
                    $err_message = "商品：{$goods_info['title']}的规格发生改变，请重新购买";
                    break;
                }
            }
            //插入订单数据
            $pay_price = getGoodsTruePrice($goods_info,$cart_info['sku_key']);
            $postage_price = getGoodsPostPrice($goods_info,$cart_info['sku_key'],floor($cart_info['buy_num']));
            $cost_price = getGoodsTrueCostPrice($goods_info,$cart_info['sku_key']);
            $market_price = getGoodsTrueMarketPrice($goods_info,$cart_info['sku_key']);
            $order_data = array(
                'verify_code' => random(8,true),
                'order_no' => $order_no,
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['member']['uid'],
                'goods_id' => $goods_info['id'],
                'store_id' => $goods_info['store_id'],
                'store_type' => STORE_TYPE_OTO,
                'username' => $address['username'],
                'mobile' => $address['mobile'],
                'province' => $address['province'],
                'city' => $address['city'],
                'district' => $address['district'],
                'address' => $address['address'],
                'leave_words' => isset($words[$goods_info['store_id']])?$words[$goods_info['store_id']]:'',
                'buy_num' => floor($cart_info['buy_num']),
                'pay_price' => doubleval($pay_price),
                'pay_postage_fee' => doubleval($postage_price),
                'pay_total_price' => doubleval($pay_price*$cart_info['buy_num']+doubleval($postage_price)),
                'sku_key' => $cart_info['sku_key'],
                'sku_desc' => $cart_info['sku_desc'],
                'createtime' => TIMESTAMP
            );
            //订单分销金额
            $order_data['distribution_money'] = floatval($order_data['pay_total_price']-$order_data['pay_postage_fee']-$cost_price*$order_data['buy_num']);
            if($order_data['distribution_money'] < 0){
                $order_data['distribution_money'] = 0;
            }

            if($order_data['buy_num'] < 1){
                $err_num++;
                $err_message = "商品：{$goods_info['title']}的购买数目不能小于1";
                break;
            }
            if($order_data['pay_price'] <= 0){
                $err_num++;
                $err_message = "商品：{$goods_info['title']}的购买价格异常";
                break;
            }
            $insert_order_id = OtoModel::insertOrderInfoReturnInsertId($order_data);
            if($insert_order_id == false){
                $err_num++;
                $err_message = "商品：{$goods_info['title']}提交失败";
                break;
            }

            //插入订单商品信息
            $order_goods_data = array(
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['member']['uid'],
                'order_id' => $insert_order_id,
                'store_id' => $goods_info['store_id'],
                'store_type' => STORE_TYPE_OTO,
                'goods_id' => $goods_info['id'],
                'goods_name'=>$goods_info['title'],
                'thumb' => $goods_info['thumb'],
                'cost_price' => $cost_price,
                'market_price' => $market_price,
                'sale_price' => $pay_price,
                'unit' => $goods_info['unit'],
                'weight' => $goods_info['weight'],
                'sku_desc' => $cart_info['sku_desc'],
                'give_credit1' => $goods_info['give_credit1'],
                'createtime' => TIMESTAMP
            );
            $insert_order_goods_status = OtoModel::insertOrderGoodsInfo($order_goods_data);
            if(!$insert_order_goods_status){
                $err_num++;
                $err_message = "商品：{$goods_info['title']}信息记录失败";
                break;
            }

            //成功记录订单的id,记录到pay_log中
            array_push($order_ids,$insert_order_id);
            $pay_total_price += $order_data['pay_total_price'];
        }
        if($err_num != 0){
            pdo_rollback();
            message($err_message,'','error');
        }

        //插入支付记录
        $pay_log_data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'order_ids' => implode(SPLIT_ORDER_IDS,$order_ids),
            'out_trade_no' => $order_no,
            'order_type' => ORDER_TYPE_OTO_GOODS,
            'pay_price' => $pay_total_price,
            'createtime' => TIMESTAMP
        );
        $insert_pay_log_id = OtoModel::insertOrderPayLogReturnInsertId($pay_log_data);
        if($insert_pay_log_id == false){
            pdo_rollback();
            message('支付信息，提交失败','','error');
        }

        //清空购物车
        $clean_cart_status = OtoModel::deleteShoppingCartByIds($cart_ids);
        if(!$clean_cart_status){
            pdo_rollback();
            message('购物车信息删除失败','','error');
        }

        //提交事务
        pdo_commit();
        message('提交成功，正在跳转',url('mc/pay/display',array('id'=>$insert_pay_log_id)),'success');
    }else{ //订单提交
        $goods_id = floor(trim($_GPC['goods_id']));
        $sku_key = trim($_GPC['sku_key']);
        $sku_desc = trim($_GPC['sku_desc']);
        $buy_num = floor(trim($_GPC['buy_num']));
        if(empty($goods_id)){
            message('请选择要下单的商品','','error');
        }
        $goods_info = OtoModel::getGoodsInfoById($goods_id);
        if(empty($goods_info) || !is_array($goods_info)){
            message('商品信息不存在','','error');
        }
        if(!empty($sku_key) && $goods_info['is_open_spec']){
            if(!in_array($sku_key,array_keys($goods_info['sku_list']))){
                message("商品：{$goods_info['title']},{$sku_desc}不存在",'','error');
            }
            if($sku_desc != $goods_info['sku_list'][$sku_key]['filed_1'].SPLIT_GOODS_SKU_DESC.$goods_info['sku_list'][$sku_key]['filed_2']){
                message("商品：{$goods_info['title']}的规格发生改变，请重新购买",'','error');
            }
        }
        //插入订单数据
        $pay_price = getGoodsTruePrice($goods_info,$sku_key);
        $postage_price = getGoodsPostPrice($goods_info,$sku_key,floor($buy_num));
        $cost_price = getGoodsTrueCostPrice($goods_info,$sku_key);
        $market_price = getGoodsTrueMarketPrice($goods_info,$sku_key);
        $order_data = array(
            'verify_code' => random(8,true),
            'order_no' => $order_no,
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'goods_id' => $goods_id,
            'store_id' => $goods_info['store_id'],
            'store_type' => STORE_TYPE_OTO,
            'username' => $address['username'],
            'mobile' => $address['mobile'],
            'province' => $address['province'],
            'city' => $address['city'],
            'district' => $address['district'],
            'address' => $address['address'],
            'leave_words' => isset($words[$goods_info['store_id']])?$words[$goods_info['store_id']]:'',
            'buy_num' => floor($buy_num),
            'pay_price' => doubleval($pay_price),
            'pay_postage_fee' => doubleval($postage_price),
            'pay_total_price' => doubleval($pay_price*$buy_num+$postage_price),
            'sku_key' => $sku_key,
            'sku_desc' => $sku_desc,
            'createtime' => TIMESTAMP
        );
        //订单分销金额
        $order_data['distribution_money'] = floatval($order_data['pay_total_price']-$order_data['pay_postage_fee']-$cost_price*$order_data['buy_num']);
        if($order_data['distribution_money'] < 0){
            $order_data['distribution_money'] = 0;
        }
        if($order_data['buy_num'] < 1){
            message("商品：{$goods_info['title']}的购买数目不能小于1",'','error');
        }
        if($order_data['pay_price'] <= 0){
            message("商品：{$goods_info['title']}的购买价格异常",'','error');
        }
        //开启事务
        pdo_begin();
        $insert_order_id = OtoModel::insertOrderInfoReturnInsertId($order_data);
        if($insert_order_id == false){
            pdo_rollback();
            message("商品：{$goods_info['title']}提交失败",'','error');
        }

        //插入订单商品信息
        $order_goods_data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'order_id' => $insert_order_id,
            'store_id' => $goods_info['store_id'],
            'store_type' => STORE_TYPE_OTO,
            'goods_id' => $goods_info['id'],
            'goods_name'=>$goods_info['title'],
            'thumb' => $goods_info['thumb'],
            'cost_price' => $cost_price,
            'market_price' => $market_price,
            'sale_price' => $pay_price,
            'unit' => $goods_info['unit'],
            'weight' => $goods_info['weight'],
            'sku_desc' => $sku_desc,
            'give_credit1' => $goods_info['give_credit1'],
            'createtime' => TIMESTAMP
        );
        $insert_order_goods_status = OtoModel::insertOrderGoodsInfo($order_goods_data);
        if(!$insert_order_goods_status){
            pdo_rollback();
            message("商品：{$goods_info['title']}信息记录失败",'','error');
        }

        //插入支付记录
        $pay_log_data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'order_ids' => $insert_order_id,
            'out_trade_no' => $order_no,
            'pay_price' => $order_data['pay_total_price'],
            'order_type' => ORDER_TYPE_OTO_GOODS,
            'createtime' => TIMESTAMP
        );
        $insert_pay_log_id = OtoModel::insertOrderPayLogReturnInsertId($pay_log_data);
        if($insert_pay_log_id == false){
            pdo_rollback();
            message('支付信息，提交失败','','error');
        }
        //提交事务
        pdo_commit();
        header("location:".url('mc/pay/display',array('id'=>$insert_pay_log_id)));
    }
}elseif($op == 'address'){
    if($_W['isajax']){
        $addressList = OtoModel::getDeliverAddressList(0,50);
        if(!empty($addressList) && is_array($addressList)){
            message($addressList,'返回地址列表','success');
        }
        message('没有相关地址，请点击添加地址','','error');
    }
    message('请求方式错误','','error');
}elseif($op == 'add_address'){
    if($_W['isajax']){
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'username' => trim($_GPC['username']),
            'mobile' => trim($_GPC['mobile']),
            'zipcode' => trim($_GPC['zipcode']),
            'province' => trim($_GPC['area']['province']),
            'city' => trim($_GPC['area']['city']),
            'district' => trim($_GPC['area']['district']),
            'address' => trim($_GPC['address']),
            'isdefault' => floor(trim($_GPC['isdefault'])) == IS_DEFAULT?IS_DEFAULT:NOT_DEFAULT,
            'createtime' => TIMESTAMP
        );
        $error = array(
            'username' => '请输入姓名',
            'mobile' => '请输入手机号',
            'province' => '请选择省份',
            'city' => '请选择城市',
            'district' => '请选择区县',
            'address' => '请填写详细地址'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        if($data['isdefault'] == IS_DEFAULT){
            pdo_update('mc_member_address',array('isdefault'=>NOT_DEFAULT),array('uniacid'=>$_W['uniacid'],'uid'=>$_W['member']['uid'],'isdefault'=>IS_DEFAULT));
        }
        $status = pdo_insert('mc_member_address',$data);
        if(!$status){
            message('添加失败','','error');
        }
        $data['id'] = pdo_insertid();
        message($data,'','success');
    }
    message('请求方式错误','','error');
}
include $this->template('check');

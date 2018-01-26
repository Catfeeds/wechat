<?php
/**
 * @param int $id
 * @return array|null
 * 根据支付记录ID获取支付信息
 */
load()->func('check');
class PayModel{
    private static $_db_pay_log = "pay_log";//支付日志表
    private static $_db_order_list = "order_list";//BBC、OTO订单列表
    private static $_db_order_offline = "order_offline";//OTO线下支付
    private static $_db_goods_list = "goods_list";//BBC、OTO商品表
    private static $_db_store_income = "store_income";//店铺收入表
    private static $_db_store_list = "store_list";//商家列表

    /**
     * @param $id
     * @return array|null
     * 根据日志ID 获取支付信息
     */
    public static function getPayLogInfoById($id){
        if(!check_id($id)){
            return null;
        }
        $info = pdo_get(self::$_db_pay_log,array(
            'id' => $id
        ));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param $out_trade_no
     * @return array|null
     * 根据支付日志订单号获取日志信息
     */
    public static function getPayLogInfoByOutTradeNo($out_trade_no){
        $info = pdo_get(self::$_db_pay_log,array(
            'out_trade_no' => $out_trade_no
        ));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param $id
     * @param array $data
     * @return bool
     * 修改支付日志信息
     */
    public static function updatePayLogInfoById($id,$data = array()){
        global $_W;
        if(!check_id($id) || empty($data) || !is_array($data)){
            return false;
        }
        $where = array(
            'id' => $id
        );
        $status = pdo_update(self::$_db_pay_log,$data,$where);
        return !$status?false:true;
    }

    /**
     * @param array $ids
     * @return bool
     * 批量修改订单信息
     */
    public static function otoGoodsOrder($ids = array(),$pay_method){
        global $_W;
        if(empty($ids) || !is_array($ids)){
            return false;
        }
        $status = pdo_query("UPDATE ".tablename(self::$_db_order_list)." SET `order_status`='".ORDER_STATUS_NOT_DELIVER."',`pay_status`='".PAY_YES."',`pay_method`='{$pay_method}',`updatetime`='".TIMESTAMP."' WHERE `uniacid`='{$_W['uniacid']}' AND `id` IN (".implode(',',$ids).")");
        //付款成功，更新库存
        if(!$status){
            return false;
        }
        self::updateOtoGoodsTotal($ids);
        $orders = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_order_list)." WHERE uniacid='{$_W['uniacid']}' AND id IN (".implode(',',$ids).")");
        if(!empty($orders) && is_array($orders)){
            foreach($orders as $k => $order){
                //插入收入记录
                self::insertStoreIncome(array(
                    'uniacid' => $order['uniacid'],
                    'store_id' => $order['store_id'],
                    'store_type' => $order['store_type'],
                    'buy_uid' => $order['uid'],
                    'order_id' => $order['id'],
                    'pay_price' => $order['pay_total_price'],
                    'post_fee' => $order['pay_postage_fee'],
                    'credit1' => $order['use_credit1'],
                    'credit2' => $order['use_credit2'],
                    'order_type' => ORDER_TYPE_OTO_GOODS,
                    'order_no' => $order['order_no'],
                    'createtime' => TIMESTAMP
                ));
                //更新商家总收入、邮费、支付价、使用积分、使用余额
                pdo_query("UPDATE ".tablename(self::$_db_store_list)." SET total_pay_price=total_pay_price+{$order['pay_total_price']},total_post_fee=total_post_fee+{$order['pay_postage_fee']},total_credit1=total_credit1+{$order['use_credit1']},total_credit2=total_credit1+{$order['use_credit2']} WHERE uniacid='{$_W['uniacid']}' AND id='{$order['store_id']}'");
            }
        }
        return true;
    }

    /***
     * @param $data
     * @return bool
     * 插入店铺收益
     */
    public static function insertStoreIncome($data){
        $status = pdo_insert(self::$_db_store_income,$data);
        return !$status?false:true;
    }

    /**
     * @param int $id
     * @return bool
     * 店内在线支付订单处理
     */
    public static function otoOfflineOrder($id = 0,$pay_method){
        global $_W;
        if(!check_id($id)){
            return false;
        }
        $status = pdo_update(self::$_db_order_offline,array(
            'pay_status' => PAY_YES,
            'pay_method' => $pay_method,
            'finish_time' => TIMESTAMP
        ),array(
            'uniacid' => $_W['uniacid'],
            'id' => $id,
            'pay_status' => PAY_NO
        ));
        return !$status?false:true;
    }

    /**
     * @param int $id
     * @return array|null
     * 获取线上支付订单
     */
    public static function getOfflineOrderInfoById($id = 0){
        if(!check_id($id)){
            return null;
        }
        $info = pdo_get(self::$_db_order_offline,array(
            'id' => $id
        ));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param array $ids
     * @return bool
     * 更新库存
     */
    public static function updateOtoGoodsTotal($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $orderList = self::getOrderListByIds($ids);
            if(!empty($orderList) && is_array($orderList)){
                $error_num = 0;
                foreach($orderList as $order){
                    $goods_info = self::getGoodsInfoById($order['goods_id']);
                    if(!empty($goods_info) && is_array($goods_info)){
                        $data = array(
                            'sale_count' => $goods_info['sale_count']+$order['buy_num'],
                            'total' => $goods_info['total'] - $order['buy_num'],
                            'updatetime' => TIMESTAMP
                        );
                        if($goods_info['is_open_spec'] == OPEN_STATUS && !empty($order['sku_key'])){
                            //满足规格
                            if(!empty($goods_info['sku_list']) && is_array($goods_info['sku_list'])){
                                //设置了规格
                                if(isset($goods_info['sku_list'][$order['sku_key']])) {
                                    //购买的商品规格存在，并且未改变
                                    if ($order['sku_desc'] == $goods_info['sku_list'][$order['sku_key']]['filed_1'] . SPLIT_GOODS_SKU_DESC . $goods_info['sku_list'][$order['sku_key']]['filed_2']) {
                                        $goods_info['sku_list'][$order['sku_key']]['total'] -= $order['buy_num'];
                                    }
                                }
                            }
                        }
                        $data['sku_list'] = iserializer($goods_info['sku_list']);
                        $status = self::updateGoodsInfoById($order['goods_id'],$data);
                        if(!$status){
                            $error_num ++;
                        }
                    }
                }
                return $error_num == 0?true:false;
            }
        }
        return false;
    }

    /**
     * @param array $ids
     * @return array|null
     * 根据订单ids获取订单信息
     */
    public static function getOrderListByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_order_list)." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND id IN (".implode(',',$ids).")");
            return !empty($list) && is_array($list)?$list:null;
        }
        return null;
    }

    /**
     * @param int $id
     * @return array|bool|null
     * 根据商品id获取商品信息
     */
    public static function getGoodsInfoById($id = 0){
        global $_W;
        if(!check_id($id)){
            return false;
        }
        $goods_info = pdo_get(self::$_db_goods_list,array('uniacid'=>$_W['uniacid'],'is_display'=>DISPLAY_YES,'is_check'=>CHECK_PASS,'id'=>$id));
        if(!empty($goods_info) && is_array($goods_info)){
            if(!empty($goods_info['attr_list'])){
                $goods_info['attr_list'] = iunserializer($goods_info['attr_list']);
            }
            if(!empty($goods_info['spec_list'])){
                $goods_info['spec_list'] = iunserializer($goods_info['spec_list']);
            }
            if(!empty($goods_info['sku_list'])){
                $goods_info['sku_list'] = iunserializer($goods_info['sku_list']);
            }
            if(!empty($goods_info['thumbs'])){
                $goods_info['thumbs'] = iunserializer($goods_info['thumbs']);
            }
            return $goods_info;
        }
        return null;
    }


    /**
     * @param int $id
     * @param array $data
     * @return bool|null
     * 修改商品信息
     */
    public static function updateGoodsInfoById($id = 0,$data = array()){
        global $_W;
        if(!check_id($id) || !is_array($data)){
            return null;
        }
        $status = pdo_update(self::$_db_goods_list,$data,array('uniacid'=>$_W['uniacid'],'id'=>$id));
        return !$status?false:true;
    }


}
<?php
//分销Model
load()->func('check');
class DistributionModel{
    //订单列表
    private static $db_bbc_oto_order_list = "order_list";

    //订单商品表
    private static $db_bbc_oto_order_goods = "order_goods";

    //分销设置表
    private static $db_distribution_config = "distribution_config";

    /**
     * @param int $id
     * @param string $order_type
     * @return array|null
     * 获取订单信息
     */
    public static function getOrderInfo($id = 0,$order_type = ORDER_TYPE_OTO_GOODS){
        global $_W;
        if(!check_id($id)){
            return null;
        }
        if($order_type == ORDER_TYPE_OTO_GOODS ){
            $info = pdo_get(self::$db_bbc_oto_order_list,array(
                'uniacid' => $_W['uniacid'],
                'module' => $order_type,
                'id' => $id
            ));
            return !empty($info) && is_array($info)?$info:null;
        }
        return null;
    }

    /**
     * @param int $id
     * @param string $order_type
     * @param array $data
     * @return array|bool|null
     * 更改订单信息
     */
    public static function updateOrderInfo($id = 0,$order_type = ORDER_TYPE_OTO_GOODS,$data = array()){
        global $_W;
        if(!check_id($id) || empty($data) || !is_array($data)){
            return false;
        }
        if($order_type == ORDER_TYPE_OTO_GOODS){
            $status = pdo_update(self::$db_bbc_oto_order_list,$data,array(
                'uniacid' => $_W['uniacid'],
                'module' => $order_type,
                'id' => $id
            ));
            return !$status?false:true;
        }
        return false;
    }


    /**
     * @param int $order_id
     * @return array|null
     * 根据订单ID获取订单商品信息
     */
    public static function getOrderGoodsInfoByOrderId($order_id = 0){
        global $_W;
        $info = pdo_get(self::$db_bbc_oto_order_goods,array(
            'uniacid' => $_W['uniacid'],
            'order_id' => $order_id
        ));
        return !empty($info) && is_array($info)?$info:null;
    }


    /**
     * @param string $order_type
     * @return null
     * 获取模块分销设置
     */
    public static function getDistribution($order_type = ORDER_TYPE_OTO_GOODS){
        global $_W;
        $info = pdo_get(self::$db_distribution_config,array(
            'uniacid' => $_W['uniacid'],
            'module' => $order_type
        ));
        if(!empty($info) && is_array($info)){
            $info['setting'] = iunserializer($info['setting']);
            return $info;
        }
        return null;
    }
}
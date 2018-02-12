<?php
load()->func('check');
load()->func('logging');
load()->func('notice');
load()->model('distribution');
class Distribution{
    private $_order_id;
    private $_order;//订单信息
    private $_order_type;//订单类型
    private $_distribution_config;

    /**
     * @param int $order_id
     * @param int $order_type
     * 初始化函数
     */
    public function __construct($order_id = 0,$order_type = ORDER_TYPE_OTO_GOODS){
        $this->_order_id = $order_id;
        $this->_order = DistributionModel::getOrderInfo($order_id,$order_type);
        $this->_order_type = $order_type;
        $this->_distribution_config = DistributionModel::getDistribution($order_type);
    }

    /**
     * @return bool
     * 分销入口函数
     */
    public function deal(){
        if(empty($this->_order) || !is_array($this->_order)){
            logging_run("订单信息不存在,订单ID:{$this->_order_id}",__FUNCTION__,__CLASS__);
            return false;
        }
        if($this->_order['order_status'] != ORDER_STATUS_COMPLETE || $this->_order['pay_status'] != PAY_YES){
            logging_run("订单状态为：{$this->_order['order_status']},支付状态为：{$this->_order['pay_status']},订单ID：{$order_id}");
            return false;
        }
        if($this->_order['distribution_status'] == DISTRIBUTION_STATUS_YES){
            logging_run("订单已经分销，订单ID：{$this->_order_id}",__FUNCTION__,__CLASS__);
            return false;
        }
        $status = DistributionModel::updateOrderInfo($this->_order['id'],$this->_order_type,array(
            'distribution_status' => DISTRIBUTION_STATUS_YES,
            'updatetime' => TIMESTAMP
        ));
        if(!$status){
            logging_run("订单状态更改失败，订单ID：{$this->_order['id']}",__FUNCTION__,__CLASS__);
            return false;
        }
        //处理赠送积分
        $this->_givePoint();

        //分销设置信息
        if(empty($this->_distribution_config) || !is_array($this->_distribution_config)){
            logging_run("未获取到分销设置信息，订单ID：{$this->_order['id']}",__FUNCTION__,__CLASS__);
            return false;
        }

        //处理三级分销
        $this->_levelDistribution($this->_distribution_config);

        //按营业额的分销在这里分销
        $this -> _fangYuanBao($this->_distribution_config);

        return true;
    }


    /**
     * @param $config
     * @return bool
     * 方圆宝模式
     */
    private function _fangYuanBao($config){
        return true;
    }


    /**
     * @param $config
     * @return bool
     * 处理三级分销
     */
    private function _levelDistribution($config){
        if(!isset($config['setting']['level_distribution'])){
            logging_run("未获取到三级分销设置信息，订单ID：{$this->_order['id']}",__FUNCTION__,__CLASS__);
            return false;
        }
        if($this->_order['distribution_money'] <= 0){
            logging_run("商品分销金额不足，订单ID：{$this->_order['id']}",__FUNCTION__,__CLASS__);
            return false;
        }
        $setting = $config['setting']['level_distribution'];
        if($setting['status'] == CLOSE_STATUS){
            logging_run("三级分销处于关闭状态，订单ID：{$this->_order['id']}",__FUNCTION__,__CLASS__);
            return false;
        }
        if(!empty($setting['rebate']) && is_array($setting['rebate'])){
            $level_layer_num = count($setting['rebate']);
            load()->model('mc');
            $member_info = mc_fetch($this->_order['uid']);
            if(empty($member_info) || !is_array($member_info)){
                logging_run("会员信息不存在，订单ID：{$this->_order['id']}",__FUNCTION__,__CLASS__);
                return false;
            }
            if(!check_relation($member_info['relation'])){
                logging_run("关系树异常，会员ID：{$member_info['uid']}，订单ID：{$this->_order['id']}",__FUNCTION__,__CLASS__);
                return false;
            }
            if(empty($member_info['relation'])){
                logging_run("会员ID：{$member_info['uid']}没有上级，订单ID：{$this->_order['id']}",__FUNCTION__,__CLASS__);
                return false;
            }
            $parents = explode(SPLIT_RELATION,$member_info['relation']);
            load()->model('mc');
            foreach($parents as $k=> $parent_uid){
                //当前层数，大于设置的返佣层数
                if($k+1 > $level_layer_num)break;
                $money = floatval(floatval($setting['rebate'][$k+1])*0.01*$this->_order['distribution_money']);
                if($money > 0){
                    mc_credit_update($parent_uid,'credit3',$money,array(
                        $parent_uid,
                        "您的编号为：{$this->_order['uid']}的好友购买商品，返您：{$money}元佣金",
                        $this->_order_type
                    ));
                    notice_send_simple_text($parent_uid,"您的编号为：{$this->_order['uid']}的好友购买商品，返您：{$money}元佣金");
                }
            }
            return true;
        }
        return false;
    }


    /**
     * @return bool
     * 赠送积分
     */
    private function _givePoint(){
        if($this->_order_type == MODULE_NAME_OTO || $this->_order_type == MODULE_NAME_SHOP){
            $order_goods_info = DistributionModel::getOrderGoodsInfoByOrderId($this->_order['id']);
            if(empty($order_goods_info) || !is_array($order_goods_info)){
                logging_run("订单商品信息不存在，订单ID：{$this->_order['id']}",__FUNCTION__,__CLASS__);
                return false;
            }
            if(!empty($order_goods_info['give_credit1'])){
                if(strexists($order_goods_info['give_credit1'],'%')) {
                    $give_credit1 = floatval((floatval(str_replace('%','',trim($order_goods_info['give_credit1'])))/100)*($this->_order['pay_total_price']-$this->_order['pay_postage_fee']));
                }else{
                    $give_credit1 = floatval(trim($order_goods_info['give_credit1'])*$this->_order['buy_num']);
                }
                if($give_credit1 <= 0){
                    logging_run("购物返积分数值错误，值为：{$give_credit1}，订单ID：{$this->_order['id']}",__FUNCTION__,__CLASS__);
                    return false;
                }
                load()->model('mc');
                $status = mc_credit_update($this->_order['uid'],'credit1',$give_credit1,array(
                    $this->_order['uid'],
                    "购物返积分，赠送您{$give_credit1}积分，模块：{$this->_order_type}",
                    $this->_order_type
                ));
                if($status == true){
                    notice_send_simple_text($this->_order['uid'],"购物返积分，赠送您{$give_credit1}积分，模块：{$this->_order_type}");
                    return true;
                }
            }
        }
        return false;
    }


}
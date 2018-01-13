<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/5/20
 * Time: 12:46
 * 业务类，函数返回true或者false，禁用退出
 */
load()->func('check');
load()->func('logging');
load()->model('pay');
load()->func('notice');
load()->model('mc');
load()->model('user');
class PayNotify{
    //支付日志信息
    private $_payResult;
    private $_payLog;

    /**
     * @param $result
     * 支付回调数组，回调传递的参数
     */
    public function __construct($result){
        $this->_payResult = $result;
    }

    /**
     * @return bool
     * 处理函数
     */
    public function finish(){
        global $_W;
        //检查信息
        if(empty($this->_payResult) || !is_array($this->_payResult)){
            logging_run("参数错误",__FUNCTION__,__CLASS__);
            return false;
        }
        if(empty($this->_payResult['out_trade_no'])){
            logging_run("交易号不存在",__FUNCTION__,__CLASS__);
            return false;
        }
        $this->_payLog = pdo_get('pay_log',array(
            'out_trade_no' => $this->_payResult['out_trade_no']
        ));
        if(empty($this->_payLog) || !is_array($this->_payLog)){
            logging_run("支付信息不存在",__FUNCTION__,__CLASS__);
            return false;
        }
        if($this->_payLog['pay_status'] == PAY_YES){
            logging_run("订单已支付",__FUNCTION__,__CLASS__);
            return false;
        }
        $status1 = pdo_update('pay_log',array(
            'pay_status' => PAY_YES
        ),array(
            'id' => $this->_payLog['id']
        ));
        if(!$status1){
            logging_run("日志ID：{$this->_payLog['id']},支付日志状态修改失败",__FUNCTION__,__CLASS__);
            return false;
        }
        //如果是使用的积分+现金，将扣除积分
        if($this->_payLog['pay_price'] > 0 && $this->_payLog['use_credit1'] > 0){
            $member_info = mc_fetch($this->_payLog['uid']);
            if(empty($member_info) || !is_array($member_info)){
                logging_run("日志ID：{$this->_payLog['id']},会员信息不存在",__FUNCTION__,__CLASS__);
                return false;
            }
            if($this->_payLog['use_credit1'] > $member_info['credit1']){
                logging_run("日志ID：{$this->_payLog['id']},会员积分不足",__FUNCTION__,__CLASS__);
                return false;
            }
            $reduceCredit1 = mc_credit_update($this->_payLog['uid'],'credit1',-$this->_payLog['use_credit1'],array($this->_payLog['uid'],"积分消费，扣除{$this->_payLog['use_credit1']}积分"));
            if(is_error($reduceCredit1)){
                logging_run("日志ID：{$this->_payLog['id']},会员积分扣除失败",__FUNCTION__,__CLASS__);
                return false;
            }
        }

        //处理订单
        switch($this->_payLog['order_type']){
            case ORDER_TYPE_OLD_FEE:
                $this->_dealOldFeeOrder();
                break;
            case ORDER_TYPE_OTO_GOODS:
                $this->_dealOtoGoodsOrder();
                break;
            case ORDER_TYPE_OFFLINE:
                $this->_dealOfflineOrder();
                break;
            case ORDER_TYPE_PERSON:
                $this->_dealPersonOrder();
                break;
            case ORDER_TYPE_DEVELOP_SHOP:
                $this->_dealDevelopShop();
                break;
            case ORDER_TYPE_SJ_NEWS_AD:
                $this->_dealSjNewsAdOrder();
                break;
            case ORDER_TYPE_SJ_NEWS_RENEW:
                $this->_dealSjNewsRenewOrder();
                break;
            case ORDER_TYPE_VAPP_GOODS:
                $this->_dealVappGoodsOrder();
                break;
        }
        //处理方圆宝分销
        $this->_dealFangyuanBaoDis();
        return true;
    }


    //处理百变APP订单
    private function _dealVappGoodsOrder(){
        //更新订单状态
        $status = pdo_update('vapp_order',array(
            'order_status' => ORDER_STATUS_NOT_DELIVER,
            'pay_status' => PAY_YES,
            'pay_method' => $this->_payLog['pay_method']
        ),array(
            'order_no' => $this->_payLog['out_trade_no']
        ));
        if(!$status){
            logging_run("日志ID：{$this->_payLog['id']},订单状态更新失败",__FUNCTION__,__CLASS__);
            return false;
        }
    }


    //处理三晋都市报续费订单
    private  function _dealSjNewsRenewOrder(){
        $order = pdo_get('sj_news_ad_renew_order',array(
            'uniacid'=>$this->_payLog['uniacid'],
            'id' => $this->_payLog['order_ids']
        ));
        if(!check_data($order)){
            logging_run("日志ID：{$this->_payLog['id']},订单信息不存在",__FUNCTION__,__CLASS__);
            return false;
        }
        $status = pdo_update('sj_news_ad_renew_order',array(
            'pay_status'=>PAY_YES,
            'pay_method' => $this->_payLog['pay_method']
        ),array(
            'uniacid'=>$this->_payLog['uniacid'],
            'id' => $this->_payLog['order_ids']
        ));
        if(!$status){
            logging_run("日志ID：{$this->_payLog['id']},订单状态更新失败",__FUNCTION__,__CLASS__);
            return false;
        }
        //广告显示和有效期
        $ad_info = pdo_get('sj_news_ad',array(
            'id' => $order['ad_id'],
            'uniacid'=>$order['sj_uniacid']
        ));
        if(check_data($ad_info)){
            $status2 = pdo_update('sj_news_ad',array(
                'renew_is_check' => 1,
                'updatetime' => TIMESTAMP
            ),array(
                'id' => $order['ad_id'],
                'uniacid'=>$order['sj_uniacid']
            ));
            if(!$status2){
                logging_run("日志ID：{$this->_payLog['id']},广告续费审核状态更新失败",__FUNCTION__,__CLASS__);
            }
        }
        /**
         * 支付成功后的续费期限，放到总平台审核以后；需要处理
         * 将广告的 renew_is_check = 0
         * 增加相应的天数
         * 续费不操作is_check
         */
    }


    //处理三晋都市报广告订单
    private function _dealSjNewsAdOrder(){
        $order = pdo_get('sj_news_ad_order',array(
            'uniacid'=>$this->_payLog['uniacid'],
            'id' => $this->_payLog['order_ids']
        ));
        if(!check_data($order)){
            logging_run("日志ID：{$this->_payLog['id']},订单信息不存在",__FUNCTION__,__CLASS__);
            return false;
        }
        $status = pdo_update('sj_news_ad_order',array(
            'pay_status'=>PAY_YES,
            'pay_method' => $this->_payLog['pay_method']
        ),array(
            'uniacid'=>$this->_payLog['uniacid'],
            'id' => $this->_payLog['order_ids']
        ));
        if(!$status){
            logging_run("日志ID：{$this->_payLog['id']},订单状态更新失败",__FUNCTION__,__CLASS__);
            return false;
        }
        /**
         * 支付成功后的，放到总平台审核以后
         * 总平台审核后增加天数
         */
    }



    //开店分销
    private function _dealDevelopShop(){
        $order = pdo_get('fangyuanbao_shop_order',array(
            'uniacid'=>$this->_payLog['uniacid'],
            'id' => $this->_payLog['order_ids']
        ));
        if(!check_data($order)){
            logging_run("日志ID：{$this->_payLog['id']},订单信息不存在",__FUNCTION__,__CLASS__);
            return false;
        }
        $status = pdo_update('fangyuanbao_shop_order',array(
            'pay_status'=>PAY_YES,
            'pay_method' => $this->_payLog['pay_method']
        ),array(
            'uniacid'=>$this->_payLog['uniacid'],
            'id' => $this->_payLog['order_ids']
        ));
        if(!$status){
            logging_run("日志ID：{$this->_payLog['id']},订单状态更新失败",__FUNCTION__,__CLASS__);
            return false;
        }
        $shop = pdo_get('fangyuanbao_shop_list',array(
            'uniacid'=>$order['uniacid'],
            'uid'=>$order['uid']
        ));
        if(check_data($shop)){
            logging_run("日志ID：{$this->_payLog['id']},已开通垂直仓储店",__FUNCTION__,__CLASS__);
            return false;
        }
        $shop_data = array(
            'uniacid'=>$order['uniacid'],
            'uid'=>$order['uid'],
            'point_name' => $order['point_name'],
            'point_city' => $order['point_city'],
            'point_address' => $order['point_address'],
            'lat' => $order['lat'],
            'lng' => $order['lng'],
            'province' => $order['province'],
            'city' => $order['city'],
            'district' => $order['district'],
            'address'=>$order['address'],
            'createtime'=>TIMESTAMP
        );
        $status = pdo_insert('fangyuanbao_shop_list',$shop_data);
        $shop_id = pdo_insertid();
        if(!$status || !$shop_id){
            logging_run("日志ID：{$this->_payLog['id']},垂直仓储店新增失败",__FUNCTION__,__CLASS__);
            return false;
        }
        $shop_title = 'LFGD'.str_pad($shop_id,6,0,STR_PAD_LEFT);
        notice_send_simple_text($order['uid'],"您的垂直仓储店申请成功，编号：{$shop_title}，订单号：{$order['order_no']}");
        $user = pdo_get('fangyuanbao_shop_user',array(
            'uniacid'=>$order['uniacid'],
            'uid'=>$order['uid']
        ));
        if(check_data($user)){
            logging_run("日志ID：{$this->_payLog['id']},垂直仓储会员信息已存在",__FUNCTION__,__CLASS__);
            return false;
        }
        $user_data = array(
            'uniacid' => $order['uniacid'],
            'uid'=>$order['uid'],
            'order_id'=>$order['id'],
            'shop_id' => $shop_id,
            'pay_price' => $order['pay_price'],
            'createtime' => TIMESTAMP
        );
        $user_data['relation'] = 0;
        if(!empty($order['parent_uid'])){
            $parent = pdo_get('fangyuanbao_shop_user',array(
                'uniacid'=>$order['uniacid'],
                'uid'=>$order['parent_uid']
            ));
            if(check_data($parent)){
                $user_data['relation'] = $parent['uid'].'-'.$parent['relation'];
            }
        }
        $status2 = pdo_insert('fangyuanbao_shop_user',$user_data);
        if(!$status2){
            logging_run("日志ID：{$this->_payLog['id']},垂直仓储会员增加失败",__FUNCTION__,__CLASS__);
            return false;
        }
        //处理分销
        $config = pdo_getcolumn('fangyuanbao_shop_config',array(
            'uniacid'=>$order['uniacid']
        ),'setting');
        if(empty($config)){
            logging_run("日志ID：{$this->_payLog['id']},平台未设置配置信息",__FUNCTION__,__CLASS__);
            return false;
        }
        $setting = iunserializer($config);
        if(!check_data($setting)){
            logging_run("日志ID：{$this->_payLog['id']},平台未设置配置信息",__FUNCTION__,__CLASS__);
            return false;
        }
        if($setting['status'] != OPEN_STATUS){
            logging_run("日志ID：{$this->_payLog['id']},开店分销未开启",__FUNCTION__,__CLASS__);
            return false;
        }
        $relation = $user_data['relation'];
        if(!empty($relation)) {
            $parents_ids = explode(SPLIT_RELATION, $relation);
            if (!empty($parents_ids) && is_array($parents_ids)) {
                foreach ($parents_ids as $k => $parent_uid) {
                    $total_money = 0;
                    if($parent_uid == 0)break;
                    //推荐奖，不超过2代
                    if($k < 2){
                        $rebate_money = $setting['recommend_money'][$k+1];
                        if($rebate_money > 0){
                            $re_log = array(
                                'uniacid' => $order['uniacid'],
                                'uid' => $parent_uid,
                                'order_no'=>$order['order_no'],
                                'type' => 0,
                                'credit3' => $rebate_money*0.5,
                                'credit1' => $rebate_money*0.5,
                                'createtime'=>TIMESTAMP
                            );
                            $total_money += $rebate_money;
                            $res1 = mc_credit_update($parent_uid,'credit1',$re_log['credit1'],array($parent_uid,"下级会员开店奖励{$re_log['credit1']}积分"));
                            if(is_error($res1)){
                                logging_run("日志ID：{$this->_payLog['id']},开店返积分失败",__FUNCTION__,__CLASS__);
                                return false;
                            }
                            $res2 = mc_credit_update($parent_uid,'credit3',$re_log['credit3'],array($parent_uid,"下级会员开店奖励{$re_log['credit3']}佣金"));
                            if(is_error($res2)){
                                logging_run("日志ID：{$this->_payLog['id']},开店返佣金失败",__FUNCTION__,__CLASS__);
                                return false;
                            }
                            $status3 = pdo_insert('fangyuanbao_shop_rebate',$re_log);
                            if(!$status3){
                                logging_run("日志ID：{$this->_payLog['id']},开店返记录失败",__FUNCTION__,__CLASS__);
                                return false;
                            }
                        }
                    }
                    //管理奖，从第2代开始，过滤掉上一级
                    if($k > 0){
                        $up_num = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_shop_user')." WHERE uniacid='{$order['uniacid']}' AND relation REGEXP '^{$parent_uid}-'");
                        $level = 0;
                        //获取管理奖等级
                        foreach($setting['manage'] as $k1 => $manage){
                            if($up_num >= $manage['up_num']){
                                $level = $k1;
                            }
                        }
                        if($level > 0){
                            //满足管理奖条件，把自己+1代，小于层数
                            if($k+1 <= $setting['manage'][$level]['layer'] && $setting['manage'][$level]['money'] > 0){
                                $rebate_money2 = $setting['manage'][$level]['money'];
                                if($rebate_money2 > 0){
                                    $re_log = array(
                                        'uniacid' => $order['uniacid'],
                                        'uid' => $parent_uid,
                                        'order_no'=>$order['order_no'],
                                        'type' => 1,
                                        'credit3' => $rebate_money2*0.5,
                                        'credit1' => $rebate_money2*0.5,
                                        'createtime'=>TIMESTAMP
                                    );
                                    $total_money += $rebate_money2;
                                    $res1 = mc_credit_update($parent_uid,'credit1',$re_log['credit1'],array($parent_uid,"下级会员开店奖励{$re_log['credit1']}积分"));
                                    if(is_error($res1)){
                                        logging_run("日志ID：{$this->_payLog['id']},开店返积分失败2",__FUNCTION__,__CLASS__);
                                        return false;
                                    }
                                    $res2 = mc_credit_update($parent_uid,'credit3',$re_log['credit3'],array($parent_uid,"下级会员开店奖励{$re_log['credit3']}佣金"));
                                    if(is_error($res2)){
                                        logging_run("日志ID：{$this->_payLog['id']},开店返佣金失败2",__FUNCTION__,__CLASS__);
                                        return false;
                                    }
                                    $status3 = pdo_insert('fangyuanbao_shop_rebate',$re_log);
                                    if(!$status3){
                                        logging_run("日志ID：{$this->_payLog['id']},开店返记录失败2",__FUNCTION__,__CLASS__);
                                        return false;
                                    }
                                }
                            }
                        }
                    }
                   if($total_money > 0){
                       notice_send_simple_text($parent_uid,"恭喜您收到开店奖励{$total_money}积分，请在您的个人中心查收");
                   }
                }
            }
        }
    }

    //处理分销
    private function _dealFangyuanBaoDis(){
//        if($this->_payLog['order_type'] == ORDER_TYPE_OLD_FEE){
//            return false;
//        }
//        //读取方圆宝配置信息
//        $setting = pdo_fetchcolumn("SELECT setting FROM ".tablename('distribution_config')." WHERE uniacid='{$this->_payLog['uniacid']}'");
//        if(!empty($setting)){
//            $setting = iunserializer($setting);
//            if(check_data($setting)){
//                $fyb = $setting['fangyuanbao'];
//                if($fyb['status'] == OPEN_STATUS && $fyb['sum_pay_price'] > 0){
//                    //方圆宝模式
//                    $member = pdo_get('mc_members',array(
//                        'uniacid' => $this->_payLog['uniacid'],
//                        'uid' => $this->_payLog['uid']
//                    ));
//                    if(check_data($member)){
//                        //消费总额
//                        $where = "uniacid='{$this->_payLog['uniacid']}' AND pay_status=".PAY_YES." AND createtime>{$member['last_stat_time']}";
//                        $total_oto_order_pay = pdo_fetchcolumn("SELECT SUM(pay_price) FROM ".tablename('order_list')." WHERE {$where} AND uid='{$this->_payLog['uid']}' AND store_id!='3959'");
//                        $total_offline_pay = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." WHERE {$where} AND uid='{$this->_payLog['uid']}' AND store_id!='3959'");
//                        $total_person_pay = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_person')." WHERE {$where} AND pay_uid='{$this->_payLog['uid']}'");
//                        $total_develop_shop_pay = pdo_fetchcolumn("SELECT SUM(pay_price) FROM ".tablename('fangyuanbao_shop_order')." WHERE {$where} AND uid='{$this->_payLog['uid']}'");
//                        $sum_pay = floatval($total_oto_order_pay+$total_offline_pay+$total_person_pay+$total_develop_shop_pay)+$member['credit6'];
//                        if($sum_pay >= $fyb['sum_pay_price']){ //满消费金额
//                            $count = floor($sum_pay/$fyb['sum_pay_price']);
//                            //修改剩余金额和累计时间
//                            pdo_update('mc_members',array(
//                                'credit6' => round(floatval($sum_pay-$count*$fyb['sum_pay_price']),2),
//                                'last_stat_time' => TIMESTAMP
//                            ),array(
//                                'uniacid'=>$this->_payLog['uniacid'],
//                                'uid' => $this->_payLog['uid']
//                            ));
//                            $first_queue_no = pdo_fetchcolumn("SELECT MAX(queue_no) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$this->_payLog['uniacid']}'");
//                            $first_queue_no +=1;
//                            for($i = 0;$i< $fyb['give_fyb_num']*$count;$i++){
//                                //新增方圆宝
//                                pdo_insert('fangyuanbao_queue',array(
//                                    'uniacid'=>$this->_payLog['uniacid'],
//                                    'uid' => $this->_payLog['uid'],
//                                    'count'=>1,
//                                    'queue_no'=>$first_queue_no++,
//                                    'province' => $member['province'],
//                                    'city'=>$member['city'],
//                                    'district' => $member['district'],
//                                    'createtime' => TIMESTAMP
//                                ));
//                            }
//                            notice_send_simple_text($this->_payLog['uid'],"恭喜您获得".($fyb['give_fyb_num']*$count)."个方圆宝");
//                        }
//                    }
//                }
//            }
//        }
    }

    //处理二维码订单
    private function _dealPersonOrder(){
        $order = pdo_get('order_person',array(
            'uniacid'=>$this->_payLog['uniacid'],
            'id' => $this->_payLog['order_ids']
        ));
        if(!check_data($order)){
            logging_run("日志ID：{$this->_payLog['id']},订单信息不存在",__FUNCTION__,__CLASS__);
            return false;
        }
        $status = pdo_update('order_person',array(
            'pay_status'=>PAY_YES,
            'pay_method' => $this->_payLog['pay_method']
        ),array(
            'uniacid'=>$this->_payLog['uniacid'],
            'id' => $this->_payLog['order_ids']
        ));
        if(!$status){
            logging_run("日志ID：{$this->_payLog['id']},订单状态更新失败",__FUNCTION__,__CLASS__);
            return false;
        }
        if(!empty($order['old_goods_id'])){
            pdo_query("UPDATE ".tablename('old_goods')." SET total=total-1 WHERE uniacid='{$order['uniacid']}' AND id='{$order['old_goods_id']}'");
        }
        //通知转款人
        notice_send_simple_text($order['pay_uid'],"您已成功向会员ID：{$order['cashier_uid']}转账,转账金额为{$order['money']}元，订单号：{$order['order_no']}");
        //通知收款人
        notice_send_simple_text($order['cashier_uid'],"您收到新订单:\n来自会员ID：{$order['pay_uid']}的转账;\n到帐金额{$order['money']}元;\n订单号：{$order['order_no']},\n转款小伙伴留言：{$order['note']}");
    }

    //处理商城订单
    private function _dealOtoGoodsOrder(){
        //更新订单状态
        $status = pdo_update('order_list',array(
            'order_status' => 1,
            'pay_status' => PAY_YES,
            'pay_method' => $this->_payLog['pay_method']
        ),array(
            'order_no' => $this->_payLog['out_trade_no']
        ));
        if(!$status){
            logging_run("日志ID：{$this->_payLog['id']},订单状态更新失败",__FUNCTION__,__CLASS__);
            return false;
        }
        //通知买家
        notice_send_simple_text($this->_payLog['uid'],"您的订单已成功支付,消费金额{$this->_payLog['pay_price']}元，订单号：{$this->_payLog['out_trade_no']}");
        //通知卖家
        $order_ids = explode(SPLIT_ORDER_IDS,$this->_payLog['order_ids']);
        if(check_data($order_ids)){
            foreach($order_ids as $k => $id){
                $order = pdo_get('order_list',array(
                    'uniacid'=>$this->_payLog['uniacid'],
                    'uid'=>$this->_payLog['uid'],
                    'id'=>$id
                ));
                if(check_data($order)){
                    $saler_uid = pdo_fetchcolumn("SELECT saler_uid FROM ".tablename('store_list')." WHERE uniacid='{$order['uniacid']}' AND id='{$order['store_id']}'");
                    if(!empty($saler_uid)){
                        notice_send_simple_text($saler_uid,"您有新订单：\n来自会员：{$order['uid']};\n共计金额：{$order['pay_total_price']}元;\n订单号：{$order['order_no']};\n买家留言：{$order['leave_words']}");
                        $this->_dealStoreFangyuanBaoDis($saler_uid,$order['store_id']);
                    }
                }
            }
        }

    }

    //处理线下付款订单
    private function _dealOfflineOrder(){
        $order = pdo_get('order_offline',array(
            'uniacid'=>$this->_payLog['uniacid'],
            'id' => $this->_payLog['order_ids']
        ));
        if(!check_data($order)){
            logging_run("日志ID：{$this->_payLog['id']},订单信息不存在",__FUNCTION__,__CLASS__);
            return false;
        }
        $status = pdo_update('order_offline',array(
            'pay_status'=>PAY_YES,
            'pay_method' => $this->_payLog['pay_method']
        ),array(
            'uniacid'=>$this->_payLog['uniacid'],
            'id' => $this->_payLog['order_ids']
        ));
        if(!$status){
            logging_run("日志ID：{$this->_payLog['id']},订单状态更新失败",__FUNCTION__,__CLASS__);
            return false;
        }
        //通知卖家
        $store = pdo_get('store_list',array(
            'uniacid'=>$order['uniacid'],
            'id'=>$order['store_id']
        ));
        if(check_data($store)){
            //通知买家
            notice_send_simple_text($order['uid'],"您已成功在店铺：{$store['title']}支付{$order['money']}元，订单号：{$order['order_no']}");
            //通知卖家
            if(!empty($store['saler_uid'])){
                notice_send_simple_text($store['saler_uid'],"您有新订单：\n来自会员ID：{$order['uid']}的在线支付;\n支付金额：{$order['money']};\n订单号：{$order['order_no']};\n买家留言：{$order['note']}");
                $this->_dealStoreFangyuanBaoDis($store['saler_uid'],$store['id']);
            }
        }
    }

    /**
     * @param $saler_uid
     * @param $store_id
     * 商家获得方圆宝
     */
    private function _dealStoreFangyuanBaoDis($saler_uid,$store_id){
        //读取方圆宝配置信息
//        $setting = pdo_fetchcolumn("SELECT setting FROM ".tablename('distribution_config')." WHERE uniacid='{$this->_payLog['uniacid']}'");
//        if(!empty($setting)){
//            $setting = iunserializer($setting);
//            if(check_data($setting)){
//                $fyb = $setting['fangyuanbao'];
//                if($fyb['status'] == OPEN_STATUS && $fyb['sum_pay_price'] > 0){
//                   $fyb_sum_pay_price = 2*$fyb['sum_pay_price'];
//                    //方圆宝模式
//                    $member = pdo_get('mc_members',array(
//                        'uniacid' =>$this->_payLog['uniacid'],
//                        'uid'=>$saler_uid
//                    ));
//                    if(check_data($member)){
//                        //消费总额
//                        $where = "uniacid='{$this->_payLog['uniacid']}' AND pay_status=".PAY_YES." AND createtime>{$member['last_store_stat_time']}";
//                        $total_oto_order_pay = pdo_fetchcolumn("SELECT SUM(pay_price) FROM ".tablename('order_list')." WHERE {$where} AND store_id='{$store_id}' ");
//                        $total_offline_pay = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." WHERE {$where} AND store_id='{$store_id}' ");
//                        $sum_pay = floatval($total_oto_order_pay+$total_offline_pay)+$member['credit7'];
//                        if($sum_pay >= $fyb_sum_pay_price){ //满消费金额
//                            $count = floor($sum_pay/$fyb_sum_pay_price);
//                            //修改剩余金额和累计时间
//                            pdo_update('mc_members',array(
//                                'credit7' => round(floatval($sum_pay-$count*$fyb_sum_pay_price),2),
//                                'last_store_stat_time' => TIMESTAMP
//                            ),array(
//                                'uniacid'=>$this->_payLog['uniacid'],
//                                'uid' => $saler_uid
//                            ));
//                            $first_queue_no = pdo_fetchcolumn("SELECT MAX(queue_no) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$this->_payLog['uniacid']}'");
//                            $first_queue_no +=1;
//                            for($i = 0;$i< $count;$i++){
//                                //新增方圆宝
//                                pdo_insert('fangyuanbao_queue',array(
//                                    'uniacid'=>$this->_payLog['uniacid'],
//                                    'uid' => $saler_uid,
//                                    'count'=>1,
//                                    'queue_no'=>$first_queue_no++,
//                                    'province' => $member['province'],
//                                    'city'=>$member['city'],
//                                    'district' => $member['district'],
//                                    'createtime' => TIMESTAMP
//                                ));
//                            }
//                            notice_send_simple_text($saler_uid,"恭喜您的店铺新获得".($count)."个方圆宝");
//                        }
//                    }
//                }
//            }
//        }
    }

    /**
     * @return bool
     * 处理二手服务费订单
     */
    private function _dealOldFeeOrder(){
        notice_send_simple_text($this->_payLog['uid'],"您的二手服务费订单已成功支付,消费金额{$this->_payLog['pay_price']}元，订单号：{$this->_payLog['out_trade_no']}");
        $order = pdo_get('fangyuanbao_user_order',array(
            'uniacid'=>$this->_payLog['uniacid'],
            'id' => $this->_payLog['order_ids']
        ));
        if(!check_data($order)){
            logging_run("日志ID：{$this->_payLog['id']},订单信息不存在",__FUNCTION__,__CLASS__);
            return false;
        }
        if($order['pay_status'] == PAY_YES && $order['dis_status'] == DISTRIBUTION_STATUS_YES){
            logging_run("日志ID：{$this->_payLog['id']},订单已经支付",__FUNCTION__,__CLASS__);
            return false;
        }
        $status = pdo_update('fangyuanbao_user_order',array(
           'pay_status'=>PAY_YES,
            'dis_status' => DISTRIBUTION_STATUS_YES,
            'pay_method' => $this->_payLog['pay_method']
        ),array(
            'uniacid'=>$this->_payLog['uniacid'],
            'id' => $this->_payLog['order_ids']
        ));
        if(!$status){
            logging_run("日志ID：{$this->_payLog['id']},订单状态更新失败",__FUNCTION__,__CLASS__);
            return false;
        }
        $product_name = array(
            1 => 'A套餐',
            3 => 'C套餐'
        );
        $user = pdo_get('fangyuanbao_user',array(
            'uniacid' => $order['uniacid'],
            'uid' => $order['uid']
        ));
        $user_data = array(
            'product_key' => $order['product_key'],
            'price' => $order['pay_price']
        );
        if(!empty($user)){
            $user_data['updatetime'] = TIMESTAMP;
            $update_user = pdo_update('fangyuanbao_user',$user_data,array(
                'uniacid' => $order['uniacid'],
                'uid' => $order['uid']
            ));
        }else{
            $user_data['uniacid'] = $order['uniacid'];
            $user_data['uid'] = $order['uid'];
            $user_data['createtime'] = TIMESTAMP;
            $update_user = pdo_insert('fangyuanbao_user',$user_data);
        }
        if(!$update_user){
            logging_run("日志ID：{$this->_payLog['id']},用户资料设置失败",__FUNCTION__,__CLASS__);
            return false;
        }
        notice_send_simple_text($order['uid'],"您已成功购买{$product_name[$order['product_key']]}");
        if($order['product_key'] == 3){ //C套餐返佣
            $relation = pdo_fetchcolumn("SELECT relation FROM ".tablename('mc_members')." WHERE uniacid='{$order['uniacid']}' AND uid='{$order['uid']}'");
            if(!empty($relation)){
                $parents_ids = explode(SPLIT_RELATION,$relation);
                if(!empty($parents_ids) && is_array($parents_ids)){
                    foreach($parents_ids as $k => $parent_uid){
                        if($parent_uid == 0 || $k > 0){
                            //只返1级，原来是$k > 1
                            break;
                        }
                        $rebate_money = $k == 0?600:200;
                        if($rebate_money > 0){
                            $insert_log = pdo_insert('fangyuanbao_user_rebate',array(
                                'uniacid' => $order['uniacid'],
                                'uid' => $parent_uid,
                                'buy_uid' => $order['uid'],
                                'order_no' => $order['order_no'],
                                'money' => $rebate_money,
                                'createtime' => TIMESTAMP
                            ));
                            if(!$insert_log){
                                logging_run("日志ID：{$this->_payLog['id']},记录插入失败",__FUNCTION__,__CLASS__);
                                return false;
                            }
                            mc_credit_update($parent_uid,'credit3',$rebate_money,array($parent_uid,"会员{$insert_log['buy_uid']}缴纳服务费，赠送您:{$rebate_money}元，订单号：{$order['order_no']}"));
                            notice_send_simple_text($parent_uid,"恭喜您收到服务推广{$rebate_money}积分，请在您的个人中心查收");
                        }
                    }
                }
            }
        }
    }
}

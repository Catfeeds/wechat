<?php
load()->func('check');
load()->func('notice');
load()->model('mc');
if(empty($do)){$do = 'display';}
$big_city = array(
    '北京市',
    '天津市',
    '上海市',
    '重庆市',
    '石家庄市',
    '郑州市',
    '武汉市',
    '长沙市',
    '南京市',
    '南昌市',
    '沈阳市',
    '长春市',
    '哈尔滨市',
    '西安市',
    '太原市',
    '济南市',
    '成都市',
    '西宁市',
    '合肥市',
    '海口市',
    '广州市',
    '贵阳市',
    '杭州市',
    '福州市',
    '台北市',
    '兰州市',
    '昆明市',
    '拉萨市',
    '银川市',
    '南宁市',
    '乌鲁木齐市'
    //'呼和浩特市'
);
if($do == 'display'){
    $province = trim($_GPC['province']);
    $city = trim($_GPC['city']);
    $district = trim($_GPC['district']);
    $config = pdo_get('distribution_config',array(
        'uniacid' => $_W['uniacid']
    ));
    if(!check_data($config)){
        message('平台未设置兑换参数','','error');
    }
    $setting = iunserializer($config['setting']);
    if(!check_data($setting)){
        message('平台未设置兑换参数','','error');
    }

    //上周时间条件
    $endtime = strtotime(date('Y-m-d 00:00:00'));
    $starttime = $endtime - 7*24*3600;
    $time_where = " AND createtime BETWEEN {$starttime} AND {$endtime}";

    //昨日时间条件
    $yes_endtime = strtotime(date('Y-m-d 00:00:00'));
    $yes_starttime = $endtime - 24*3600;
    $yes_time_where = " AND createtime BETWEEN {$yes_starttime} AND {$yes_endtime}";

    //省份条件
    $area_where = "";
    if(!empty($province)){
        $area_where .= " AND province='{$province}'";
    }
    if(!empty($city)){
        $area_where .= " AND city='{$city}'";
    }
    if(!empty($district)){
        if(!empty($city)){
            if(in_array($city,$big_city)){
                $area_where .= " AND district='{$district}'";
            }
        }
    }

    //上周总交易额
    $total_pay_price = 0;
    $online_pay_price = pdo_fetchcolumn("SELECT SUM(pay_total_price) FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND pay_status='1' AND store_id IN (SELECT id FROM ".tablename('store_list')." WHERE uniacid='{$_W['uniacid']}'{$area_where}){$time_where}");
    if(!empty($online_pay_price)){
        $total_pay_price+=$online_pay_price;
    }
    $person_pay_price = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." WHERE uniacid='{$_W['uniacid']}' AND pay_status='1' AND store_id IN (SELECT id FROM ".tablename('store_list')." WHERE uniacid='{$_W['uniacid']}'{$area_where}){$time_where}");
    if(!empty($person_pay_price)){
        $total_pay_price+=$person_pay_price;
    }
    $offline_pay_price = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_person')." WHERE uniacid='{$_W['uniacid']}' AND pay_status='1' AND cashier_uid IN (SELECT uid FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}'{$area_where}){$time_where}");
    if(!empty($offline_pay_price)){
        $total_pay_price+=$offline_pay_price;
    }
    $user_pay_price = pdo_fetchcolumn("SELECT SUM(pay_price) FROM ".tablename('fangyuanbao_user_order')." WHERE uniacid='{$_W['uniacid']}' AND pay_status='1' AND uid IN (SELECT uid FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}'{$area_where}){$time_where}");
    if(!empty($user_pay_price)){
        $total_pay_price+=$user_pay_price;
    }

    $shop_pay_price = pdo_fetchcolumn("SELECT SUM(pay_price) FROM ".tablename('fangyuanbao_shop_order')." WHERE uniacid='{$_W['uniacid']}' AND pay_status='1' AND uid IN (SELECT uid FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}'{$area_where}){$time_where}");
    if(!empty($shop_pay_price)){
        $total_pay_price+=$shop_pay_price;
    }


    if(empty($total_pay_price)){
        $total_pay_price = 0;
    }
    //昨日总交易额
    $yesterday_total_pay_price = 0;
    $yes_online_pay_price = pdo_fetchcolumn("SELECT SUM(pay_total_price) FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND pay_status='1' AND store_id IN (SELECT id FROM ".tablename('store_list')." WHERE uniacid='{$_W['uniacid']}'{$area_where}){$yes_time_where}");
    if(!empty($yes_online_pay_price)){
        $yesterday_total_pay_price+=$yes_online_pay_price;
    }
    $yes_person_pay_price = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." WHERE uniacid='{$_W['uniacid']}' AND pay_status='1' AND store_id IN (SELECT id FROM ".tablename('store_list')." WHERE uniacid='{$_W['uniacid']}'{$area_where}){$yes_time_where}");
    if(!empty($yes_person_pay_price)){
        $yesterday_total_pay_price+=$yes_person_pay_price;
    }
    $yes_offline_pay_price = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_person')." WHERE uniacid='{$_W['uniacid']}' AND pay_status='1' AND cashier_uid IN (SELECT uid FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}'{$area_where}){$yes_time_where}");
    if(!empty($yes_offline_pay_price)){
        $yesterday_total_pay_price+=$yes_offline_pay_price;
    }
    $yes_user_pay_price = pdo_fetchcolumn("SELECT SUM(pay_price) FROM ".tablename('fangyuanbao_user_order')." WHERE uniacid='{$_W['uniacid']}' AND pay_status='1' AND uid IN (SELECT uid FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}'{$area_where}){$yes_time_where}");
    if(!empty($yes_user_pay_price)){
        $total_pay_price+=$yes_user_pay_price;
    }

    $yes_shop_pay_price = pdo_fetchcolumn("SELECT SUM(pay_price) FROM ".tablename('fangyuanbao_shop_order')." WHERE uniacid='{$_W['uniacid']}' AND pay_status='1' AND uid IN (SELECT uid FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}'{$area_where}){$yes_time_where}");
    if(!empty($yes_shop_pay_price)){
        $total_pay_price+=$yes_shop_pay_price;
    }

    if(empty($yesterday_total_pay_price)){
        $yesterday_total_pay_price = 0;
    }


    //营业额*比例 = 建议发放数目
    $fyb_send_rate = $setting['fangyuanbao']['rebate_rate']*0.01;
    $rebate_credit1 = empty($setting['fangyuanbao']['rebate_credit1'])?0:floatval($setting['fangyuanbao']['rebate_credit1']);
    $send_num = floor(floatval($total_pay_price*$fyb_send_rate/($rebate_credit1>0?$rebate_credit1:1)));
    if($_W['isajax']){
        $ac = trim($_GPC['ac']);
        //执行兑换方圆宝
        $data = array(
            'province' => trim($_GPC['area']['province']),
            'city' => trim($_GPC['area']['city']),
            'district' => trim($_GPC['area']['district']),
            'num' => floor(trim($_GPC['num'])),
            'old_num' => floor(trim($_GPC['old_num']))
        );
        if($ac == 'money'){
            message(array(
                'total' => $total_pay_price,
                'yesterday_total' => $yesterday_total_pay_price,
                'num' => $send_num
            ),'','success');
        }else{
            $error = array(
                'province' => '请选择省份',
                'city' => '请选择城市'
            );
            foreach($error as $k => $message){
                if(empty($data[$k])){
                    message($message,'','error');
                }
            }
            if(in_array($data['city'],$big_city) && empty($data['district'])){
                message('请选择区县','','error');
            }
            if($data['num'] < 1){
                $data['num'] = 0;
            }
            if($data['old_num'] < 1){
                $data['old_num'] = 0;
            }
            $send_flag_time = 1498233600;
            if($data['city'] == '沧州市'){
                $send_flag_time = 1500825600;
            }
            if(in_array($data['city'],$big_city)){
                //大城市
                $list = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND province='{$data['province']}' AND city='{$data['city']}' AND district='{$data['district']}' AND status=".NO_STATUS." AND createtime>={$send_flag_time} GROUP BY uid ORDER BY createtime ASC LIMIT 0,{$data['num']}");
                $old_list = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND province='{$data['province']}' AND city='{$data['city']}' AND district='{$data['district']}' AND status=".NO_STATUS." AND createtime<{$send_flag_time} GROUP BY uid ORDER BY createtime ASC LIMIT 0,{$data['old_num']}");
            }else{
                //小城市
                $list = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND province='{$data['province']}' AND city='{$data['city']}' AND status=".NO_STATUS." AND createtime>={$send_flag_time} GROUP BY uid ORDER BY createtime ASC LIMIT 0,{$data['num']}");
                $old_list = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND province='{$data['province']}' AND city='{$data['city']}' AND status=".NO_STATUS." AND createtime<{$send_flag_time} GROUP BY uid ORDER BY createtime ASC LIMIT 0,{$data['old_num']}");
            }

            //发送新方圆宝
            if(check_data($list)){
                pdo_begin();
                foreach($list as $k => $fyb){
                    $send_status = pdo_update('fangyuanbao_queue',array(
                        'status'=>IS_STATUS,
                        'updatetime'=>TIMESTAMP,
                        'rebatetime' => TIMESTAMP
                    ),array(
                        'uniacid'=>$_W['uniacid'],
                        'uid'=>$fyb['uid'],
                        'status'=>NO_STATUS,
                        'id'=>$fyb['id']
                    ));
                    if(!$send_status){
                        pdo_rollback();
                        message('兑换失败:-1','','error');
                    }
                    if($rebate_credit1 > 0){
                        $result = mc_credit_update($fyb['uid'],'credit1',$rebate_credit1,array($fyb['uid'],"方圆宝兑换，获得{$rebate_credit1}积分"));
                        if(is_error($result)){
                            pdo_rollback();
                            message('兑换失败:-3','','error');
                        }
                    }
                    $log = array(
                        'uniacid' => $_W['uniacid'],
                        'uid' => $fyb['uid'],
                        'queue_id' => $fyb['id'],
                        'credit1'=>$rebate_credit1,
                        'birthtime' => $fyb['createtime'],
                        'createtime' => TIMESTAMP
                    );
                    $insert = pdo_insert('fangyuanbao_rebate',$log);
                    if(!$insert){
                        pdo_rollback();
                        message('兑换失败','','error');
                    }
                }
                pdo_commit();
            }

            //发放老方圆宝
            if(check_data($old_list)){
                pdo_begin();
                foreach($old_list as $k2 => $fyb2){
                    $send_status = pdo_update('fangyuanbao_queue',array(
                        'status'=>IS_STATUS,
                        'updatetime'=>TIMESTAMP,
                        'rebatetime' => TIMESTAMP
                    ),array(
                        'uniacid'=>$_W['uniacid'],
                        'uid'=>$fyb2['uid'],
                        'status'=>NO_STATUS,
                        'id'=>$fyb2['id']
                    ));
                    if(!$send_status){
                        pdo_rollback();
                        message('兑换失败:-1','','error');
                    }
                    if($rebate_credit1 > 0){
                        $result = mc_credit_update($fyb2['uid'],'credit1',$rebate_credit1,array($fyb2['uid'],"方圆宝兑换，获得{$rebate_credit1}积分"));
                        if(is_error($result)){
                            pdo_rollback();
                            message('兑换失败:-3','','error');
                        }
                    }
                    $log = array(
                        'uniacid' => $_W['uniacid'],
                        'uid' => $fyb2['uid'],
                        'queue_id' => $fyb2['id'],
                        'credit1'=>$rebate_credit1,
                        'birthtime' => $fyb2['createtime'],
                        'createtime' => TIMESTAMP
                    );
                    $insert = pdo_insert('fangyuanbao_rebate',$log);
                    if(!$insert){
                        pdo_rollback();
                        message('兑换失败','','error');
                    }
                }
                pdo_commit();
            }

            foreach($list as $k3 => $fyb3){
                notice_send_simple_text($fyb3['uid'],"您的积分已兑换，请注意查收");
            }
            foreach($old_list as $k4 => $fyb4){
                notice_send_simple_text($fyb4['uid'],"您的积分已兑换，请注意查收");
            }
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            pdo_insert('fangyuanbao_op_log',$data);
            message('兑换成功','','success');
        }
    }
}elseif($do == 'log'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('createtime');
    $province = $_GPC['area']['province'];
    $city = $_GPC['area']['city'];
    $district = $_GPC['area']['district'];
    $where = "uniacid='{$_W['uniacid']}' AND createtime BETWEEN {$starttime} AND {$endtime}";
    if(!empty($province)){
        $where .= " AND province='{$province}'";
    }
    if(!empty($city)){
        $where .= "AND city='{$city}'";
    }
    if(!empty($district)){
        $where .= " AND district='{$district}'";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_op_log')." WHERE {$where} ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_op_log')." WHERE {$where}");
    $total_send_num = pdo_fetchcolumn("SELECT SUM(num) FROM ".tablename('fangyuanbao_op_log')." WHERE {$where}");
    $total_old_send_num = pdo_fetchcolumn("SELECT SUM(old_num) FROM ".tablename('fangyuanbao_op_log')." WHERE {$where}");
    $pager = pagination($total,$page,$psize);
}
template('fyb/display');
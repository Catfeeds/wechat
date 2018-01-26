<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/5/29
 * Time: 9:14
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
//获取所有订单数目
load()->func('check');
load()->func('notice');
load()->model('mc');
$user = mc_fetch($_W['member']['uid']);
if(empty($user)){
    message('用户不存在','','error');
}
if($do == 'display'){

    //获取总价格
    $total_oto_order_pay = pdo_fetchcolumn("SELECT SUM(pay_price) FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND pay_status=".PAY_YES);
    $total_offline_pay = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_offline')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND pay_status=".PAY_YES);
    $total_person_pay = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename('order_person')." WHERE uniacid='{$_W['uniacid']}' AND pay_uid='{$_W['member']['uid']}' AND pay_status=".PAY_YES);
    //获取总消费次数
    $total_oto_order_num = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('order_list')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND pay_status=".PAY_YES);
    $total_offline_num = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('order_offline')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND pay_status=".PAY_YES);
    $total_person_num = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('order_person')." WHERE uniacid='{$_W['uniacid']}' AND pay_uid='{$_W['member']['uid']}' AND pay_status=".PAY_YES);

    //可排队数目
    $total_queue_num = floor(($total_person_pay+$total_offline_pay+$total_oto_order_pay)/1000);
    //可激活数目
    $total_active_num = $total_oto_order_num+$total_offline_num+$total_person_num;
    /* end 方圆宝显示状态 */

    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page - 1)*$psize;
    $list = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' ORDER BY createtime ASC LIMIT {$pindex},{$psize}");
    $total_fangyuanbao = pdo_fetchcolumn("SELECT SUM(count) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND status=0");
    $can_give_fangyuanbao = pdo_fetchcolumn("SELECT SUM(count) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND status=0");
    if(empty($total_fangyuanbao)){
        $total_fangyuanbao = 0;
    }
    if(empty($can_give_fangyuanbao)){
        $can_give_fangyuanbao = 0;
    }
    if(check_data($list)){
        foreach($list as $k => &$v){
            $v['createtime'] = date('Y-m-d H:i',$v['createtime']);
            $v['label'] =  $v['status'] == IS_STATUS?'<label class="label label-success">已兑换</label>':'<label class="label label-default">未兑换</label>';
            $now_no = $pindex+$k+1;
            $v['status_tip'] = '已形成';
            if($now_no <= $total_active_num){
                $v['status_tip'] = '已激活';
            }
            if($now_no <= $total_queue_num){
                $v['status_tip'] = '排队中';
            }
        }
    }
    if($_W['isajax']){
        if(check_data($list)){
            message($list,'','success');
        }
        message('没有更多方圆宝','','error');
    }
}elseif($do == 'give'){
    //转让方圆宝
    $can_give_fangyuanbao = pdo_fetchcolumn("SELECT SUM(count) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND status=0");
    if(empty($can_give_fangyuanbao)){
        $can_give_fangyuanbao = 0;
    }
    if($_W['isajax']){
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'give_uid' => floor(trim($_GPC['uid'])),
            'count'=>floor(trim($_GPC['count'])),
            'createtime' => TIMESTAMP
        );
        if(empty($data['give_uid'])){
            message('请输入对方的ID号','','error');
        }
        if($_W['member']['uid'] == $data['give_uid']){
            message('方圆宝不能转让给自己','','error');
        }
        $receiver = pdo_get('mc_members',array(
            'uniacid'=>$_W['uniacid'],
            'uid'=>$data['give_uid']
        ));
        if(!check_data($receiver)){
            message('会员信息不存在','','error');
        }
        if(empty($receiver['province']) || empty($receiver['city']) || empty($receiver['district'])){
            message('对方未设置消费地址，不能转让','','error');
        }
        if(empty($data['count'])){
            message('请输入转让数量','','error');
        }
        if($data['count'] > $can_give_fangyuanbao){
            message('转让数目不能超过拥有的方圆宝数量','','error');
        }
        $ids = array();
        $give_count = 0;
        $fyb_list = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND status=0");
        pdo_begin();
        if(!check_data($fyb_list)){
            message('方圆宝信息获取失败','','error');
        }
        foreach($fyb_list as $k => $fyb){
            if($give_count>= $data['count'])break;
            $give_status = pdo_update('fangyuanbao_queue',array(
                'uid'=>$data['give_uid'],
                'is_give' => IS_STATUS,
                'province' => $receiver['province'],
                'city' => $receiver['city'],
                'district' => $receiver['district'],
                'updatetime' => TIMESTAMP
            ),array(
                'uniacid'=>$_W['uniacid'],
                'uid' => $fyb['uid'],
                'id'=>$fyb['id'],
                'status'=> NO_STATUS
            ));
            if(!$give_status){
                pdo_rollback();
                message('转让失败','','error');
            }
            array_push($ids,$fyb['id']);
            $give_count++;
        }
        $data['fyb_ids'] = implode(',',$ids);
        $insert_give_record = pdo_insert('fangyuanbao_give',$data);
        if(!$insert_give_record){
            pdo_rollback();
            message('转让失败','','error');
        }
        pdo_commit();
        message('你已成功转让'.$give_count.'个方圆宝',referer(),'success');
    }

}elseif($do == 'give_log'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page - 1)*$psize;
    $list = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_give')." WHERE uniacid='{$_W['uniacid']}' AND (uid='{$_W['member']['uid']}' OR give_uid='{$_W['member']['uid']}') ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    if(check_data($list)){
        foreach($list as $k => &$v){
            $v['createtime'] = date('Y-m-d H:i',$v['createtime']);
        }
    }
    if($_W['isajax']){
        if(check_data($list)){
            message($list,'','success');
        }
        message('没有更多转让记录','','error');
    }
}elseif($do == 'withdraw'){
    $member = pdo_get('mc_members',array(
        'uniacid'=>$_W['uniacid'],
        'uid'=>$_W['member']['uid']
    ));
    $withdraw_account = array();
    if(check_data($member)){
        if(!empty($member['withdraw_account'])){
            $withdraw_account = iunserializer($member['withdraw_account']);
        }
    }
    if(empty($withdraw_account)){
        message('请先设置提现账户',url('set/withdraw/display'),'error');
    }
    if($_W['isajax']){
        $config = pdo_get('distribution_config',array(
            'uniacid' => $_W['uniacid']
        ));
        if(!check_data($config)){
            message('提现未配置，不能提现','','error');
        }
        $setting = iunserializer($config['setting']);
        if(!isset($setting['withdraw']['is_apply'])){
            message('提现未配置，不能提现','','error');
        }
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'order_no' => generateOrderSnByBuyTodayTradeCount(),
            'tel' => trim($_GPC['tel']),
            'username' => trim($_GPC['username']),
            'money' => floatval(trim($_GPC['money'])),
            'withdraw_method' => PAY_METHOD_WECHAT,
            'createtime' => TIMESTAMP
        );
        if($data['money'] <= 0){
            message('提现金额有误，请重新输入','','error');
        }
        if($data['money'] > $user['credit3']){
            message('提现金额不能超过实际金额','','error');
        }
        $status = pdo_insert('mc_withdraw_apply',$data);
        $insert_id = pdo_insertid();
        if(!$status || !$insert_id){
            message("申请失败！", "", "error");
        }
        if($setting['withdraw']['is_apply'] == IS_STATUS){
            //提现需要审核
            mc_credit_update($data['uid'], 'credit3', -$data['money'],array($data['uid'],"佣金提现{$data['money']}元，扣除{$data['money']}元佣金"));
            message('申请成功，请耐心等待平台审核^-^',referer(),'success');
        }else{
            //提现不需要审核
            load()->classs("wx.pay");
            $pay = new WxPay(array(
                "uid"=>$data['uid'],
                "Record_Sn"=>  $data['order_no'],
                "Record_Money"=>$data['money'],
                "realname"=>$data['username']
            ));
            $payResult = $pay->startPay();
            if($payResult === true){ //企业付款成功
                mc_credit_update($data['uid'], 'credit3', -$data['money'],array($data['uid'],"佣金提现{$data['money']}元，扣除{$data['money']}元佣金"));
                pdo_update('mc_withdraw_apply',array(
                    'status' => IS_STATUS,
                    'updatetime' => TIMESTAMP
                ),array(
                    'uniacid'=>$_W['uniacid'],
                    'id'=>$insert_id
                ));
                message('领取成功，请登录微信查收',referer(),'success');
            }else{  //失败
                pdo_update('mc_withdraw_apply',array(
                    'status' => 2,//代表失败
                    'updatetime' => TIMESTAMP
                ),array(
                    'uniacid'=>$_W['uniacid'],
                    'id'=>$insert_id
                ));
                message("申请失败，{$payResult['message']}", "", "error");
            }

        }
    }
}elseif($do == 'withdraw_log'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $list = pdo_fetchall("SELECT * FROM ".tablename('mc_withdraw_apply')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    if(check_data($list)){
        foreach($list  as $k => &$v){
            $v['createtime'] = date('Y-m-d H:i',$v['createtime']);
        }
    }
    if($_W['isajax']){
        if(check_data($list)){
            message($list,'','success');
        }
        message('没有更多记录','','error');
    }
}
template('rebate/display');
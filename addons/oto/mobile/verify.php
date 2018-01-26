<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/5/30
 * Time: 9:28
 */
if($op == 'display'){
    //已经验证直接跳转到个人中心
    $check_info = OtoModel::getMemberCheckInfo();
    if(!empty($check_info) && is_array($check_info)){
        header('location:'.$this->createMobileUrl('user'));
    }
    if($_W['isajax']){
        $user_url = $this->createMobileUrl('user');
        $type = floor(trim($_GPC['type']));
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'type' => $type == 1?1:2,
            'old_uid' => floor(trim($_GPC['uid'])),
            'old_mobile' => trim($_GPC['mobile']),
            'createtime' => TIMESTAMP
        );
        if(!empty($data['old_uid']) && !empty($data['old_mobile'])){
            $info = pdo_get('mc_check',array(
                'old_uid' => $data['old_uid'],
                'old_mobile' => $data['old_mobile']
            ));
            if(check_data($info)){
                message('原会员已恢复过数据','','error');
            }
        }
        if($type == 1){//恢复数据
            if(empty($data['old_uid'])){
                message('请输入原会员ID','','error');
            }
            if(empty($data['old_mobile'])){
                message('请输入原手机号码','','error');
            }
            //恢复积分、佣金、余额
            $member = pdo_get('a_mc_members',array(
                'uniacid' => 306,
                'uid' => $data['old_uid'],
                'mobile' => $data['old_mobile']
            ));
            if(empty($member) || !is_array($member)){
                message('原会员信息不存在','','error');
            }
            //佣金恢复,形成方圆宝的剩余金额
            $total_pay_price = pdo_fetchcolumn("SELECT SUM(userPrice) FROM ".tablename('a_rebate_insur_order')." WHERE uniacid=306 AND uid='{$data['old_uid']}'");
            $status1 = pdo_update('mc_members',array(
                'credit1' => $member['credit1'],
                'credit2' => $member['credit2'],
                'credit3' => $member['credit3'],
                'credit6' => round(floatval($total_pay_price - floor($total_pay_price/1000)*1000),2),
                'updatetime' => TIMESTAMP
            ),array(
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['member']['uid']
            ));
            //恢复店铺
            pdo_update('store_list',array(
                'saler_uid' => $data['uid']
            ),array(
                'uniacid' => $_W['uniacid'],
                'saler_uid' => $data['old_uid']
            ));

            //恢复方圆宝
            pdo_update('fangyuanbao_queue',array(
                'is_used' => 1,
                'uid' => $data['uid']
            ),array(
                'uid' => $data['old_uid'],
                'uniacid' => $data['uniacid']
            ));
            //记录恢复
            pdo_insert('mc_check',$data);
            message('恢复成功，正在跳转',$user_url,'success');

        }else{ //新会员，跳过登录
            $status = pdo_insert('mc_check',$data);
            if(!$status){
                message('进入失败','','error');
            }
            message('进入成功，正在跳转',$user_url,'success');
        }
    }
}
include $this->template('verify');
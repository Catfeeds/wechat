<?php
header("Content-Type:text/html;charset=UTF-8");
require_once '../framework/bootstrap.inc.php';
require_once 'libs/global.func.php';
load()->func('check');
load()->func('notice');
load()->func('logging');
load()->model('pay');
load()->model('mc');
load()->model('user');
$config = pdo_getcolumn('fangyuanbao_shop_config',array(
    'uniacid'=>7
),'setting');
if(empty($config)){
    return false;
}
$setting = iunserializer($config);
if(!check_data($setting)){
    return false;
}
if($setting['status'] != OPEN_STATUS){
    return false;
}
//$parents_ids = explode(SPLIT_RELATION, '1101-203-26-0');
//if (!empty($parents_ids) && is_array($parents_ids)) {
//    load()->model('mc');
//    foreach ($parents_ids as $k => $parent_uid) {
//        $total_money = 0;
//        if($parent_uid == 0)break;
//        //推荐奖，不超过2代
//        if($k < 2){
//            $rebate_money = $setting['recommend_money'][$k+1];
//            if($rebate_money > 0){
//                $re_log = array(
//                    'uniacid' => 7,
//                    'uid' => $parent_uid,
//                    'order_no'=>'20170702000051',
//                    'type' => 0,
//                    'credit3' => $rebate_money*0.5,
//                    'credit1' => $rebate_money*0.5,
//                    'createtime'=>TIMESTAMP
//                );
//                $total_money += $rebate_money;
//                $res1 = mc_credit_update($parent_uid,'credit1',$re_log['credit1'],array($parent_uid,"下级会员开店奖励{$re_log['credit1']}积分"));
//                if(is_error($res1)){
//                    return false;
//                }
//                $res2 = mc_credit_update($parent_uid,'credit3',$re_log['credit3'],array($parent_uid,"下级会员开店奖励{$re_log['credit3']}佣金"));
//                if(is_error($res2)){
//                    return false;
//                }
//                $status3 = pdo_insert('fangyuanbao_shop_rebate',$re_log);
//                if(!$status3){
//                    return false;
//                }
//            }
//        }
//        //管理奖，从第2代开始，过滤掉上一级
//        if($k > 0){
//            $up_num = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_shop_user')." WHERE uniacid='7' AND relation REGEXP '^{$parent_uid}-'");
//            $level = 0;
//            //获取管理奖等级
//            foreach($setting['manage'] as $k1 => $manage){
//                if($up_num >= $manage['up_num']){
//                    $level = $k1;
//                }
//            }
//            if($level > 0){
//                //满足管理奖条件，把自己+1代，小于层数
//                if($k+1 <= $setting['manage'][$level]['layer'] && $setting['manage'][$level]['money'] > 0){
//                    $rebate_money2 = $setting['manage'][$level]['money'];
//                    if($rebate_money2 > 0){
//                        $re_log = array(
//                            'uniacid' => 7,
//                            'uid' => $parent_uid,
//                            'order_no'=>'20170702000051',
//                            'type' => 1,
//                            'credit3' => $rebate_money2*0.5,
//                            'credit1' => $rebate_money2*0.5,
//                            'createtime'=>TIMESTAMP
//                        );
//                        $total_money += $rebate_money2;
//                        $res1 = mc_credit_update($parent_uid,'credit1',$re_log['credit1'],array($parent_uid,"下级会员开店奖励{$re_log['credit1']}积分"));
//                        if(is_error($res1)){
//                            return false;
//                        }
//                        $res2 = mc_credit_update($parent_uid,'credit3',$re_log['credit3'],array($parent_uid,"下级会员开店奖励{$re_log['credit3']}佣金"));
//                        if(is_error($res2)){
//                            return false;
//                        }
//                        $status3 = pdo_insert('fangyuanbao_shop_rebate',$re_log);
//                        if(!$status3){
//                            return false;
//                        }
//                    }
//                }
//            }
//        }
//        if($total_money > 0){
//            echo $parent_uid.'获得'.$total_money.'元<br>';
//            notice_send_simple_text($parent_uid,"恭喜您收到开店奖励{$total_money}积分，请在您的个人中心查收");
//        }
//    }
//}
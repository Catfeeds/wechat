<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/19
 * Time: 14:17
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
if($do == 'display'){
    load()->app('tpl');
    load()->model('mc');

    $member_info = mc_fetch($_W['member']['uid']);
    if($_W['isajax']){
        if(empty($member_info) || !is_array($member_info)){
            message('会员信息不存在','','error');
        }
        $gender = floor(trim($_GPC['gender']));
        $birthyear = '';
        $birthmonth = '';
        $birthday = '';
        if(!in_array($gender,array(GENDER_TYPE_SAFE, GENDER_TYPE_MALE, GENDER_TYPE_FEMALE))){
            $gender = GENDER_TYPE_SAFE;
        }
        if(!empty($_GPC['birthday'])){
            list($birthyear,$birthmonth,$birthday) = explode('-',trim($_GPC['birthday']));
        }
        $data = array(
            'nickname' => $_GPC['nickname'],
            'gender' => $gender,
            'birthyear' => $birthyear,
            'birthmonth' => $birthmonth,
            'birthday' => $birthday,
            'updatetime' => TIMESTAMP
        );
        $status = mc_update($_W['member']['uid'], $data);
        if(!$status){
            message('保存失败','','error');
        }
        message('保存成功',referer(),'success');
    }
    $birthday = date('Y-m-d');
    if(!empty($member_info['birthyear']) && !empty($member_info['birthmonth']) && !empty($member_info['birthday'])){
        $birthday = "{$member_info['birthyear']}-{$member_info['birthmonth']}-{$member_info['birthday']}";
    }
}
template('set/info');
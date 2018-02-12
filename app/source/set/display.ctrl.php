<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/18
 * Time: 21:30
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
load()->model('mc');
$member_info = mc_fetch($_W['member']['uid']);
$avatar = tomedia(AVATAR_DEFAULT_SRC);
if(!empty($member_info) && is_array($member_info)){
    if(!empty($member_info['avatar'])){
        $avatar = $member_info['avatar'];
        if(strpos($avatar,'images/') === 0){
            $avatar = tomedia($avatar);
        }
    }
}
if($do == 'display'){
    if($_W['isajax']){
        if(!empty($_FILES['avatar']['name'])){
            $upload_info = apply_upload_image_file($_FILES['avatar']);
            if($upload_info['status'] == 1){
                message("头像上传失败！{$upload_info['message']}",'','error');
            }
            if(empty($upload_info['path'])){
                message('头像上传地址错误！','','error');
            }
            if(empty($member_info) || !is_array($member_info)){
                message('会员信息不存在','','error');
            }
            $status =  mc_update($_W['member']['uid'], array(
                'avatar' => $upload_info['path'],
                'updatetime' => TIMESTAMP
            ));
            if(!$status){
                message('头像修改失败','','error');
            }
            if(!empty($member_info['avatar']) && $member_info['avatar'] != AVATAR_DEFAULT_SRC && strpos($member_info['avatar'],'images/') === 0){
                if(is_file(IA_ROOT.'/attachment/'.$member_info['avatar'])){
                    @unlink(IA_ROOT.'/attachment/'.$member_info['avatar']);
                }
            }
            message('头像修改成功',referer(),'success');
        }
    }
}
template('set/display');
<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/14
 * Time: 10:29
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
if($do == 'display'){
    load()->app('tpl');
    load()->classs('point');
    $tencent_js_key = (new Point())->getTencentJsKey();
    if($_W['isajax']){
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'store_name' => trim($_GPC['store_name']),
            'contacts' => trim($_GPC['contacts']),
            'tel' => trim($_GPC['tel']),
            'manage_content' => trim($_GPC['manage_content']),
            'saler_uid' => floor(trim($_GPC['saler_uid'])),
            'referrer_uid' => floor(trim($_GPC['referrer_uid'])),
            'type' => floor(trim($_GPC['type'])) == APPLY_TYPE_COMPANY?APPLY_TYPE_COMPANY:APPLY_TYPE_PERSON,
            'store_type' => floor(trim($_GPC['store_type'])) == STORE_TYPE_OTO?STORE_TYPE_OTO:STORE_TYPE_BBC,
            'province' => trim($_GPC['area']['province']),
            'city' => trim($_GPC['area']['city']),
            'district' => trim($_GPC['area']['district']),
            'address' => trim($_GPC['address']),
            'lat' => doubleval(trim($_GPC['lat'])),
            'lng' => doubleval(trim($_GPC['lng'])),
            'ip' => CLIENT_IP,
            'createtime' => TIMESTAMP
        );
        $error = array(
            'uid' => '请先登录！',
            'store_name' => '请输入店铺名称！',
            'contacts' => '请输入负责人姓名！',
            'tel' => '请输入联系方式！',
            'manage_content' => '请输入经营内容！',
            'province' => '请选择所在省份！',
            'city' => '请选择所在城市！',
            'district' => '请选择所在区县！',
            'address' => '请书详细地址！'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
                break;
            }
        }
        //上传营业执照
        if(!empty($_FILES['business_license']['name'])){
            $upload_business_license_info = apply_upload_image_file($_FILES['business_license']);
            if($upload_business_license_info['status'] == 1){
                message("营业执照上传失败！{$upload_business_license_info['message']}",'','error');
            }
            $data['business_license'] = $upload_business_license_info['path'];
            if(empty($data['business_license'])){
                message('营业执照上传地址错误！','','error');
            }
        }else{
            message('请上传营业执照','','error');
        }
        //上传门面
        if(!empty($_FILES['store_thumb']['name'])){
            $upload_store_thumb_info = apply_upload_image_file($_FILES['store_thumb']);
            if($upload_store_thumb_info['status'] == 1){
                message("门面照片上传失败！{$upload_store_thumb_info['message']}",'','error');
            }
            $data['store_thumb'] = $upload_store_thumb_info['path'];
            if(empty($data['store_thumb'])){
                message('门面照片上传失败！','','error');
            }
        }else{
            message('请上传门面照','','error');
        }

        //上传身份证反面
        if(!empty($_FILES['identity_card_01']['name'])){
            $upload_identity_card_01_info = apply_upload_image_file($_FILES['identity_card_01']);
            if($upload_identity_card_01_info['status'] == 1){
                message("身份证反面上传失败！{$upload_identity_card_01_info['message']}",'','error');
            }
            $data['identity_card_01'] = $upload_identity_card_01_info['path'];
            if(empty($data['identity_card_01'])){
                message('身份证反面上传地址错误！','','error');
            }
        }

        $flag =  pdo_insert('store_apply',$data);
        if(!$flag){
            message('申请提交失败！','','error');
        }
        message('申请提交成功，请耐心等待平台审核^-^',referer(),'success');
    }
}
template('mc/apply');
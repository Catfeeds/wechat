<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/3/13
 * Time: 14:00
 */
load()->model('store');
$do = in_array(trim($_GPC['do']),array('display'))?trim($_GPC['do']):'display';
if($do == 'display'){
    $item = StoreModel::getStoreInfo();
    $categoryList = StoreModel::getStoreCategory();
    if($_W['isajax']){
        if(empty($_W['store_id'])){
            message('请先登录',url('user/login'),'error');
        }
        if(empty($item)){
            message('店铺信息不存在','','error');
        }
        $data = array(
            'title' => trim($_GPC['title']),
            'logo' => trim($_GPC['logo']),
            'desc' => $_GPC['desc'],
            'category_id' => floor(trim($_GPC['category_id'])),
            'province' => isset($_GPC['area']['province'])?trim($_GPC['area']['province']):null,
            'city' => isset($_GPC['area']['city'])?trim($_GPC['area']['city']):null,
            'district' => isset($_GPC['area']['district'])?trim($_GPC['area']['district']):null,
            'address' => trim($_GPC['address']),
            'lng' => isset($_GPC['map']['lng'])?doubleval(trim($_GPC['map']['lng'])):0,
            'lat' => isset($_GPC['map']['lat'])?doubleval(trim($_GPC['map']['lat'])):0,
            'contacts' => trim($_GPC['contacts']),
            'tel' => trim($_GPC['tel']),
            'email' => trim($_GPC['email']),
            'qq' => trim($_GPC['qq']),
            'weixin' => trim($_GPC['weixin']),
            'is_display' => floor(trim($_GPC['is_display'])) == DISPLAY_YES?DISPLAY_YES:DISPLAY_NO,
            'notice' => $_GPC['notice'],
            'updatetime' => TIMESTAMP
        );
        $error = array(
            'title' => '请输入店铺标题',
            'logo' => '请选择店铺logo',
            'desc' => '请输入店铺描述',
            'category_id' => '请选择行业分类',
            'province' => '请选择店铺所在省份',
            'city' => '请选择店铺所在城市',
            'district' => '请选择店铺所在区县',
            'address' => '请输入店铺详细地址',
            'lng' => '请输入店铺所在地理经度',
            'lat' => '请输入店铺所在地理纬度',
            'contacts' => '请输入店铺联系人',
            'tel' => '请输入联系人电话'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
                break;
            }
        }
        $update_status = StoreModel::updateStoreInfo($data);
        if(!$update_status){
            message('保存失败！','','success');
        }
        message('保存成功！',referer(),'success');
    }
}
template('shop/display');
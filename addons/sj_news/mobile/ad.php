<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/9/24
 * Time: 13:34
 */
if($op == 'display'){
    checkauth();
    //广告配置
    $adConfig = pdo_get('sj_news_ad_config',array(
        'uniacid' => $_W['uniacid']
    ));
    if(!empty($adConfig['setting'])){
        $adConfig['setting'] = iunserializer($adConfig['setting']);
    }

    //系统配置
    $config = pdo_get('sj_news_config',array(
        'uniacid' => $_W['uniacid']
    ));
    $setting = array();
    if(!empty($config['setting'])){
        $setting = iunserializer($config['setting']);
    }
    if($_W['isajax']){
        //发布
        load()->model('mc');
        $member = mc_fetch($_W['member']['uid']);
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'title' => trim($_GPC['title']),
            'package_id' => floor(trim($_GPC['package_id'])),
            'industry' => trim($_GPC['industry']),
            'contact' => trim($_GPC['contact']),
            'desc' => $_GPC['desc'],
            'link' => trim($_GPC['link']),
            'province' => $_W['location']['province'],
            'city' => $_W['location']['city'],
            'district' => $_W['location']['district'],
            'address' => $_W['location']['address'],
            'lat' => $_W['location']['lat'],
            'lng' => $_W['location']['lng'],
            'is_display' => 0, //测试阶段，不需要审核
            'createtime' => TIMESTAMP
        );
        $error = array(
            'title' => '请输入标题',
            'desc' => '请输入广告描述',
            'city' => '请选择所在城市'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        $selectedAd = array();
        if(!empty($adConfig['setting'][$data['package_id']]) && is_array($adConfig['setting'][$data['package_id']])){
            $selectedAd = $adConfig['setting'][$data['package_id']];
        }
        if(!check_data($selectedAd)){
            message('选择的广告套餐不存在','','error');
        }

        //处理图片
        if (empty($_FILES['qualifications']['name'])) {
            message('请上传资质照片', '', 'error');
        }
        if (empty($_FILES['thumb']['name'])) {
            message('请选择广告图片', '', 'error');
        }

        //广告图片
        $upload_info = apply_upload_file($_FILES['thumb']);
        if ($upload_info['status'] == 1) {
            message("图片上传失败！{$upload_info['message']}", '', 'error');
        }
        $data['thumb'] = $upload_info['path'];
        if (empty($data['thumb'])) {
            message('图片上传地址错误！', '', 'error');
        }

        $upload_info2 = apply_upload_file($_FILES['qualifications']);
        if ($upload_info2['status'] == 1) {
            message("资质上传失败！{$upload_info2['message']}", '', 'error');
        }
        $data['qualifications'] = $upload_info2['path'];
        if (empty($data['qualifications'])) {
            message('资质上传地址错误！', '', 'error');
        }
        $status = pdo_insert('sj_news_ad',$data);
        $insert_ad_id = pdo_insertid();
        if(!$status || !$insert_ad_id){
            message('发布失败','','error');
        }
        //成功后跳转到支付
        $order_data = array(
            'sj_uniacid' => $_W['uniacid'],
            'sj_uid' => $_W['member']['uid'],
            'ad_id' => $insert_ad_id,
            'order_no' => generateOrderSnByBuyTodayTradeCount(),
            'price' => $selectedAd['price']*(1-0.01*$selectedAd['pay_rate']),
            'pay_goods_price' =>  $selectedAd['price']*0.01*$selectedAd['pay_rate'],
            'day' => $selectedAd['day'],
            'createtime' => TIMESTAMP
        );
        $status2 = pdo_insert('sj_news_ad_order',$order_data);
        $insert_ad_order_id = pdo_insertid();
        if(!$status2 || !$insert_ad_order_id){
            message('广告订单生成失败','','error');
        }
        $log_data = array(
            'order_ids' => $insert_ad_order_id,
            'out_trade_no' => $order_data['order_no'],
            'pay_price' => $order_data['price'],
            'order_type' => ORDER_TYPE_SJ_NEWS_AD,
            'createtime' => TIMESTAMP
        );
        $status3 = pdo_insert('pay_log',$log_data);
        $insert_log_id = pdo_insertid();
        if(!$status3 || !$insert_log_id){
            message('支付记录生成失败','','error');
        }
        message('发布成功',"{$_W['siteroot']}app/index.php?i=7&c=mc&a=pay&do=display&id={$insert_log_id}&wxref=mp.weixin.qq.com",'success');
    }
}
include $this->template('ad');
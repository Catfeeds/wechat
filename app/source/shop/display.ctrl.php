<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/6/1
 * Time: 18:34
 */
defined('IN_IA') or exit('Access Denied');
if($do == 'display'){
    load()->classs('point');
    $baidu_key = (new Point())->getBaiduJsAk();
    $item = pdo_get('fangyuanbao_shop_list',array(
        'uniacid'=>$_W['uniacid'],
        'uid'=>$_W['member']['uid']
    ));
    $member = pdo_get('mc_members',array(
        'uniacid'=>$_W['uniacid'],
        'uid'=>$_W['member']['uid']
    ));
}elseif($do == 'point'){
    load()->classs('point');
    $key = (new Point())->getTencentServerKey();
    $back_url = urlencode("{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=shop&a=display&do=develop");
    $select_url = "http://apis.map.qq.com/tools/locpicker?search=1&type=0&backurl={$back_url}&key={$key}&referer=myapp";
    header('location:'.$select_url);
}elseif($do == 'develop'){
    list($lat,$lng) = explode(',',$_GPC['latng']);
    load()->classs('point');
    $area = (new Point())->getTencentAddressByLatLng($lat,$lng);
    $info = array(
        'uniacid'=> $_W['uniacid'],
        'uid'=>$_W['member']['uid'],
        'point_name' => $_GPC['name'],
        'point_city' => $_GPC['city'],
        'point_address' => $_GPC['addr'],
        'lat' => $lat,
        'lng' => $lng,
        'province' => $area['province'],
        'city' => $area['city'],
        'district' => $area['district'],
        'address' => $area['address']
    );
    if($_W['isajax']){
        $member = pdo_get('mc_members',array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid']
        ));
        if(empty($member['city']) || empty($member['province']) ||empty($member['district'])){
            message('请先设置所在地',url('set/location/display'),'error');
        }
        $shop = pdo_get('fangyuanbao_shop_list',array(
            'uniacid'=>$_W['uniacid'],
            'uid'=>$_W['member']['uid']
        ));
        if(check_data($shop)){
            message('您已开通垂直仓储店，无需重复开通','','error');
        }
        $config = pdo_getcolumn('fangyuanbao_shop_config',array(
            'uniacid'=>$_W['uniacid']
        ),'setting');
        if(empty($config)){
            message('平台未设置配置信息','','error');
        }
        $setting = iunserializer($config);
        if(!check_data($setting)){
            message('平台未设置配置信息','','error');
        }
        if($setting['status'] != OPEN_STATUS){
            message('状态未开启','','error');
        }
        $order_no = generateOrderSnByBuyTodayTradeCount();
        $info['order_no'] = $order_no;
        $info['createtime'] = TIMESTAMP;
        $info['pay_price'] = $setting['develop_fee'];
        $info['parent_uid'] = floor(trim($_GPC['parent_uid']));
        if(!check_id($info['parent_uid'])){
            message('请输入所属会员ID','','error');
        }
        if(!empty($info['parent_uid'])){
            $parent = pdo_get('fangyuanbao_shop_user',array(
                'uniacid'=>$_W['uniacid'],
                'uid'=>$info['parent_uid']
            ));
            if(!check_data($parent)){
                message('所属会员信息不存在或未开店','','error');
            }
        }
        if(!is_numeric($info['pay_price']) || $info['pay_price']<=0){
            message('订单金额错误','','error');
        }
        pdo_begin();
        $status = pdo_insert('fangyuanbao_shop_order',$info);
        if(!$status){
            pdo_rollback();
            message('订单提交失败','','error');
        }
        $order_id = pdo_insertid();
        if(!$order_id){
            pdo_rollback();
            message('订单提交失败','','error');
        }
        //插入支付记
        $pay_log_data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'order_ids' => $order_id,
            'out_trade_no' => $order_no,
            'pay_price' => $info['pay_price'],
            'order_type' => ORDER_TYPE_DEVELOP_SHOP,
            'createtime' => TIMESTAMP
        );
        $status2 = pdo_insert('pay_log',$pay_log_data);
        if(!$status2){
            pdo_rollback();
            message('订单提交失败','','error');
        }
        $log_id = pdo_insertid();
        if(!$log_id){
            pdo_rollback();
            message('订单提交失败','','error');
        }
        pdo_commit();
        message('提交成功，正在跳转',url('mc/pay/display',array('id'=>$log_id)),'success');
    }
}elseif($do == 'focus'){
    //关注人数
    $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_shop_user')." WHERE uniacid='{$_W['uniacid']}'");
    $team = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_shop_user')." WHERE uniacid='{$_W['uniacid']}' AND (relation REGEXP '^{$_W['member']['uid']}-' OR relation REGEXP '-{$_W['member']['uid']}-')");
    $one = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_shop_user')." WHERE uniacid='{$_W['uniacid']}' AND relation REGEXP '^{$_W['member']['uid']}-'");
    $two = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_shop_user')." WHERE uniacid='{$_W['uniacid']}' AND relation REGEXP '^[0-9]+-{$_W['member']['uid']}-'");
    $three = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_shop_user')." WHERE uniacid='{$_W['uniacid']}' AND relation REGEXP '^[0-9]+-[0-9]+-{$_W['member']['uid']}-'");
    $four = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_shop_user')." WHERE uniacid='{$_W['uniacid']}' AND relation REGEXP '^[0-9]+-[0-9]+-[0-9]+-{$_W['member']['uid']}-'");
    $five = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_shop_user')." WHERE uniacid='{$_W['uniacid']}' AND relation REGEXP '^[0-9]+-[0-9]+-[0-9]+-[0-9]+-{$_W['member']['uid']}-'");
}elseif($do == 'focus_log'){
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.avatar,b.realname FROM ".tablename('fangyuanbao_shop_user')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE a.uniacid='{$_W['uniacid']}' AND (a.relation REGEXP '^{$_W['member']['uid']}-' OR a.relation REGEXP '-{$_W['member']['uid']}-')");
}
template('shop/display');
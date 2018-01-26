<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/16
 * Time: 10:17
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($do)?$do:'display';
$store_type = floor(trim($_GPC['store_type'])) == STORE_TYPE_BBC?STORE_TYPE_BBC:STORE_TYPE_OTO;
load()->model('mc');
$uid = $_W['member']['uid'];
if(empty($uid)){
    $uid = floor(trim($_GPC['uid']));
}
$member_info = mc_fetch($uid,array('avatar','nickname'));
$nickname = !empty($member_info['nickname'])?$member_info['nickname']:'';
$avatar = IA_ROOT."/assets/common/images/poster/{$_W['uniacid']}.jpg";
if(!file_exists($avatar)){
    $avatar = IA_ROOT."/assets/common/images/poster/{$_W['uniacid']}.png";
}
if(!file_exists($avatar)){
    $avatar = IA_ROOT.'/attachment/images/global/avatars/avatar_1.jpg';
}
if($do != 'image'){
    $goods_list = pdo_fetchall("SELECT * FROM ".tablename('goods_list')." WHERE uniacid='{$_W['uniacid']}' AND store_type='{$store_type}' AND total >0 AND is_display=".DISPLAY_YES." AND is_check=".CHECK_PASS." ORDER BY platform_order_by DESC LIMIT 20");
    foreach($goods_list as $k => &$v){
        $v['sale_price'] = getGoodsTruePrice($v);
        $v['thumb'] = tomedia($v['thumb']);
        if($store_type == STORE_TYPE_OTO){
            $v['link'] = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&id={$v['id']}&do=detail&m=oto";
        }else{
            $v['link'] = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&do=index&m=shop#/productDetail/{$v['id']}";
        }
    }
}

if($do == 'platform'){ //生成关注海报
    $tip = "扫一扫，关注公众号";
    $img = getWechatFocusImage();
    $item = pdo_get('uni_settings',array(
        'uniacid' => $_W['uniacid']
    ));
    if(!empty($item) && is_array($item)){
        if(!empty($item['poster'])){
            $config = iunserializer($item['poster']);
        }
    }
    if(!empty($config)){
        load()->classs('poster');
        $poster = new Poster(iunserializer($config));
        $poster -> getPlatformPosterImage($avatar,$nickname,$img);
    }
    message('平台未设置参数','','error');
    exit;
}elseif($do == 'store'){ //生成店铺海报
    $tip = "扫一扫，进入店铺";
    $id = floor(trim($_GPC['id']));
    if($store_type == STORE_TYPE_OTO){
        $link = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&id={$id}&do=store&m=oto";
    }else{
        $link = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&do=index&m=shop#/shops/{$id}";
    }
    $link = urlencode($link);
    $img = url('mc/poster/image')."&ps={$link}";
}elseif($do == 'goods'){ //生成商品海报
    $tip = "扫一扫，查看商品";
    $id = floor(trim($_GPC['id']));
    if($store_type == STORE_TYPE_OTO){
        $link = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&id={$id}&do=detail&m=oto";
    }else{
        $link = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&do=index&m=shop#/productDetail/{$id}";

    }
    $link = urlencode($link);
    $img = url('mc/poster/image')."&ps={$link}";
}elseif($do == 'image'){ //生成二维码
    require_once(IA_ROOT.'/framework/library/qrcode/phpqrcode.php');
    $errorCorrectionLevel = "L";
    $matrixPointSize = "8";
    $text = $_GPC['ps'];
    QRcode::png($text, false, $errorCorrectionLevel, $matrixPointSize);
    exit();
}elseif($do == 'show_poster'){
    $_share['imgUrl'] = "{$_W['siteroot']}app/resource/images/default_friend.jpg";
    $_share['link'] = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=mc&a=poster&do=show_poster&parent_uid={$_W['member']['uid']}";
    if(!empty($_GPC['parent_uid'])){
        $parent_uid = floor(trim($_GPC['parent_uid']));
        updateRelation($_W['member']['uid'],$parent_uid);
    }
    $img = url('mc/poster/platform',array('uid'=>$_W['member']['uid']));
}
template('mc/poster');


<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/18
 * Time: 15:52
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
if($do == 'display'){
    $page = max(1,floor(trim($_GPC['page'])));
    $type = floor(trim($_GPC['type'])) == COLLECT_TYPE_STORE?COLLECT_TYPE_STORE:COLLECT_TYPE_GOODS;
    $psize = 50;
    $pindex = ($page-1)*$psize;
    if($type == COLLECT_TYPE_STORE){
        $list = pdo_fetchall("SELECT a.id,a.type,a.store_type,b.id AS flag_id,b.collect_count,b.logo,b.province,b.city,b.title FROM ".tablename('mc_member_collect')." a LEFT JOIN ".tablename('store_list')." b ON a.flag_id=b.id WHERE a.uniacid='{$_W['uniacid']}' AND a.uid='{$_W['member']['uid']}' AND a.`type`=".COLLECT_TYPE_STORE." ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}");
    }else{
        $list = pdo_fetchall("SELECT a.id,a.type,a.store_type,b.id AS flag_id,b.thumb,b.market_price,b.sale_price,b.title FROM ".tablename('mc_member_collect')." a LEFT JOIN ".tablename('goods_list')." b ON a.flag_id=b.id WHERE a.uniacid='{$_W['uniacid']}' AND a.uid='{$_W['member']['uid']}' AND a.`type`=".COLLECT_TYPE_GOODS." ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}");
    }
    if(!empty($list) && is_array($list)){
        foreach($list as $k => &$v){
            if(isset($v['logo'])){
                $v['logo'] = tomedia($v['logo']);
            }
            if(isset($v['thumb'])){
                $v['thumb'] = tomedia($v['thumb']);
            }
            if($v['type'] == COLLECT_TYPE_STORE){
                //店铺
                if($v['store_type'] == STORE_TYPE_BBC){
                    $v['link'] = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&do=index&m=shop#/shops/{$v['flag_id']}";
                }else{
                    $v['link'] = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&id={$v['flag_id']}&do=store&m=oto";
                }
            }else{
                //商品
                if($v['store_type'] == STORE_TYPE_BBC){
                    $v['link'] = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&do=index&m=shop#/productDetail/{$v['flag_id']}";
                }else{
                    $v['link'] = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&id={$v['flag_id']}&do=detail&m=oto";
                }
            }
        }
    }
    if($_W['isajax']){
        if(!empty($list) && is_array($list)){
            message($list,'','success');
        }
        message("没有更多".($type == COLLECT_TYPE_STORE?'店铺':'商品'),'','error');
    }
}
template('mc/collect');
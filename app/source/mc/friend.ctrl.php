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
    $level = floor(trim($_GPC['type']));
    $psize = 50;
    $pindex = ($page-1)*$psize;
    load()->model('common');
    $list = CommonModel::getTeamByLevel($_W['member']['uid'],$level,$pindex,$psize);
    if(!empty($list) && is_array($list)){
        foreach($list as $k => &$v){
            $star_level = pdo_fetchcolumn("SELECT product_key FROM ".tablename('fangyuanbao_user')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$v['uid']}'");
            if($star_level == 1){
                $v['level'] = 1;
            }elseif($star_level == 2){
                $v['level'] = 3;
            }elseif($star_level == 3){
                $v['level'] = 5;
            }else{
                $v['level'] = 0;
            }
        }
    }
    if($_W['isajax']){
        if(!empty($list) && is_array($list)){
            message($list,'','success');
        }
        message('没有更多好友','','error');
    }
}
template('mc/friend');
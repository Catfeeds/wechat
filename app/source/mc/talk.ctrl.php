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
    if($_W['isajax']){
        if(!empty($list) && is_array($list)){
            message($list,'','success');
        }
        message('没有更多好友','','error');
    }
}
template('mc/talk');
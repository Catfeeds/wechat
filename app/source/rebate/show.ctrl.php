<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/6/4
 * Time: 20:53
 */
load()->func('check');
if($do == 'display'){
    //方圆宝形成
    $endtime = strtotime(date('Y-m-d 23:59:59'));
    $starttime =  $endtime - 5*24*3600;
    $list = pdo_fetchall("SELECT a.createtime,b.nickname,b.avatar FROM ".tablename('fangyuanbao_queue')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE a.uniacid='7' AND a.createtime BETWEEN {$starttime} AND {$endtime} ORDER BY a.createtime DESC");
}elseif($do == 'list'){
    //方圆宝兑换
    $endtime = strtotime(date('Y-m-d 23:59:59'));
    $starttime =  $endtime - 8*24*3600;
    $list = pdo_fetchall("SELECT a.uid,a.createtime,a.old_nickname,a.old_province,a.old_city,a.old_district,a.type,a.birthtime,b.nickname,b.avatar,b.city,b.district FROM ".tablename('fangyuanbao_rebate')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE a.uniacid='7' AND a.createtime BETWEEN {$starttime} AND {$endtime} ORDER BY a.createtime DESC");
    if(check_data($list)){
        foreach($list as $k => &$v){
            if($v['type'] == 1){
                $v['nickname'] = $v['old_nickname'];
                $v['city'] = $v['old_city'];
                $v['district'] = $v['old_district'];
                $v['avatar'] = pdo_fetchcolumn("SELECT avatar FROM ".tablename('a_mc_members')." WHERE uniacid='306' AND uid='{$v['uid']}'");
            }
        }
    }
}elseif($do == 'shop'){
    //开店列表
    $endtime = strtotime(date('Y-m-d 23:59:59'));
//    $starttime =  $endtime - 8*24*3600;
    $starttime = 0;
    $list = pdo_fetchall("SELECT a.*,b.nickname,b.avatar,b.city FROM ".tablename('fangyuanbao_shop_user')." a LEFT JOIN ".tablename('mc_members')." b ON a.uid=b.uid WHERE a.uniacid='7' AND a.createtime BETWEEN {$starttime} AND {$endtime} ORDER BY a.createtime DESC");
}
template('rebate/show');
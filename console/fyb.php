<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/7/23
 * Time: 12:10
 */
//1500782915
exit;
header("Content-Type:text/html;charset=UTF-8");
require_once '../framework/bootstrap.inc.php';
require_once 'libs/global.func.php';
load()->func('check');
load()->func('notice');
$page = getApartPageNo('page');
$psize = 100;
$pindex = ($page - 1)*$psize;
echo '当前页'.$page.'<br>';
//方圆宝模式
$list = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='7' AND id<13736 AND status=0 AND createtime<=1500782915 ORDER BY createtime ASC LIMIT {$pindex},{$psize}");
if(!check_data($list)){
    message('方圆宝队列不存在','','error');
}
foreach($list as $k => $fyb){
    $member = pdo_get('mc_members',array(
        'uniacid' => 7,
        'uid' => $fyb['uid']
    ));
    $first_queue_no = pdo_fetchcolumn("SELECT MAX(queue_no) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='7'");
    $first_queue_no +=1;
    for($i = 0;$i< 5;$i++){
        //新增方圆宝
        pdo_insert('fangyuanbao_queue',array(
            'uniacid'=>7,
            'uid' => $fyb['uid'],
            'count'=>1,
            'queue_no'=>$first_queue_no++,
            'province' => $member['province'],
            'city'=>$member['city'],
            'district' => $member['district'],
            'createtime' => $fyb['createtime']
        ));
    }
    pdo_delete('fangyuanbao_queue',array(
        'uniacid'=>7,
        'id' => $fyb['id']
    ));
}
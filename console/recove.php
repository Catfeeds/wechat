<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/7/23
 * Time: 12:10
 */

header("Content-Type:text/html;charset=UTF-8");
require_once '../framework/bootstrap.inc.php';
require_once 'libs/global.func.php';
load()->func('check');
load()->func('notice');
$uid=45;
$member = pdo_get('mc_members',array(
    'uniacid' => 7,
    'uid' => $uid
));
for($i=1;$i<175;$i++){
    $first_queue_no = pdo_fetchcolumn("SELECT MAX(queue_no) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='7'");
    $first_queue_no +=1;
    //新增方圆宝
    $status = pdo_insert('fangyuanbao_queue',array(
        'uniacid'=>7,
        'uid' => $uid,
        'count'=>1,
        'queue_no'=>$first_queue_no++,
        'province' => $member['province'],
        'city'=>$member['city'],
        'district' => $member['district'],
        'createtime' => 1496592000+$i*6*3600
    ));
    if($status){
        echo "会员{$uid}新增第{$i}个方圆宝，序号：{$first_queue_no},日期:".date('Y-m-d H:i')."<br>";
    }
}
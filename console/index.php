<?php
header("Content-Type:text/html;charset=UTF-8");
require_once '../framework/bootstrap.inc.php';
require_once 'libs/global.func.php';
load()->func('check');
$list = pdo_fetchall("SELECT uid,birth,2*level AS count,createtime FROM ".tablename('a_rebate_insur_rebate')." WHERE uniacid='306' AND types='fund' AND oktime='' ORDER BY createtime ASC");
foreach($list as $k => &$li){
    $area = json_decode($li['birth'],true);
    $li['province'] = $area['province'];
    $li['city'] = $area['city'];
    $li['district'] = $area['district'];
    $li['new_uid'] = pdo_fetchcolumn("SELECT uid FROM ".tablename('mc_check')." WHERE uniacid='7' AND old_mobile!='' AND old_uid='{$li['uid']}' LIMIT 1");
    unset($li['birth']);
}
export_list($list);
function export_list($list = array()){
    if(!empty($list) && is_array($list)){
        $index = 1;
        foreach($list as $k => &$v){
            $v['no'] = $index++;
            if(!empty($v['createtime'])){
                $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
            }
        }
    }else{
        message('导出的数据不存在！','','error');
    }
    load()->classs('export');
    $excel = new ExcelTool();
    $columns =array(
        array(
            "title" => '序号',
            "field" => "no",
            "width" => 10
        ) ,
        array(
            "title" => '时间',
            "field" => "createtime",
            "width" => 20
        ),
        array(
            "title" => '老会员ID',
            "field" => "uid",
            "width" => 10
        ) ,
        array(
            "title" => '新会员ID',
            "field" => "new_uid",
            "width" => 10
        ) ,
        array(
            "title" => '数量',
            "field" => "count",
            "width" => 20
        ) ,
        array(
            "title" => '已发放数量',
            "field" => "district",
            "width" => 10
        ),
        array(
            "title" => '省份',
            "field" => "province",
            "width" => 20
        ) ,
        array(
            "title" => '城市',
            "field" => "city",
            "width" => 40
        ) ,
        array(
            "title" => '区县',
            "field" => "district",
            "width" => 10
        )
    );
    $excel->export(
        $list,//导出的数据数组
        array(
            "title" => "方圆宝-" . date("Y-m-d-H-i", time()) , //标题
            "columns" => $columns //数据列
        )
    );
}
//$uniacid = 7;
//$users = array(
//    '814992' => 43,
//    '820200' => 1,
//    '820107' => 1,
//    '820106' => 2,
//    '820105' => 1,
//    '820104' => 1,
//    '820099' => 1,
//    '820098'=>1,
//    '698862'=>1,
//    '811294'=>1,
//    '814084'=>1,
//    '810541'=>7,
//    '806093' => 5,
//    '811657' => 2,
//    '789534' => 3,
//    '802691' => 4,
//    '805915' => 4,
//    '807292'=>5,
//    '804594' => 1,
//    '805409'=>1,
//    '807626'=>1,
//    '803692'=>5,
//    '805134'=>1,
//    '807697'=>9,
//    '803769'=>2,
//    '807715'=>6,
//    '807570'=>1,
//    '734457'=>2,
//    '805115'=>2,
//    '804014'=>2,
//    '806326'=>1,
//    '807809'=>4,
//    '805156'=>3,
//    '807905'=>3,
//    '803641'=>1,
//    '804976'=>1,
//    '804414'=>2,
//    '808079'=>2,
//    '801814'=>3,
//    '751226'=>1,
//    '755171'=>3,
//    '792645'=>1,
//    '681594'=>1,
//    '824304'=>1
//);
//$no = pdo_fetchcolumn("SELECT MAX(queue_no) FROM ".tablename('fangyuanbao_queue'));
//$no++;
//foreach($users as $uid => $count){
//    $birth = pdo_fetchcolumn("SELECT birth FROM ".tablename('a_rebate_insur_rebate')." WHERE uid='{$uid}' LIMIT 1");
//    $new_uid = pdo_fetchcolumn("SELECT uid FROM ".tablename('mc_check')." WHERE uniacid='7' AND old_uid='{$uid}' AND old_mobile!='' AND uid!='' LIMIT 1");
//    if(!empty($birth)){
//        $birth = json_decode($birth,true);
//    }
//    for($i = 0;$i< 2*$count;$i++){
//        //新增方圆宝
//       pdo_insert('fangyuanbao_queue',array(
//            'uniacid'=>7,
//            'uid' => !empty($new_uid)?$new_uid:$uid,
//            'count'=>1,
//            'queue_no'=>$no,
//            'province' => !empty($birth['province'])?$birth['province']:'',
//            'city'=>!empty($birth['city'])?$birth['city']:'',
//            'district' => !empty($birth['district'])?$birth['district']:'',
//            'createtime' => TIMESTAMP,
//           'is_used' => !empty($new_uid)?1:0
//        ));
//        $no ++;
//    }
//}
//





//$users = array(
//    '814992' => 3,
//    '820200' => 55,
//    '820107' => 37,
//    '820106' => 10,
//    '820105' => 5,
//    '820104' => 33,
//    '820098' => 27,
//    '698862'=>61,
//    '814084'=>3,
//    '810541'=>94,
//    '811657'=>2,
//    '789534'=>20,
//    '805915' => 45,
//    '807292' => 84,
//    '805409' => 45,
//    '803692' => 10,
//    '807697' => 4,
//    '803769'=>2,
//    '807715' => 16,
//    '807570'=>2,
//    '734457'=>11,
//    '807809'=>8,
//    '805156'=>17,
//    '803641'=>1,
//    '751226'=>2,
//    '792645'=>45,
//    '681594'=>6
//);
//$uids = array_keys($users);
//$list = pdo_fetchall("select uid,sum(level) AS count,birth from ims_a_rebate_insur_rebate where oktime='' and types='fund' and level>0 group by uid");
//$no = 1;
//foreach($list as $k => $v){
//    if(!empty($v['birth'])){
//        $birth = json_decode($v['birth'],true);
//    }
//    if(in_array($v['uid'],$uids)){
//        $v['count'] = $users[$v['uid']];
//    }
//    for($i = 0;$i< 2*$v['count'];$i++){
//        //新增方圆宝
//       pdo_insert('fangyuanbao_queue',array(
//            'uniacid'=>7,
//            'uid' => $v['uid'],
//            'count'=>1,
//            'queue_no'=>$no,
//            'province' => !empty($birth['province'])?$birth['province']:'',
//            'city'=>!empty($birth['city'])?$birth['city']:'',
//            'district' => !empty($birth['district'])?$birth['district']:'',
//            'createtime' => TIMESTAMP,
//           'is_used' => !empty($new_uid)?1:0
//        ));
//        $no ++;
//    }
//}

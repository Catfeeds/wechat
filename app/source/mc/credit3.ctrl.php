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
    $psize = 50;
    $type = floor(trim($_GPC['type']));
    $pindex = ($page-1)*$psize;
    $where = "uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND credittype='credit3'";
    if($type == 1){
        $where .= ' AND num>0';
    }elseif($type == 2){
        $where .= ' AND num<0';
    }
    $where .= " ORDER BY createtime DESC";
    $list = pdo_fetchall("SELECT * FROM ".tablename('mc_credits_record')." WHERE {$where} LIMIT {$pindex},{$psize}");
    if($_W['isajax']){
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
                $v['span_class'] = $v['num']>0?'success':'danger';
                $v['span_desc'] = $v['num'] >0?'收入':'支出';
                $v['num'] = $v['num'] >0?'+'.$v['num']:$v['num'];
            }
            message($list,'','success');
        }
        message('没有更多信息','','error');
    }
}
template('mc/credit3');
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
    if($_W['isajax']){
        $page = max(1,floor(trim($_GPC['page'])));
        $psize = 50;
        $pindex = ($page-1)*$psize;
        $where = "uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'";
        $where .= " ORDER BY createtime DESC";
        $list = pdo_fetchall("SELECT * FROM ".tablename('mc_member_suggest')." WHERE {$where} LIMIT {$pindex},{$psize}");
        if($_W['isajax']){
            if(!empty($list) && is_array($list)){
                foreach($list as $k => &$v){
                    $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
                    $v['span_class'] = $v['is_check'] == CHECK_PASS?'success':'danger';
                    $v['span_desc'] = $v['is_check'] == CHECK_PASS?'已查看':'未查看';
                    $v['reply'] = empty($v['reply'])?'未回复':$v['reply'];
                }
                message($list,'','success');
            }
            message('没有更多信息','','error');
        }
    }
}elseif($do == 'suggest'){
    if($_W['isajax']){
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'content' => $_GPC['content'],
            'ip' => CLIENT_IP,
            'createtime' => TIMESTAMP
        );
        if(mb_strlen($data['content'],'utf-8')<10){
            message('内容不能少于10个字','','error');
        }
        $status = pdo_insert('mc_member_suggest',$data);
        if(!$status){
            message('提交失败','','error');
        }
        message('谢谢您的反馈，祝您生活愉快^-^','','success');
    }
    message('请求方式错误','','error');
}
template('mc/suggest');
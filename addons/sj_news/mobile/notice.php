<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/9/24
 * Time: 13:34
 */
checkauth();
load()->model('mc');
if($op == 'display'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $list = pdo_fetchall("SELECT a.*,b.uid AS looked_uid FROM ".tablename('sj_news_notice')." a LEFT JOIN ".tablename('sj_news_notice_looked')." b ON a.id=b.notice_id AND b.uid='{$_W['member']['uid']}' WHERE a.uniacid='{$_W['uniacid']}' ORDER BY a.id DESC LIMIT {$pindex},{$psize}");
    $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('sj_news_notice')." WHERE uniacid='{$_W['uniacid']}'");
    $pager = mobilePagination($total,$page,$psize);
    include $this->template('notice');
}else{
    $id = floor(trim($_GPC['id']));
    $item = array();
    if(check_id($id)){
        $item = pdo_get('sj_news_notice',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    if(!check_data($item)){
        message('公告不存在','','error');
    }
    $looked_info = pdo_get('sj_news_notice_looked',array(
        'uniacid' => $_W['uniacid'],
        'uid' => $_W['member']['uid'],
        'notice_id' => $id
    ));
    if(!check_data($looked_info)){
        pdo_insert('sj_news_notice_looked',array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'notice_id' => $id,
            'ip' => CLIENT_IP,
            'createtime' => TIMESTAMP
        ));
    }
    pdo_query("UPDATE ".tablename('sj_news_notice')." SET look_num=look_num+1 WHERE uniacid='{$_W['uniacid']}' AND id='{$id}'");
    include $this->template('notice_detail');
}

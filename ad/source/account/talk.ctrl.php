<?php
load()->func('check');
$do = empty($_GPC['do'])?'display':trim($_GPC['do']);
if($do == 'display'){
    if($_W['isajax']){
        if(empty($_GPC['ids'])){
            message('请选择要删除的评论','','error');
        }
        $ids = implode(',',$_GPC['ids']);
        $delete_status = pdo_query("DELETE FROM ".tablename('sj_news_talk')." WHERE uniacid='{$_W['uniacid']}' AND id IN ($ids)");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $where = "a.uniacid='{$_W['uniacid']}'";
    if(!empty($keyword)){
        $where .= " AND a.content LIKE '%{$keyword}%'";
    }
    if($_W['ad_type'] != 1){
        $where .= " AND a.news_id IN (SELECT id FROM ".tablename('sj_news_list')." WHERE uniacid='{$_W['uniacid']}' AND province='{$_W['province']}' AND city='{$_W['city']}')";
    }
    $list = pdo_fetchall("SELECT a.*,b.province.b.city FROM ".tablename('sj_news_talk')." a LEFT JOIN ".tablename('sj_news_list')." b ON a.news_id=b.id WHERE {$where} ORDER BY a.id DESC LIMIT {$pindex},{$psize}");
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('sj_news_talk')." WHERE {$where}"),$page,$psize);
}

if($do == 'post'){
    $id = floor(trim($_GPC['id']));
    $status = pdo_update('sj_news_talk',array(
        'is_display' => 1,
        'updatetime' => TIMESTAMP
    ),array(
        'uniacid' => $_W['uniacid'],
        'id' => $id
    ));
    if(!$status){
        message('通过失败','','error');
    }
    message('通过成功',referer(),'success');
}
template('account/talk');
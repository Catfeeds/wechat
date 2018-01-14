<?php
load()->func('check');
$do = empty($_GPC['do'])?'display':trim($_GPC['do']);
if($do == 'display'){
    if($_W['isajax']){
        if(empty($_GPC['ids'])){
            message('请选择要删除的建议','','error');
        }
        $ids = implode(',',$_GPC['ids']);
        $delete_status = pdo_query("DELETE FROM ".tablename('sj_news_suggest')." WHERE uniacid='{$_W['uniacid']}' AND id IN ($ids)");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $where = "uniacid='{$_W['uniacid']}'";
    if(!empty($keyword)){
        $where .= " AND (name LIKE '%{$keyword}%' OR content LIKE '%{$keyword}%')";
    }
    if($_W['ad_type'] != 1){
        $where .= " AND uid IN (SELECT uid FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND province='{$_W['province']}' AND city='{$_W['city']}')";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('sj_news_suggest')." WHERE {$where} ORDER BY id DESC LIMIT {$pindex},{$psize}");
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('sj_news_suggest')." WHERE {$where}"),$page,$psize);
}
template('suggest/display');
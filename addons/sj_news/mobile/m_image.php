<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/9/24
 * Time: 13:34
 */
checkauth();
load()->model('mc');
$member = mc_fetch($_W['member']['uid']);
if($op == 'display'){
    if($_W['isajax']){
        load()->func('check');
        $id = floor(trim($_GPC['id']));
        if(!check_id($id)){
            message('图文ID错误','','error');
        }
        $status = pdo_delete('sj_news_list',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id,
            'uid' => $member['uid']
        ));
        if(!$status){
            message('删除失败','','error');
        }
        pdo_delete('sj_news_talk',array(
            'uniacid' => $_W['uniacid'],
            'news_id' => $id
        ));
        message('删除成功',referer(),'success');
    }
    $page = getApartPageNo();
    $psize = 100;
    $pindex = ($page-1)*$psize;
    $list = pdo_fetchall("SELECT * FROM ".tablename('sj_news_list')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$member['uid']}' AND type=2 ORDER BY id DESC LIMIT {$pindex},{$psize}");
    if(empty($list)){
        message('您还尚未发布图文','','error');
    }
}
include $this->template('m_image');
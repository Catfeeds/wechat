<?php
load()->func('check');
$do = empty($_GPC['do'])?'display':trim($_GPC['do']);
if($do == 'display'){
    if($_W['isajax']){
        if(empty($_GPC['ids'])){
            message('请选择要删除的通知','','error');
        }
        $ids = implode(',',$_GPC['ids']);
        $delete_status = pdo_query("DELETE FROM ".tablename('sj_news_notice')." WHERE uniacid='{$_W['uniacid']}' AND id IN ($ids)");
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
        $where .= " AND title LIKE '%{$keyword}%'";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('sj_news_notice')." WHERE {$where} ORDER BY id DESC LIMIT {$pindex},{$psize}");
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('sj_news_notice')." WHERE {$where}"),$page,$psize);
}elseif($do == 'post'){
    $id = floor(trim($_GPC['id']));
    $item = array();
    if(check_id($id)){
        $item = pdo_get('sj_news_notice',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'detail' => $_GPC['detail']
        );
        $error = array(
            'title' => '请输入标题',
            'detail' => '请输入详情',
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        if(empty($item)){ //插入数据
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $status = pdo_insert('sj_news_notice',$data);
            $tip = "添加";
        }else{
            //更新数据
            $data['updatetime'] = TIMESTAMP;
            $status = pdo_update('sj_news_notice',$data,array(
                'uniacid' => $_W['uniacid'],
                'id' => $id
            ));
            $tip = "修改";
        }
        if(!$status){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
}
template('notice/display');
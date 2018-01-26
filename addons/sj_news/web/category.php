<?php
if($op == 'display'){
    //商家分类列表
    $keyword = trim($_GPC['keyword']);
    $is_display = $_GPC['is_display'];
    $where = "uniacid='{$_W['uniacid']}'";
    if(!empty($keyword)){
        $where.=" AND title LIKE '%{$keyword}%'";
    }
    if(check_data($is_display)){
        $where .= " AND is_display IN (".implode(',',$is_display).")";
    }
    $psize = 15;
    $pindex = (max(1,floor(trim($_GPC['page'])))-1)*$psize;
    $list = pdo_fetchall("SELECT * FROM ".tablename('sj_news_category')." WHERE {$where} LIMIT {$pindex},{$psize}");
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('sj_news_category')." WHERE {$where}"),floor(trim($_GPC['page'])),$psize);
}else{
    //添加、删除、修改商家分类
    if(trim($_GPC['ac']) == 'delete'){
        $ids = $_GPC['ids'];
        if(!check_data($ids)){
            message('请选择要删除的分类','','error');
        }
        $delete_status = pdo_query("DELETE FROM ".tablename('sj_news_category')." WHERE uniacid='{$_W['uniacid']}' AND id IN (".implode(',',$ids).")");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $id = floor(trim($_GPC['id']));
    if(!empty($id)) {
        $item = pdo_get('sj_news_category', array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'is_display' => floor(trim($_GPC['is_display'])) == DISPLAY_YES?DISPLAY_YES:DISPLAY_NO,
            'order_by' => floor(trim($_GPC['order_by']))
        );
        if(empty($data['title'])){
            message('请输入分类标题！','','error');
        }
        if(empty($item)){
            $is_exist_category = pdo_get('sj_news_category',array(
                'uniacid' => $_W['uniacid'],
                'title' => $data['title']
            ));
            if(!empty($is_exist_category)){
                message('分类已经存在！','','error');
            }
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $flag = pdo_insert('sj_news_category',$data);
            $tip = "添加";
        }else{
            $data['updatetime'] = TIMESTAMP;
            $flag = pdo_update('sj_news_category',$data,array(
                'uniacid' => $_W['uniacid'],
                'id' => $id
            ));
            $tip = "修改";
        }
        if(!$flag){
            message("{$tip}失败！",'','error');
        }
        message("{$tip}成功！",referer(),'success');
    }
}
include $this->template('category');
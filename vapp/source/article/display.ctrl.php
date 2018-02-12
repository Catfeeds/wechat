<?php
if(empty($do)){$do = 'display';}

//栏目列表
$company_where = "AND company_id IN (SELECT id FROM ".tablename('vapp_company')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['user_id']}')";
if($do == 'display'){
    if($_W['isajax']){
        if(!check_data($_GPC['ids'])){
            message('请选择要删除的分类','','error');
        }
        $delete_status = pdo_query("DELETE FROM ".tablename('vapp_article_category')." WHERE uniacid='{$_W['uniacid']}' {$company_where} AND id IN (".implode(',',$_GPC['ids']).")");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('vapp_article_category')." WHERE uniacid='{$_W['uniacid']}' {$company_where} ORDER BY order_by DESC LIMIT 0,50");
}

//提交栏目
if($do == 'post'){
    $companies = pdo_fetchall("SELECT * FROM ".tablename('vapp_company')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['user_id']}' ORDER BY order_by DESC LIMIT 0,50",array(),'id');
    $id = floor(trim($_GPC['id']));
    $item = array();
    if(check_id($id)){
        $item = pdo_fetch("SELECT * FROM ".tablename('vapp_article_category')." WHERE uniacid='{$_W['uniacid']}' {$company_where} AND id='{$id}'");
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'is_display' => floor(trim($_GPC['is_display'])) == 1?1:0,
            'order_by' => floor(trim($_GPC['order_by'])),
            'company_id' => floor(trim($_GPC['company_id'])),
            'thumb' => trim($_GPC['thumb'])
        );
        $error = array(
            'title' => '请输入栏目标题',
            'company_id' => '请选择公司',
            'thumb' => '请上传栏目图'
        );
        foreach ($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        if(empty($companies[$data['company_id']])){
            message('选择的公司不存在','','error');
        }
        if(empty($item)){
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $flag = pdo_insert('vapp_article_category',$data);
            $tip = "添加";
        }else{
            $data['updatetime'] = TIMESTAMP;
            $flag = pdo_update('vapp_article_category',$data,array(
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

template('article/display');
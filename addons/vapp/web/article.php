<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017-12-06
 * Time: 14:05
 */

//文章列表
if($op == 'display'){
    if($_W['isajax']){
        if(!check_data($_GPC['ids'])){
            message('请选择要删除的信息','','error');
        }
        $delete_status = pdo_query("DELETE FROM ".tablename('vapp_article')." WHERE uniacid='{$_W['uniacid']}' AND id IN (".implode(',',$_GPC['ids']).")");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $page = getApartPageNo();
    $psize = 15;
    $pindex = ($page-1)*$psize;
    $where = "uniacid='{$_W['uniacid']}'";
    $keyword = trim($_GPC['keyword']);
    if(!empty($keyword)){
        $where .= " AND title LIKE '%{$keyword}%'";
    }
    $is_display = $_GPC['is_display'];
    if(check_data($is_display)){
        $where .= " AND is_display IN (".implode(',',$is_display).")";
    }

    $list = pdo_fetchall("SELECT * FROM ".tablename('vapp_article')." WHERE {$where}");
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('vapp_article')." WHERE {$where}"),$page,$psize);
}


//添加文章
if($op == 'post'){
    $companies = pdo_fetchall("SELECT * FROM ".tablename('vapp_company')." WHERE uniacid='{$_W['uniacid']}' ORDER BY order_by DESC LIMIT 0,50");
    $id = floor(trim($_GPC['id']));
    if(check_id($id)) {
        $item = pdo_get('vapp_article',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
        if(check_data($item)){
            $menus = pdo_fetchall("SELECT * FROM ".tablename('vapp_article_category')." WHERE uniacid='{$_W['uniacid']}' AND company_id='{$item['company_id']}' ORDER BY order_by DESC LIMIT 0,50");
        }
    }
    if($_W['isajax']){
        $data = array(
            'company_id' => floor(trim($_GPC['company_id'])),
            'category_id' => floor(trim($_GPC['category_id'])),
            'title' => trim($_GPC['title']),
            'thumb' => trim($_GPC['thumb']),
            'detail' => $_GPC['detail'],
            'is_display' => floor(trim($_GPC['is_display'])) == 1?1:0,
            'order_by' => floor(trim($_GPC['order_by']))
        );
        $error = array(
            'company_id' => '请选择公司',
            'category_id' => '请选择公司栏目',
            'title' => '请输入文章标题',
            'thumb' => '请上传文章封面图',
            'detail' => '请输入文章内容'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
                break;
            }
        }
        if(empty($item)){ //添加
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $tip = "添加";
            $flag = pdo_insert('vapp_article',$data);
        }else{ //修改
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = pdo_update('vapp_article',$data,array(
                'uniacid' => $_W['uniacid'],
                'id' => $id
            ));
        }
        if(!$flag){
            message("{$tip}失败！请重试！",'','error');
        }
        message("{$tip}成功！",referer(),'success');
    }
}


//搜索相关的栏目
if($op == 'search'){
    $id = floor(trim($_GPC['id']));
    if(!check_id($id)){
        message('公司ID错误','','error');
    }
    $menus = pdo_fetchall("SELECT * FROM ".tablename('vapp_article_category')." WHERE uniacid='{$_W['uniacid']}' AND company_id='{$id}' ORDER BY order_by DESC LIMIT 0,50");
    if(!check_data($menus)){
        message('没有相关栏目，请先添加',$this->createWebUrl('article',array('op'=>'post_category')),'error');
    }
    message($menus,'','success');
}


//分类列表
if($op == 'category'){
    if($_W['isajax']){
        if(!check_data($_GPC['ids'])){
            message('请选择要删除的分类','','error');
        }
        $delete_status = pdo_query("DELETE FROM ".tablename('vapp_article_category')." WHERE uniacid='{$_W['uniacid']}' AND id IN (".implode(',',$_GPC['ids']).")");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('vapp_article_category')." WHERE uniacid='{$_W['uniacid']}' ORDER BY order_by DESC LIMIT 0,50");
}


//提交分类
if($op == 'post_category'){
    $companies = pdo_fetchall("SELECT * FROM ".tablename('vapp_company')." WHERE uniacid='{$_W['uniacid']}' ORDER BY order_by DESC LIMIT 0,50");
    $id = floor(trim($_GPC['id']));
    $category = array();
    if(!empty($id)){
        $category = pdo_get('vapp_article_category',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
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
        if(empty($category)){
            $is_exist_category = pdo_get('vapp_article_category',array(
                'uniacid' => $_W['uniacid'],
                'title' => $data['title']
            ));
            if(!empty($is_exist_category)){
                message('栏目已经存在！','','error');
            }
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
include $this->template('article');
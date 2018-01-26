<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/20
 * Time: 16:57
 */
global  $_W,$_GPC;

$menus = array(
    'CompanyPage' => '官网',
    'MallPage' => '商城',
    'CirclePage' => '圈子',
    'TouTiaoPage' => '头条',
    'GroupPage' => '社群',
    'LivePage' => '直播',
    'UnionPage' => '联盟',
    'KeFuPage' => '客服',
    'LuckPage' => '缘分'
);

//会员列表
if($op == 'display'){
    if($_W['isajax']){
        if(!check_data($_GPC['ids'])){
            message('请选择要删除的信息','','error');
        }
        $delete_status = pdo_query("DELETE FROM ".tablename('vapp_find')." WHERE uniacid='{$_W['uniacid']}' AND id IN (".implode(',',$_GPC['ids']).")");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('vapp_find')." WHERE uniacid='{$_W['uniacid']}' ORDER BY order_by DESC LIMIT 0,20");
}


//添加删除会员
if($op == 'post'){
    $id = floor(trim($_GPC['id']));
    $item = array();
    if(check_id($id)) {
        $item = pdo_get('vapp_find',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'thumb' => trim($_GPC['thumb']),
            'page' => trim($_GPC['page']),
            'is_display' => floor(trim($_GPC['is_display'])) == 1?1:0,
            'order_by' => floor(trim($_GPC['order_by']))
        );
        $error = array(
            'title' => '请输入菜单标题',
            'thumb' => '请上传菜单图',
            'page' => '请选择菜单页面',
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
                break;
            }
        }
        if(!check_data($item)){ //添加
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $tip = "添加";
            $flag = pdo_insert('vapp_find',$data);
        }else{ //修改
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = pdo_update('vapp_find',$data,array(
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



include $this->template('find');
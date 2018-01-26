<?php
if(empty($do)){$do = 'display';}
if($do == 'display'){
    $keyword = trim($_GPC['keyword']);
    $is_display = $_GPC['is_display'];
    $psize = 20;
    $page = max(1,floor(trim($_GPC['page'])));
    $pindex = ($page - 1)*$psize;
    $list = ServicerModel::getSlideList($keyword,$is_display,$pindex,$psize);
    $total = ServicerModel::getSlideCount($keyword,$is_display);
    $pager = pagination($total,$page,$psize);
}elseif($do == 'post'){
    if(trim($_GPC['ac']) == 'delete'){
        $delete_status = ServicerModel::deleteSlideByIds($_GPC['ids']);
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $id = floor(trim($_GPC['id']));
    if(!empty($id)){
        $item = ServicerModel::getSlideById($id);
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'thumb' => trim($_GPC['thumb']),
            'link' => trim($_GPC['link']),
            'is_display' => floor(trim($_GPC['is_display'])) == DISPLAY_YES?DISPLAY_YES:DISPLAY_NO,
            'order_by' => floor(trim($_GPC['order_by']))
        );
        if(empty($data['title'])){
            message('请输入轮播图标题','','error');
        }
        if(empty($data['thumb'])){
            message('请上传轮播图图片','','error');
        }
        if(empty($item)){
            $data['uniacid'] = $_W['uniacid'];
            $data['servicer_id'] = $_W['servicer_id'];
            $data['createtime'] = TIMESTAMP;
            $tip = "添加";
            $flag = ServicerModel::insertSlide($data);
        }else{
            $data['updatetime'] = TIMESTAMP;
            $tip = "修改";
            $flag = ServicerModel::updateSlideById($id,$data);
        }
        if(!$flag){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
}
template('shop/display');
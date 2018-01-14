<?php
load()->func('check');
$do = empty($_GPC['do'])?'display':trim($_GPC['do']);
if($do == 'display'){
    if($_W['isajax']){
        if(empty($_GPC['ids'])){
            message('请选择要删除的活动','','error');
        }
        $ids = implode(',',$_GPC['ids']);
        $delete_status = pdo_query("DELETE FROM ".tablename('sj_news_activity')." WHERE uniacid='{$_W['uniacid']}' AND id IN ($ids)");
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
    if($_W['ad_type'] != 1){
        $where .= " AND province='{$_W['province']}' AND city='{$_W['city']}'";
    }
    if(!empty($keyword)){
        $where .= " AND title LIKE '%{$keyword}%'";
    }
    if($_W['ad_type'] == 1){
        //是超级管理员，所有地区
        $province = $_GPC['area']['province'];
        if(!empty($province)){
            $where .= " AND province='{$province}'";
        }
        $city = $_GPC['area']['city'];
        if(!empty($city)){
            $where .= " AND city='{$city}'";
        }
        $district = $_GPC['area']['district'];
    }else{
        //地级市管理员
        $province = $_W['province'];
        $city = $_W['city'];
        $district = $_W['district'];
        $where .= " AND province='{$_W['province']}' AND city='{$_W['city']}'";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('sj_news_activity')." WHERE {$where} ORDER BY id DESC LIMIT {$pindex},{$psize}");
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('sj_news_activity')." WHERE {$where}"),$page,$psize);
}elseif($do == 'post'){
    $id = floor(trim($_GPC['id']));
    $item = array();
    if(check_id($id)){
        $item = pdo_get('sj_news_activity',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    list($starttime,$endtime) = getStartTimeEndTimeByGPC('activity_time');
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'content' => $_GPC['content'],
            'from_user' => $_GPC['from_user'],
            'starttime' => $starttime,
            'endtime' => $endtime,
            'province' => $_GPC['area']['province'],
            'city' => $_GPC['area']['city'],
            'district' => $_GPC['area']['district'],
            'address' => $_GPC['address'],
            'tel' => trim($_GPC['tel']),
            'wxid' => trim($_GPC['wxid']),
            'is_display' => floor(trim($_GPC['is_display'])) == 1?1:0
        );
        $error = array(
            'title' => '请输入活动标题',
            'content' => '请输入活动内容',
            'from_user' => '请输入主办方',
            'starttime' => '请选择开始时间',
            'endtime' => '请选择结束时间',
            'province' => '请选择报名省份',
            'city' => '请选择报名城市',
            'district' => '请选择报名区县',
            'address' => '请输入报名详细地址',
            'tel' => '请输入联系电话',
            'wxid' => '请输入微信号'
        );
        foreach ($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        if($data['starttime'] >= $data['endtime']){
            message('开始时间不能小于结束时间','','error');
        }
        if($data['endtime'] <= TIMESTAMP){
            message('结束时间不能小于当前时间','','error');
        }
        if(empty($item)){
            //插入数据
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $status = pdo_insert('sj_news_activity',$data);
            $tip = "添加";
        }else{
            //更新数据
            $data['updatetime'] = TIMESTAMP;
            $status = pdo_update('sj_news_activity',$data,array(
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
template('activity/display');
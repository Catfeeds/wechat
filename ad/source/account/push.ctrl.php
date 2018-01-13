<?php
load()->func('check');
$do = empty($_GPC['do'])?'display':trim($_GPC['do']);
$categories = pdo_fetchall("SELECT * FROM ".tablename('sj_news_category')." WHERE uniacid='{$_W['uniacid']}'",array(),'id');
if($do == 'display'){
    if($_W['isajax']){
        if(empty($_GPC['ids'])){
            message('请选择要删除的文章','','error');
        }
        $ids = implode(',',$_GPC['ids']);
        $delete_status = pdo_query("DELETE FROM ".tablename('sj_news_list')." WHERE uniacid='{$_W['uniacid']}' AND id IN ($ids)");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $category_id = floor(trim($_GPC['category_id']));
    $keyword = trim($_GPC['keyword']);
    $where = "uniacid='{$_W['uniacid']}' AND type!='0'";
    if(!empty($_GPC['type']) && is_array($_GPC['type'])){
        $where .= " AND type IN (".implode(',',$_GPC['type']).")";
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
        $where .= " AND province='{$province}' AND city='{$city}'";
    }
    if(check_data($_GPC['is_display'])){
        $where .= " AND is_display IN (".implode(',',$_GPC['is_display']).")";
    }
    if(!empty($keyword)){
        $where .= " AND title LIKE '%{$keyword}%'";
    }
    if(!empty($category_id)){
        $where .= " AND cid='{$category_id}'";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('sj_news_list')." WHERE {$where} ORDER BY id DESC LIMIT {$pindex},{$psize}");
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('sj_news_list')." WHERE {$where}"),$page,$psize);
}elseif($do == 'post'){
    $id = floor(trim($_GPC['id']));
    $item = array();
    if(check_id($id)){
        $item = pdo_get('sj_news_list',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    if(!check_data($item)){
        message('发布信息不存在','','error');
    }
    if(!empty($item['thumbs'])){
        $item['thumbs'] = iunserializer($item['thumbs']);
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'cid' => floor(trim($_GPC['cid'])),
            'desc' => $_GPC['desc'],
            'detail' => $_GPC['detail'],
            'updatetime' => TIMESTAMP
        );

        //存储对应类别的信息
        if(!empty($_GPC['thumbs'])){
            $data['thumbs'] = iserializer($_GPC['thumbs']);
        }
        if(!empty($_GPC['thumb'])){
            $data['thumb'] = $_GPC['thumb'];
        }

        if($_W['ad_type'] == 1){
            //超级管理员
            $data['is_check2'] = floor(trim($_GPC['is_check2'])) == 1?1:0;
            if($data['is_check2'] == 1 && $item['is_check1'] != 1){
                message('地级市尚未审核','','error');
            }
            //显示状态
            $data['is_display'] = 1;
        }else{
            //地级市管理员
            $data['is_check1'] = floor(trim($_GPC['is_check1'])) == 1?1:0;
            if($data['is_check1'] == 1 && ($item['type'] == 2 ||  $item['type'] == 3)){
                //$data['is_display'] = 1;
            }
        }

        $error = array(
            'title' => '请输入标题',
            'cid' => '请选择分类',
            'desc' => '请输入简介',
            'detail' => '请输入详情',
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        $where = array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        );
        if($_W['ad_type'] == 0){
            $where['province'] = $_W['province'];
            $where['city'] = $_W['city'];
        }
        $status = pdo_update('sj_news_list',$data,$where);
        if(!$status){
            message("提交失败",'','error');
        }
        //奖励积分
        if($data['is_display'] == 1 && $item['is_display'] == 0){
            //内容显示，审核通过赠送积分
            load()->model('mc');
            load()->func('check');
            load()->func('notice');
            $config = pdo_get('sj_news_credit_config',array(
                'uniacid' => $_W['uniacid']
            ));
            if(check_data($config)){
                if(!empty($config['setting'])){
                    $setting = iunserializer($config['setting']);
                    //获取积分奖励设置
                    $rebate_credit1 = 0;
                    $rebate_type = "文章";
                    $type = 0;
                    if(check_data($setting)){
                        switch ($item['type']){
                            //文章
                            case 1:
                                $rebate_credit1 = $setting['article'];
                                $rebate_type = "文章";
                                $type = 1;
                                ;break;

                            //图片
                            case 2:
                                $rebate_credit1 = $setting['img'];
                                $rebate_type = "图片";
                                $type = 2;
                                ;break;

                            //视频
                            case 3:
                                $rebate_credit1 = $setting['video'];
                                $rebate_type = "视频";
                                $type = 3;
                                ;break;
                        }
                       if($rebate_credit1 > 0){
                            //增加积分并记录
                            mc_credit_update($_W['member']['uid'],'credit1',$rebate_credit1,array($_W['member']['uid'],"{$rebate_type}审核成功，赠送{$rebate_credit1}积分。"));
                            pdo_insert('sj_news_rebate',array(
                                'uniacid' => $_W['uniacid'],
                                'uid' => $item['uid'],
                                'credit1' => $rebate_credit1,
                                'type' => $type,
                                'createtime' => TIMESTAMP
                            ));
                            notice_send_simple_text($_W['member']['uid'],"文章评论发表成功，恭喜您获得{$setting['share']}积分奖励。【新晋传媒】");
                        }
                    }
                }
            }
        }
        message("提交成功",referer(),'success');
    }
}
template('account/push');
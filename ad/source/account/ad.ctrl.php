<?php
load()->func('check');
$do = empty($_GPC['do'])?'display':trim($_GPC['do']);
//广告配置
$adConfig = pdo_get('sj_news_ad_config',array(
    'uniacid' => $_W['uniacid']
));
if(!empty($adConfig['setting'])){
    $adConfig['setting'] = iunserializer($adConfig['setting']);
}

//广告支付订单
if($do == 'display'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $where = "a.uniacid='{$_W['uniacid']}'";
    if($_W['ad_type'] == 1){
        //是超级管理员，所有地区
        $province = $_GPC['area']['province'];
        if(!empty($province)){
            $where .= " AND a.province='{$province}'";
        }
        $city = $_GPC['area']['city'];
        if(!empty($city)){
            $where .= " AND a.city='{$city}'";
        }
        $district = $_GPC['area']['district'];
    }else{
        //地级市管理员
        $province = $_W['province'];
        $city = $_W['city'];
        $district = $_W['district'];
        $where .= " AND a.province='{$_W['province']}' AND a.city='{$_W['city']}'";
    }
    if(!empty($keyword)){
        $where .= " AND a.title LIKE '%{$keyword}%'";
    }
    $list = pdo_fetchall("SELECT a.*,b.is_check,b.pay_status,b.pay_method,b.price,b.pay_goods_price,b.id AS order_id FROM ".tablename('sj_news_ad')." a LEFT JOIN ".tablename('sj_news_ad_order')." b ON a.id=b.ad_id WHERE {$where} ORDER BY a.id DESC LIMIT {$pindex},{$psize}");
    if(check_data($list)){
        $payMethodArrSpan = array(
            1 => '<span class="label label-default">余额</span>',
            2 => '<span class="label label-info">微信</span>',
            3 => '<span class="label label-warning">支付宝</span>',
            4 => '<span class="label label-success">银行卡</span>',
            5 => '<span class="label label-warning">微信</span>',
            6 => '<span class="label label-danger">货到</span>'
        );
        foreach($list as $k => &$v){
            $v['package_name'] = $adConfig['setting'][$v['package_id']]['name'];
            $v['pay_method'] = $payMethodArrSpan[$v['pay_method']];
            $v['thumb'] = tomedia($v['thumb']);
            $v['qualifications'] = tomedia($v['qualifications']);
        }
    }
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('sj_news_ad')." a WHERE {$where}"),$page,$psize);
}

if($do == 'post'){
    $id = floor(trim($_GPC['id']));
    $order = pdo_get('sj_news_ad_order',array(
        'sj_uniacid'=>$_W['uniacid'],
        'id' => $id
    ));
    if(!check_data($order)){
        message('订单信息不存在','','error');
    }
    if($order['pay_status'] == 0){
        message('广告未支付','','error');
    }
    //广告显示和有效期
    $item = pdo_get('sj_news_ad',array(
        'id' => $order['ad_id'],
        'uniacid'=>$order['sj_uniacid']
    ));
    if(!check_data($item)) {
        message('广告信息不存在','','error');
    }
    if($_W['isajax']){
        $data = array(
           'title' => trim($_GPC['title']),
            'desc' => trim($_GPC['desc']),
            'thumb' => trim($_GPC['thumb']),
            'link' => trim($_GPC['link']),
            'industry' => trim($_GPC['industry']),
            'qualifications' => trim($_GPC['qualifications']),
            'contact' => trim($_GPC['contact']),
            'updatetime' => TIMESTAMP
        );
        if($_W['ad_type'] == 1){
            //总平台
            if($order['is_check'] == 0){
                message('地级市尚未审核','','error');
            }
            //开启事务
            pdo_begin();
            //修改广告
            $data['is_display'] = floor(trim($_GPC['is_display'])) == 1?1:0;
            $data['last_time']  = ($item['last_time'] < TIMESTAMP?TIMESTAMP:$item['last_time']) + $order['day']*24*3600;
            $status2 = pdo_update('sj_news_ad',$data,array(
                'id' => $order['ad_id'],
                'uniacid'=>$order['sj_uniacid']
            ));
            if(!$status2){
                pdo_rollback();
                message('审核失败:-2','','error');
            }
            pdo_commit();
            message('审核成功',referer(),'success');
        }else{
            //开启事务
            pdo_begin();
            //修改订单
            $status = pdo_update('sj_news_ad_order',array(
                'is_check' => floor(trim($_GPC['is_check'])) == 1?1:0,
                'updatetime' => TIMESTAMP
            ),array(
                'sj_uniacid'=>$_W['uniacid'],
                'id' => $id
            ));
            if(!$status){
                pdo_rollback();
                message('审核失败：-1','','error');
            }
            //修改广告
            $status2 = pdo_update('sj_news_ad',$data,array(
                'id' => $order['ad_id'],
                'uniacid'=>$order['sj_uniacid']
            ));
            if(!$status2){
                pdo_rollback();
                message('审核失败:-2','','error');
            }
            pdo_commit();
            message('审核成功',referer(),'success');
        }
    }
}

//广告续费订单
if($do == 'renew'){
    if($_W['isajax']){
        $id = floor(trim($_GPC['id']));
        $order = pdo_get('sj_news_ad_renew_order',array(
            'sj_uniacid'=>$_W['uniacid'],
            'id' => $id
        ));
        if(!check_data($order)){
            message('订单信息不存在','','error');
        }
        if($order['pay_status'] == 0){
            message('广告未支付','','error');
        }
        //广告显示和有效期
        $ad_info = pdo_get('sj_news_ad',array(
            'id' => $order['ad_id'],
            'uniacid'=>$order['sj_uniacid']
        ));
        if(!check_data($ad_info)) {
            message('广告信息不存在','','error');
        }
        if($_W['ad_type'] == 1){
            //总平台
            if($order['is_check'] == 0){
                message('地级市尚未审核','','error');
            }
            //开启事务
            pdo_begin();
            //修改广告
            $status2 = pdo_update('sj_news_ad',array(
                'renew_is_check' => 0,
                'last_time' => ($ad_info['last_time'] < TIMESTAMP?TIMESTAMP:$ad_info['last_time']) + $order['total_day']*24*3600,
                'updatetime' => TIMESTAMP
            ),array(
                'id' => $order['ad_id'],
                'uniacid'=>$order['sj_uniacid']
            ));
            if(!$status2){
                pdo_rollback();
                message('审核失败:-2','','error');
            }
            pdo_commit();
            message('审核成功',referer(),'success');
        }else{
            if($ad_info['is_check'] == 1){
                message('广告已经审核，无需重复操作','','error');
            }
            //如果是地级市，只需修改订单的状态
            $status = pdo_update('sj_news_ad_renew_order',array(
                'is_check' => 1,
                'updatetime' => TIMESTAMP
            ),array(
                'sj_uniacid'=>$_W['uniacid'],
                'id' => $id
            ));
            if(!$status){
                message('审核失败','','error');
            }
            message('审核成功',referer(),'success');
        }
    }
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $where = "a.uniacid='{$_W['uniacid']}'";
    if($_W['ad_type'] == 1){
        //是超级管理员，所有地区
        $province = $_GPC['area']['province'];
        if(!empty($province)){
            $where .= " AND a.province='{$province}'";
        }
        $city = $_GPC['area']['city'];
        if(!empty($city)){
            $where .= " AND a.city='{$city}'";
        }
        $district = $_GPC['area']['district'];
    }else{
        //地级市管理员
        $province = $_W['province'];
        $city = $_W['city'];
        $district = $_W['district'];
        $where .= " AND a.province='{$_W['province']}' AND a.city='{$_W['city']}'";
    }
    if(!empty($keyword)){
        $where .= " AND a.title LIKE '%{$keyword}%'";
    }
    $list = pdo_fetchall("SELECT a.*,b.is_check,b.pay_status,b.pay_method,b.price,b.pay_goods_price,b.id AS order_id FROM ".tablename('sj_news_ad')." a RIGHT JOIN ".tablename('sj_news_ad_renew_order')." b ON a.id=b.ad_id WHERE {$where} ORDER BY a.id DESC LIMIT {$pindex},{$psize}");
    if(check_data($list)){
        $payMethodArrSpan = array(
            1 => '<span class="label label-default">余额</span>',
            2 => '<span class="label label-info">微信</span>',
            3 => '<span class="label label-warning">支付宝</span>',
            4 => '<span class="label label-success">银行卡</span>',
            5 => '<span class="label label-warning">微信</span>',
            6 => '<span class="label label-danger">货到</span>'
        );
        foreach($list as $k => &$v){
            $v['package_name'] = $adConfig['setting'][$v['package_id']]['name'];
            $v['pay_method'] = $payMethodArrSpan[$v['pay_method']];
            $v['thumb'] = tomedia($v['thumb']);
            $v['qualifications'] = tomedia($v['qualifications']);
        }
    }
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('sj_news_ad')." a WHERE {$where}"),$page,$psize);
}
template('account/ad');
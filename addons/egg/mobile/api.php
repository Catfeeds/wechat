<?php
load()->func('check');
$_W['isajax'] = true;
if($op == 'display'){

}elseif($op == 'getConfig'){
    //使用说明
    $setting = array();
    $config = pdo_get('egg_config',array(
        'uniacid'=>$_W['uniacid']
    ));
    if(check_data($config)){
        if(!empty($config['setting'])){
            $setting = iunserializer($config['setting']);
            if(check_data($setting)){
                if(!empty($setting['introduce'])){
                    $setting['introduce'] = htmlspecialchars_decode(iunserializer($setting['introduce']));
                }
                if(!empty($setting['use_introduce'])){
                    $setting['use_introduce'] = htmlspecialchars_decode(iunserializer($setting['use_introduce']));
                }
                if(!empty($setting['update_detail'])){
                    $setting['update_detail'] = htmlspecialchars_decode(iunserializer($setting['update_detail']));
                }
                if(!empty($setting['copyright'])){
                    $setting['copyright'] = htmlspecialchars_decode(iunserializer($setting['copyright']));
                }
            }
        }
    }
    if(!check_data($setting)){
        message('软件未进行相关设置','','error');
    }
    message($setting,'','success');

}elseif($op == 'search'){
    //搜索公司
    $keyword = trim($_GPC['keyword']);
    $page = max(1,floor(trim($_GPC['page'])));
    $psize = 50;
    $pindex = ($page-1)*$psize;
    $where = "uniacid='{$_W['uniacid']}'";
    if(!empty($keyword)){
        $where .= " AND name LIKE '%{$keyword}%'";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('egg_company')." WHERE {$where} ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    if(!check_data($list)){
        message('没有更多信息','','error');
    }
    message($list,'','success');
}elseif($op == 'getAdList'){
    //搜索公司
    $keyword = trim($_GPC['keyword']);
    $type = floor(trim($_GPC['type'])) == 1?1:0;
    $page = max(1,floor(trim($_GPC['page'])));
    $psize = 6;
    $pindex = ($page-1)*$psize;
    $where = "uniacid='{$_W['uniacid']}' AND type='{$type}'";
    if(!empty($keyword)){
        $where .= " AND title LIKE '%{$keyword}%'";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('egg_ad')." WHERE {$where} ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    if(!check_data($list)){
        message('没有更多信息','','error');
    }
    foreach($list as $k => &$v){
        $v['thumb'] = tomedia($v['thumb']);
    }
    message($list,'','success');
}elseif($op == 'getSlides'){
    //获取轮播列表
    $list = pdo_fetchall("SELECT * FROM ".tablename('egg_slide')." WHERE uniacid='{$_W['uniacid']}' AND is_display='1'ORDER BY order_by DESC");
    if(check_data($list)){
        foreach($list as $k => &$v){
            $v['thumb'] = tomedia($v['thumb']);
        }
        message($list,'','success');
    }
    message('没有获取到相关轮播','','error');
}elseif($op == 'pushAd'){
    //提交图文推广、视频推广
    $data = array(
       'uniacid' => $_W['uniacid'],
       'title' => trim($_GPC['title']),
        'thumb' => $_GPC['productImg'],
        'wx_code' => $_GPC['qrCode'],
        'type'=> floor(trim($_GPC['type'])) == 1?1:0,
        'url' => trim($_GPC['urlLink']),
        'desc' => trim($_GPC['description'])
    );
    $error = array(
        'title' => '请输入标题',
        'thumb' => '请上传产品图片',
        'wx_code' => '请上传二维码图片',
        'desc' => '请输入产品描述'
    );
    if($data['type'] == 1){
        $error['url'] = '请输入视频详情地址';
    }
    foreach($error as $k => $message){
        if(empty($data[$k])){
            message($message,'','error');
        }
    }
    $data['thumb'] = base64ToImage($data['thumb']);
    $data['wx_code'] = base64ToImage($data['wx_code']);
    $status = pdo_insert('egg_ad',$data);
    if(!$status){
        message('提交失败','','error');
    }
    message('提交成功','','success');
}elseif($op == 'pushFen'){
    if($_W['isajax']){
        $data = array(
            'uniacid' => $_W['uniacid'],
            'title' => trim($_GPC['title']),
            'tags' => check_data($_GPC['tagArr'])?implode('#',$_GPC['tagArr']):null,
            'type' => floor(trim($_GPC['jiaQun'])),
            'wx' => trim($_GPC['number']),
            'wx_code' => '',
            'desc' => $_GPC['description'],
            'province' => !empty($_GPC['area'][0])?$_GPC['area'][0]:"",
            'city' => !empty($_GPC['area'][1])?$_GPC['area'][1]:"",
            'district' => !empty($_GPC['area'][2])?$_GPC['area'][2]:"",
            'createtime' => TIMESTAMP
        );
        $error = array(
            'title' => '请输入标题',
            'type' => '请选择类别',
            'wx' => '请输入微信号',
            'desc' => '请输入描述'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        $status = pdo_insert('egg_baofen',$data);
        if(!$status){
            message('提交失败','','error');
        }
        message('提交成功','','success');
    }
}elseif($op == 'getTelProvinces'){
    //获取所有省份
    $list = pdo_fetchall("SELECT DISTINCT(province) FROM ".tablename('egg_mobile_area')." WHERE uniacid='{$_W['uniacid']}' ORDER BY province ASC");
    if(!check_data($list)){
        message('省份列表获取失败','','error');
    }
    message($list,'返回省份','success');
}elseif($op == 'getTelCities'){
    //获取所有省份
    $province = trim($_GPC['province']);
    if(empty($province)){
        message('请先选择省份','','error');
    }
    $list = pdo_fetchall("SELECT DISTINCT(city) FROM ".tablename('egg_mobile_area')." WHERE uniacid='{$_W['uniacid']}' AND province='{$province}' ORDER BY city ASC");
    if(!check_data($list)){
        message('城市列表获取失败','','error');
    }
    message($list,'返回城市','success');
}elseif($op == 'getTelMatching'){
    //获取所有省份
    $province = trim($_GPC['province']);
    $city = trim($_GPC['city']);
    if(empty($province)){
        message('请先选择省份','','error');
    }
    if(empty($city)){
        message('请先选择城市','','error');
    }
    $list = pdo_fetchall("SELECT matching FROM ".tablename('egg_mobile_area')." WHERE uniacid='{$_W['uniacid']}' AND province='{$province}' AND city='{$city}' ORDER BY city ASC");
    if(!check_data($list)){
        message('号段列表获取失败','','error');
    }
    message($list,'返回号段','success');
}
message('请求错误','','error');
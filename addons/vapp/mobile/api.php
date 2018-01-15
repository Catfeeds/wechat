<?php

//将ajax设为true，强制返回json的数据
global $_W;

//支付确认提交页面
if($op == 'push_pay'){
    $user_info = auth_check($_GPC['token']);
    if(!check_data($user_info)){
        to_json(-1,'请先登录');
    }
    //获取支付方式
    $config = pdo_get('vapp_config',array(
        'uniacid' => $_W['uniacid']
    ));
    $setting = array();
    if(!empty($config['setting'])){
        $setting = iunserializer($config['setting']);
    }
    if(!check_data($setting['payment'])) {
        to_json(1,'没有设定支付参数');
    }
    $id = floor(trim($_GPC['id']));
    if(!check_id($id)){
        to_json(1,'支付记录不存在');
    }
    $pay_info = pdo_get('pay_log',array(
        'uniacid' => $_W['uniacid'],
        'uid' => $user_info['uid'],
        'id' => $id
    ));
    if(!check_data($pay_info)){
        to_json(1,'支付信息不存在');
    }
    if($pay_info['status'] == PAY_YES){
        to_json(1,'订单已经支付');
    }
    $pay_method = floor(trim($_GPC['pay_method']));
    pdo_update('pay_log',array('pay_method'=>$pay_method),array(
        'uniacid' => $_W['uniacid'],
        'uid' => $user_info['uid'],
        'id' => $id
    ));
    if($pay_method == PAY_METHOD_CREDIT){
        //余额支付
        if($setting['payment']['credit2']['status'] != OPEN_STATUS){
            to_json(1,'余额支付未开启');
        }
        /* 处理余额支付 */
        pay_credit2_deal($user_info['uid'],$_GPC['password'],$pay_info);
    }elseif($pay_method == PAY_METHOD_WECHAT){
        //微信支付
        if($setting['payment']['wechat']['status'] != OPEN_STATUS){
            to_json(1,'微信支付未开启');
        }
    }elseif($pay_method == PAY_METHOD_ALIPAY){
        //支付宝支付
        if($setting['payment']['alipay']['status'] != OPEN_STATUS){
            to_json(1,'支付宝支付未开启');
        }
        $body = md5('AliPayAuthKey'.$pay_info['out_trade_no']);

        //基本参数
        $pay_query = json_encode(array(
            'timeout_express' => "30m",
            'product_code' => "QUICK_MSECURITY_PAY",
            "total_amount" => $pay_info['pay_price'],
            "subject" => '测试商品',
            "body" => $body,
            "out_trade_no" => $pay_info['out_trade_no']
        ));
        $aliquery = array(
            "app_id" 		=> $setting['payment']['alipay']['partner'],
            "method" 		=> "alipay.trade.app.pay",
            "sign_type" 	=> "RSA2",
            "version" 		=> "1.0",
            "timestamp" 	=> date('Y-m-d H:i:s')  ,//yyyy-MM-dd HH:mm:ss
            "biz_content" 	=> $pay_query,
            "charset" 		=> "utf-8"
        );
        $aliquery['sign'] = urlencode(alipay_getSign($aliquery,$setting['payment']['alipay']['secret']));
        $aliquery['format'] = "JSON";
        $aliquery['timestamp'] =  urlencode($aliquery['timestamp']);
        $aliquery['notify_url'] = urlencode("http://wx.51muma.com/payment/alipay/notify_url.php");
        $aliquery['biz_content'] = urlencode($aliquery['biz_content']);
        $aliquery['body'] = urlencode($body);
        to_json(0,'返回支付宝参数',http_build_query($aliquery));
    }
    to_json(1,'支付方式选择错误');
}

//支付确认展示页
if($op == 'pay'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $id = floor(trim($_GPC['id']));
    if(empty($id)){
        to_json('支付记录ID错误');
    }
    $pay_info = pdo_get('pay_log',array(
        'uniacid' => $_W['uniacid'],
        'uid' => $user_info['uid'],
        'id' => $id
    ));
    if(!check_data($pay_info)){
        to_json(1,'支付信息不存在');
    }
    if($pay_info['status'] == PAY_YES){
        to_json(1,'订单已经支付');
    }
    $pay_info['order_count'] =  count(explode('-',$pay_info['order_ids']));
    //获取支付方式
    $config = pdo_get('vapp_config',array(
        'uniacid' => $_W['uniacid']
    ));
    $pay_info['payment'] = array();
    if(!empty($config['setting'])){
        $setting = iunserializer($config['setting']);
        if(check_data($setting['payment'])){
            if($setting['payment']['credit2']['switch'] != OPEN_STATUS){
                array_push($pay_info['payment'],array(
                    'pay_method' => PAY_METHOD_CREDIT,
                    'title' => '余额支付'
                ));
            }
            if($setting['payment']['alipay']['switch'] != OPEN_STATUS){
                array_push($pay_info['payment'],array(
                    'pay_method' => PAY_METHOD_ALIPAY,
                    'title' => '支付宝支付'
                ));
            }
            if($setting['payment']['wechat']['switch'] != OPEN_STATUS){
                array_push($pay_info['payment'],array(
                    'pay_method' => PAY_METHOD_WECHAT,
                    'title' => '微信支付'
                ));
            }
        }
    }
    to_json(0,'返回支付信息',$pay_info);
}

//获取社群列表
if($op == 'get_groups'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $page = getApartPageNo();
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $list = pdo_fetchall("SELECT id,title,logo,max_count,member_count,topic_count,talk_count FROM ".tablename('vapp_group')." WHERE uniacid='{$_W['uniacid']}' AND is_display='1' ORDER BY order_by DESC LIMIT {$pindex},{$psize}");
    if(!check_data($list)){
        to_json(1,'没有找到相关社群');
    }
    foreach ($list as $k => &$v){
        $v['logo'] = tomedia($v['logo']);
    }
    to_json(0,'返回社群列表',$list);
}


//订单列表
if($op == 'get_orders'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $orderStatus = array(
        0 => '全部',
        1 => '未付款',
        2 => '待发货',
        3 => '待确认',
        4 => '已完成',
        5 => '待退款',
        6 => '已退款',
        7 => '已关闭'
    );
    $status = floor(trim($_GPC['status']));
    $page = getApartPageNo();
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $where = "a.uniacid='{$_W['uniacid']}' AND a.uid='{$user_info['uid']}'";
    switch ($status - 1){
        case 0: $where .= " AND a.order_status=".ORDER_STATUS_NOT_PAY;;break;
        case 1: $where .= " AND a.order_status=".ORDER_STATUS_NOT_DELIVER;break;
        case 2: $where .= " AND a.order_status=".ORDER_STATUS_NOT_CONFIRM;break;
        case 3: $where .= " AND a.is_talk=".TALK_NO." AND a.order_status=".ORDER_STATUS_COMPLETE;break;
        case 4: $where .= " AND a.order_status=".ORDER_STATUS_NOT_RETURN; break;
        case 5: $where .= " AND a.order_status=".ORDER_STATUS_RETURN;break;
        case 6: $where .= " AND a.order_status=".ORDER_STATUS_CLOSE;break;
    }
    $where .= " ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}";
    $list = pdo_fetchall("SELECT a.*,b.`goods_name`,b.`thumb` FROM ".tablename('vapp_order')." a LEFT JOIN ".tablename('vapp_order_goods')." b ON a.id=b.order_id WHERE {$where}");
    if(check_data($list)){
        foreach ($list as $k => &$v){
            $v['pay_desc'] = $orderStatus[$v['pay_status']+1];
            $v['thumb'] = tomedia($v['thumb']);
        }
    }
    to_json(0,'返回订单状态和列表',array(
        'statuses' => $orderStatus,
        'list' => $list
    ));
}

//获取地址详情
if($op == 'address_detail'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $id = floor(trim($_GPC['id']));
    if(!check_id($id)){
        to_json(1,'地址ID错误');
    }
    $info = pdo_get('vapp_address',array(
        'uniacid' => $_W['uniacid'],
        'uid' => $user_info['uid'],
        'id' => $id
    ),array(
        'id',
        'realname',
        'mobile',
        'province',
        'city',
        'district',
        'address',
        'isdefault'
    ));
    if(!check_data($info)){
        to_json(1,'地址信息不存在');
    }
    to_json(0,'返回地址详情');
}

//获取收货地址
if($op == 'get_address'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $page = getApartPageNo();
    $psize = 50;
    $pindex = ($page-1)*$psize;
    $list = pdo_fetchall("SELECT id,realname,mobile,province,city,district,address,isdefault FROM ".tablename('vapp_address')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$user_info['uid']}' ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    if(!check_data($list)){
        to_json(1,'没有更多收货地址信息');
    }
    to_json(0,'返回地址列表',$list);
}

//删除收货地址
if($op == 'delete_address'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $id = floor(trim($_GPC['id']));
    $delete_status = pdo_delete('vapp_address',array(
        'uniacid'=>$_W['uniacid'],
        'uid'=>$user_info['uid'],
        'id'=>$id
    ));
    if(!$delete_status){
        to_json(1,'删除失败');
    }
    to_json(0,'删除成功');
}

//设为默认地址
if($op == 'set_default_address'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $id = floor(trim($_GPC['id']));
    if(!check_id($id)){
        to_json(1,'地址ID错误');
    }
    $item = pdo_get('vapp_address',array(
        'uniacid'=>$_W['uniacid'],
        'uid'=>$user_info['uid'],
        'id'=>$id
    ));
    if(!check_data($item)){
        to_json(1,'地址信息不存在');
    }
    $is_default = ($item['isdefault'] == 1?0:1);
    if($is_default == 1){
        pdo_update('vapp_address',array(
            'isdefault' => 0
        ),array(
            'uniacid' => $_W['uniacid'],
            'uid' => $user_info['uid'],
            'isdefault' => 1
        ));
    }
    $status = pdo_update('vapp_address',
        array(
            'isdefault'=>$is_default
        ),
        array(
            'uniacid'=>$_W['uniacid'],
            'uid'=>$user_info['uid'],
            'id' => $id
        )
    );
    if(!$status){
        to_json(1,'设置失败');
    }
    to_json(0,'设置成功');
}

//添加收货地址
if($op == 'add_address'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $data = array(
        'uniacid' => $_W['uniacid'],
        'uid' => $user_info['uid'],
        'realname' => trim($_GPC['realname']),
        'mobile' => trim($_GPC['mobile']),
        'zipcode' => trim($_GPC['zipcode']),
        'province' => trim($_GPC['province']),
        'city' => trim($_GPC['city']),
        'district' => trim($_GPC['district']),
        'address' => trim($_GPC['address']),
        'createtime' => TIMESTAMP
    );
    $error = array(
        'realname' => '请输入姓名',
        'mobile' => '请输入手机号',
        'province' => '请选择省份',
        'city' => '请选择城市',
        'district' => '请选择区县',
        'address' => '请填写详细地址'
    );
    foreach($error as $k => $message){
        if(empty($data[$k])){
            to_json(1,$message);
        }
    }
    $status = pdo_insert('vapp_address',$data);
    if(!$status){
        to_json(1,'添加失败');
    }
    to_json(0,'添加成功');
}

//修改收货地址
if($op == 'update_address'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $id = floor(trim($_GPC['id']));
    if(!check_id($id)){
        to_json(1,'地址ID错误');
    }
    $item = pdo_get('vapp_address',array(
        'uniacid'=>$_W['uniacid'],
        'uid'=>$user_info['uid'],
        'id'=>$id
    ));
    if(!check_data($item)){
        to_json(1,'地址信息不存在');
    }
    $data = array(
        'username' => trim($_GPC['username']),
        'mobile' => trim($_GPC['mobile']),
        'zipcode' => trim($_GPC['zipcode']),
        'province' => trim($_GPC['province']),
        'city' => trim($_GPC['city']),
        'district' => trim($_GPC['district']),
        'address' => trim($_GPC['address']),
        'updatetime' => TIMESTAMP
    );
    $error = array(
        'username' => '请输入姓名',
        'mobile' => '请输入手机号',
        'province' => '请选择省份',
        'city' => '请选择城市',
        'district' => '请选择区县',
        'address' => '请填写详细地址'
    );
    foreach($error as $k => $message){
        if(empty($data[$k])){
            to_json(1,$message);
        }
    }
    $status = pdo_update('vapp_address',$data,
        array(
            'uniacid'=>$_W['uniacid'],
            'uid'=>$user_info['uid'],
            'id'=>$id
        )
    );
    if(!$status){
        to_json(1,'修改失败');
    }
    to_json(0,'修改成功');
}

//获取购物车
if($op == 'get_cart'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $where = "a.uniacid='{$_W['uniacid']}' AND a.uid='{$user_info['uid']}'";
    $list = pdo_fetchall("SELECT a.*,b.`title` AS goods_name,b.`thumb`,b.`sale_price`,b.`limit_time_price`,b.`total`,b.`is_open_limit_time_buy`,b.`limit_time_buy_start`,b.`limit_time_buy_end`,b.`sku_list`,b.`is_open_spec`,b.`unit`,c.`title`,c.`logo` AS company_logo FROM ".tablename('vapp_order_cart')." a LEFT JOIN ".tablename('vapp_goods')." b ON a.goods_id=b.id LEFT JOIN ".tablename('vapp_company')." c ON a.company_id=c.id  WHERE {$where}");
    if(!check_data($list)){
        to_json(1,'购物车为空');
    }
    foreach($list as $k => &$v){
        $v['thumb'] = tomedia($v['thumb']);
        $v['company_logo'] = tomedia($v['company_logo']);
        if(!empty($v['sku_list'])){
            $v['sku_list'] = iunserializer($v['sku_list']);
        }
    }
    $cartList = array();
    foreach($list as $k => $cart){
        $cartList[$cart['company_id']]['id'] = $cart['id'];
        $cartList[$cart['company_id']]['title'] = $cart['title'];
        $cartList[$cart['company_id']]['company_logo'] = $cart['company_logo'];
        $cart['sku_change'] = getGoodsSkuChange($cart,$cart['sku_desc'],$cart['sku_key']);
        $cart['sale_price'] = getGoodsTruePrice($cart,$cart['sku_key']);
        unset($cart['sku_list']);
        $cartList[$cart['company_id']]['carts'][] = $cart;
    }
    arrayTransByKey($cartList);
    to_json(0,'返回购物车列表',$cartList);
}

//删除购物车
if($op == 'delete_cart'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $ids = array();
    if(!empty($_GPC['id'])){
        $ids = explode(',',$_GPC['id']);
    }
    if(!check_data($ids)){
        to_json(1,'请选择要删除的商品');
    }
    $status = pdo_query("DELETE FROM ".tablename('vapp_order_cart')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$user_info['uid']}' AND id IN (".implode(',',$ids).")");
    if(!$status){
        to_json(1,'删除失败');
    }
    to_json(0,'删除成功');
}

//修改购物车
if($op == 'update_cart'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $cart_id = floor(trim($_GPC['cart_id']));
    $buy_num = floor(trim($_GPC['buy_num']));
    $cart = pdo_get('vapp_order_cart',array(
        'uniacid'=>$_W['uniacid'],
        'uid' => $user_info['uid'],
        'id' => $cart_id
    ));
    if(!check_data($cart)){
        to_json(1,'购物车不存在');
    }
    $goods_info = pdo_get('vapp_goods',array(
        'uniacid'=>$_W['uniacid'],
        'is_display'=>DISPLAY_YES,
        'is_check'=>CHECK_PASS,
        'id'=>$cart['goods_id']
    ));
    if(!check_data($goods_info)){
        to_json(1,'商品信息不存在');
    }
    if(!empty($goods_info['attr_list'])){
        $goods_info['attr_list'] = iunserializer($goods_info['attr_list']);
    }
    if(!empty($goods_info['spec_list'])){
        $goods_info['spec_list'] = iunserializer($goods_info['spec_list']);
    }
    if(!empty($goods_info['sku_list'])){
        $goods_info['sku_list'] = iunserializer($goods_info['sku_list']);
    }
    if(!empty($goods_info['thumbs'])){
        $goods_info['thumbs'] = iunserializer($goods_info['thumbs']);
    }
    if(!check_data($goods_info)){
        to_json(1,'商品信息不存在');
    }
    if($buy_num > getGoodsTrueTotal($goods_info,$cart['sku_key'])){
        to_json(1,'商品数量不足');
    }
    $status = pdo_update('vapp_order_cart',array(
        'buy_num' => $buy_num,
        'updatetime' => TIMESTAMP
    ),array(
        'uniacid' => $_W['uniacid'],
        'uid' => $user_info['uid'],
        'id' => $cart_id
    ));
    if(!$status){
        to_json(1,'修改失败');
    }
    to_json(0,'修改成功');
}

//添加购物车
if($op == 'add_cart'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $goods_id = floor(trim($_GPC['goods_id']));
    //检查是否登录
    $sku_key = trim($_GPC['sku_key']);
    $sku_desc = trim($_GPC['sku_desc']);
    $buy_num = floor(trim($_GPC['buy_num']));
    $already_buy_num = 0;
    //判断相同规格的产品是否已经存在购物车，如果存在数目相加
    $cart_info = pdo_get('vapp_order_cart',array(
        'uniacid' =>  $_W['uniacid'],
        'uid' => $user_info['uid'],
        'goods_id' => $goods_id,
        'sku_key' => $sku_key,
        'sku_desc' => $sku_desc
    ));
    if(check_data($cart_info)){
        $already_buy_num = $cart_info['buy_num'];
    }
    if($buy_num < 1){
       to_json(1,'购买数量不能小于1');
    }
    $goods_info = pdo_get('vapp_goods',array(
        'uniacid'=>$_W['uniacid'],
        'is_display'=>DISPLAY_YES,
        'is_check'=>CHECK_PASS,
        'id'=>$goods_id
    ));
    if(!check_data($goods_info)){
        to_json(1,'商品信息不存在');
    }
    if(!empty($goods_info['attr_list'])){
        $goods_info['attr_list'] = iunserializer($goods_info['attr_list']);
    }
    if(!empty($goods_info['spec_list'])){
        $goods_info['spec_list'] = iunserializer($goods_info['spec_list']);
    }
    if(!empty($goods_info['sku_list'])){
        $goods_info['sku_list'] = iunserializer($goods_info['sku_list']);
    }
    if(!empty($goods_info['thumbs'])){
        $goods_info['thumbs'] = iunserializer($goods_info['thumbs']);
    }
    $sale_price = $goods_info['sale_price'];
    //限时购价格
    if($goods_info['is_open_limit_time_buy'] == OPEN_STATUS){
        if($goods_info['limit_time_buy_start'] < TIMESTAMP && $goods_info['limit_time_buy_end'] > TIMESTAMP){
            $sale_price = $goods_info['limit_time_price'];
        }
    }

    if($goods_info['is_open_spec'] == OPEN_STATUS){
        if(empty($sku_key)){
            to_json(1,'请选择规格');
        }

        if(!in_array($sku_key,array_keys($goods_info['sku_list']))){
            to_json(1,'所选规格不存在');
        }
        if($buy_num + $already_buy_num > $goods_info['sku_list'][$sku_key]['total']){
            to_json(1,'购买数目不能超过库存量');
        }
        if($goods_info['is_open_limit_time_buy'] == OPEN_STATUS){
            if($goods_info['limit_time_buy_start'] < TIMESTAMP && $goods_info['limit_time_buy_end'] > TIMESTAMP){
                $sale_price = $goods_info[$sku_key]['limit_time_price'];
            }
        }
    }else{
        if($goods_info['total'] < $buy_num + $already_buy_num){
            to_json(1,'购买数目不能超过库存量');
        }
    }
    $data = array(
        'uniacid' => $_W['uniacid'],
        'uid' => $user_info['uid'],
        'company_id' => $goods_info['company_id'],
        'goods_id' => $goods_id,
        'sku_key' => $sku_key,
        'sku_desc' => $sku_desc,
    );
    if(check_data($cart_info)){
        $data['updatetime'] = TIMESTAMP;
        $data['buy_num'] = $already_buy_num + $buy_num;
        $status = pdo_update('vapp_order_cart',$data,array(
            'uniacid' =>  $_W['uniacid'],
            'uid' => $user_info['uid'],
            'store_type' => STORE_TYPE_OTO,
            'goods_id' => $goods_id,
            'sku_key' => $sku_key,
            'sku_desc' => $sku_desc
        ));
    }else{
        $data['buy_num'] = $buy_num;
        $data['createtime'] = TIMESTAMP;
        $status = pdo_insert('vapp_order_cart',$data);
    }
    if(!$status){
        to_json(1,'加入购物车失败');
    }
    to_json(0,'加入购物车成功');
}

//检查订单
if($op == 'check_order'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $where = "uniacid='{$_W['uniacid']}' AND uid='{$user_info['uid']}'";
    $address_id = floor(trim($_GPC['address_id']));
    if(check_id($address_id)){
        $where .= " AND id='{$address_id}'";
    }
    $address = pdo_fetch("SELECT id,realname,mobile,province,city,district,address FROM ".tablename('vapp_address')." WHERE {$where} ORDER BY isdefault DESC LIMIT 0,1");
    $orders = array();
    $ids = array();
    if(!empty($_GPC['id'])){
        $ids = explode(',',$_GPC['id']);
    }
    $total_num = 0; //总数目
    $total_price = 0;//总价格
    $postage_price = 0;//总邮费
    if(check_data($ids)){
        $post_type = ORDER_PUSH_CART;
        $sql = "SELECT a.*,b.`is_free_post`,b.`postage_type`,b.`postage_money`,b.`postage_id`,b.`title` AS goods_title,b.`is_open_spec`,b.`title` AS goods_name,b.`thumb`,b.`cost_price`,b.`market_price`,b.`sale_price`,b.`limit_time_price`,b.`weight`,b.`total`,b.`is_open_limit_time_buy`,b.`limit_time_buy_start`,b.`limit_time_buy_end`,b.`sku_list`,c.`title` AS company_title,c.`logo` AS company_logo FROM "
            .tablename('vapp_order_cart'). " a LEFT JOIN "
            .tablename('vapp_goods')." b ON a.goods_id=b.id LEFT JOIN "
            .tablename('vapp_company')." c ON a.company_id=c.id"
            ." WHERE a.uniacid='{$_W['uniacid']}' AND a.uid='{$user_info['uid']}' AND a.id IN(".implode(',',$ids).")";
        $list = pdo_fetchall($sql);
        if(check_data($list)){
            foreach($list as $k => &$v){
                $v['thumb'] = tomedia($v['thumb']);
                $v['company_logo'] = tomedia($v['company_logo']);
                $v['sku_list'] = iunserializer($v['sku_list']);
                unset($v['sku_list']);
            }
        }
    }else{
        $post_type = ORDER_PUSH_NOW;
        $goods_id = floor(trim($_GPC['goods_id']));
        $sku_key = trim($_GPC['sku_key']);
        $sku_desc = trim($_GPC['sku_desc']);
        $buy_num = trim($_GPC['buy_num']);
        $info = pdo_fetch("SELECT `company_id`,`title` AS goods_title,`is_open_spec`,`title` AS goods_name,`thumb`,`cost_price`,`market_price`,`sale_price`,`limit_time_price`,`weight`,`total`,`is_open_limit_time_buy`,`limit_time_buy_start`,`limit_time_buy_end`,`sku_list`,`is_free_post`,`postage_type`,`postage_money`,`postage_id` FROM ".tablename('vapp_goods')." WHERE uniacid='{$_W['uniacid']}' AND id='{$goods_id}'");
        $list = array();
        if(check_data($info)){
            $company_info = pdo_fetch("SELECT `title` AS company_title,`logo` AS company_logo FROM ".tablename('vapp_company')." WHERE uniacid='{$_W['uniacid']}' AND id='{$info['company_id']}'");
            if(!empty($company_info) && is_array($company_info)){
                $info['company_title'] = $company_info['company_title'];
                $info['company_logo'] = $company_info['company_logo'];
            }
            $info['thumb'] = tomedia($info['thumb']);
            $info['company_logo'] = tomedia($info['company_logo']);
            $info['sku_list'] = iunserializer($info['sku_list']);
            $info['buy_num'] = $buy_num;
            $info['sku_key'] = $sku_key;
            $info['sku_desc'] = $sku_desc;
            $info['sale_price'] = getGoodsTruePrice($info,$sku_key);
            unset($info['sku_list']);
            array_push($list,$info);
        }
    }
    foreach($list as $k1 => $v1){
        $orders[$v1['company_id']]['id'] = $v1['company_id'];
        $orders[$v1['company_id']]['title'] = $v1['company_title'];
        $orders[$v1['company_id']]['logo'] = $v1['company_logo'];
        if(!isset($orders[$v1['company_id']]['goodsList'])){
            $orders[$v1['company_id']]['goodsList'] = array();
        }
        $v1['sale_price'] = getGoodsTruePrice($v1,$v1['sku_key']);
        $v1['postage_price'] = getGoodsPostPrice($v1,$v1['sku_key'],$v1['buy_num']);
        array_push($orders[$v1['company_id']]['goodsList'],$v1);
        $total_num += $v1['buy_num'];
        $postage_price += $v1['postage_price'];
        $total_price += ($v1['buy_num']*$v1['sale_price']+$v1['postage_price']);
    }
    if(!check_data($orders)){
        to_json(1,'请选择需要下单的商品');
    }
    arrayTransByKey($orders);
    to_json(0,'返回信息',array(
        'address' => $address,
        'total_num' => $total_num,
        'postage_price' => $postage_price,
        'total_price' => $total_price,
        'list' => $orders
    ));
}

//立即购买
if($op == 'push_order'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $push_type = floor(trim($_GPC['push_type']));
    $address_id = floor(trim($_GPC['address_id']));
    if(empty($address_id)){
       to_json(1,'请选择收货地址');
    }
    $words = $_GPC['words'];
    $address = pdo_get('vapp_address',array(
        'uniacid'=>$_W['uniacid'],
        'uid'=> $user_info['uid'],
        'id'=>$address_id
    ));
    if(!check_data($address)){
        to_json(1,'请选择收货地址');
    }
    $order_no = get_order_no();
    if($push_type == ORDER_PUSH_CART){ //购物车提交
        $cart_ids = explode(',',$_GPC['id']);
        if(!check_data($cart_ids)){
            to_json(1,'请选择需要下单的商品');
        }
        $order_ids = array();//记录订单ID
        $pay_total_price = 0;//累计支付价格
        $err_num = 0;
        $err_message = '提交失败';
        //开启事务
        pdo_begin();
        foreach($cart_ids as $k => $id){
            $cart_info = pdo_get('vapp_order_cart',array(
                'uniacid'=>$_W['uniacid'],
                'uid' => $user_info['uid'],
                'id' => $id
            ));
            if(!check_data($cart_info)){
                $err_num++;
                $err_message = '购物车数据异常';
                break;
            }
            $goods_info = pdo_get('vapp_goods',array(
                'uniacid'=>$_W['uniacid'],
                'is_display'=>DISPLAY_YES,
                'is_check'=>CHECK_PASS,
                'id'=>$cart_info['goods_id']
            ));

            if(!check_data($goods_info)){
                $err_num++;
                $err_message = '商品信息不存在';
                break;
            }

            if(!empty($goods_info['attr_list'])){
                $goods_info['attr_list'] = iunserializer($goods_info['attr_list']);
            }
            if(!empty($goods_info['spec_list'])){
                $goods_info['spec_list'] = iunserializer($goods_info['spec_list']);
            }
            if(!empty($goods_info['sku_list'])){
                $goods_info['sku_list'] = iunserializer($goods_info['sku_list']);
            }
            if(!empty($goods_info['thumbs'])){
                $goods_info['thumbs'] = iunserializer($goods_info['thumbs']);
            }

            if(!empty($cart_info['sku_key']) && $goods_info['is_open_spec']){
                if(!in_array($cart_info['sku_key'],array_keys($goods_info['sku_list']))){
                    $err_num++;
                    $err_message = "商品：{$goods_info['title']},{$cart_info['sku_desc']}不存在";
                    break;
                }
                if($cart_info['sku_desc'] != $goods_info['sku_list'][$cart_info['sku_key']]['filed_1'].'-'.$goods_info['sku_list'][$cart_info['sku_key']]['filed_2']){
                    $err_num++;
                    $err_message = "商品：{$goods_info['title']}的规格发生改变，请重新购买";
                    break;
                }
            }
            //插入订单数据
            $pay_price = getGoodsTruePrice($goods_info,$cart_info['sku_key']);
            $postage_price = getGoodsPostPrice($goods_info,$cart_info['sku_key'],floor($cart_info['buy_num']));
            $cost_price = getGoodsTrueCostPrice($goods_info,$cart_info['sku_key']);
            $market_price = getGoodsTrueMarketPrice($goods_info,$cart_info['sku_key']);
            $order_data = array(
                'verify_code' => random(8,true),
                'order_no' => $order_no,
                'uniacid' => $_W['uniacid'],
                'uid' => $user_info['uid'],
                'goods_id' => $goods_info['id'],
                'company_id' => $goods_info['company_id'],
                'username' => $address['username'],
                'mobile' => $address['mobile'],
                'province' => $address['province'],
                'city' => $address['city'],
                'district' => $address['district'],
                'address' => $address['address'],
                'leave_words' => isset($words[$goods_info['company_id']])?$words[$goods_info['company_id']]:'',
                'buy_num' => floor($cart_info['buy_num']),
                'pay_price' => doubleval($pay_price),
                'pay_postage_fee' => doubleval($postage_price),
                'pay_total_price' => doubleval($pay_price*$cart_info['buy_num']+doubleval($postage_price)),
                'sku_key' => $cart_info['sku_key'],
                'sku_desc' => $cart_info['sku_desc'],
                'createtime' => TIMESTAMP
            );
            //订单分销金额
            $order_data['distribution_money'] = floatval($order_data['pay_total_price']-$order_data['pay_postage_fee']-$cost_price*$order_data['buy_num']);
            if($order_data['distribution_money'] < 0){
                $order_data['distribution_money'] = 0;
            }

            if($order_data['buy_num'] < 1){
                $err_num++;
                $err_message = "商品：{$goods_info['title']}的购买数目不能小于1";
                break;
            }
            if($order_data['pay_price'] <= 0){
                $err_num++;
                $err_message = "商品：{$goods_info['title']}的购买价格异常";
                break;
            }
            $insert_order_status = pdo_insert('vapp_order',$order_data);
            if(!$insert_order_status){
                $err_num++;
                $err_message = "商品：{$goods_info['title']}提交失败";
                break;
            }
            $insert_order_id = pdo_insertid();
            //插入订单商品信息
            $order_goods_data = array(
                'uniacid' => $_W['uniacid'],
                'uid' => $user_info['uid'],
                'order_id' => $insert_order_id,
                'company_id' => $goods_info['company_id'],
                'goods_id' => $goods_info['id'],
                'goods_name'=>$goods_info['title'],
                'thumb' => $goods_info['thumb'],
                'cost_price' => $cost_price,
                'market_price' => $market_price,
                'sale_price' => $pay_price,
                'unit' => $goods_info['unit'],
                'weight' => $goods_info['weight'],
                'sku_desc' => $cart_info['sku_desc'],
                'give_credit1' => $goods_info['give_credit1'],
                'createtime' => TIMESTAMP
            );
            $insert_order_goods_status = pdo_insert('vapp_order_goods',$order_goods_data);
            if(!$insert_order_goods_status){
                $err_num++;
                $err_message = "商品：{$goods_info['title']}信息记录失败";
                break;
            }

            //成功记录订单的id,记录到pay_log中
            array_push($order_ids,$insert_order_id);
            $pay_total_price += $order_data['pay_total_price'];
        }
        if($err_num != 0){
            pdo_rollback();
           to_json(1,$err_message);
        }

        //插入支付记录
        $pay_log_data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $user_info['uid'],
            'order_ids' => implode(SPLIT_ORDER_IDS,$order_ids),
            'out_trade_no' => $order_no,
            'order_type' => ORDER_TYPE_VAPP_GOODS,
            'pay_price' => $pay_total_price,
            'createtime' => TIMESTAMP
        );
        $insert_pay_log_status = pdo_insert('pay_log',$pay_log_data);
        if(!$insert_pay_log_status){
            pdo_rollback();
            message('支付信息，提交失败','','error');
        }
        $insert_pay_log_id = pdo_insertid();
        //清空购物车
        if(check_data($cart_ids)){
            $clean_cart_status = pdo_query("DELETE FROM ".tablename('vapp_order_cart')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$user_info['uid']}' AND id IN (".implode(',',$cart_ids).")");
            if(!$clean_cart_status){
                pdo_rollback();
                to_json(1,'购物车信息删除失败');
            }
        }
        //提交事务
        pdo_commit();
        to_json(0,'提交成功，正在跳转到支付',array(
            'pay_log_id' => $insert_pay_log_id
        ));
    }else{ //订单提交
        $goods_id = floor(trim($_GPC['goods_id']));
        $sku_key = trim($_GPC['sku_key']);
        $sku_desc = trim($_GPC['sku_desc']);
        $buy_num = floor(trim($_GPC['buy_num']));
        if(!check_id($goods_id)){
            to_json(1,'请选择要下单的商品');
        }
        $goods_info = pdo_get('vapp_goods',array(
            'uniacid'=>$_W['uniacid'],
            'is_display'=>DISPLAY_YES,
            'is_check'=>CHECK_PASS,
            'id'=>$goods_id
        ));
        if(!check_data($goods_info)){
            message('商品信息不存在','','error');
        }
        if(!empty($goods_info['attr_list'])){
            $goods_info['attr_list'] = iunserializer($goods_info['attr_list']);
        }
        if(!empty($goods_info['spec_list'])){
            $goods_info['spec_list'] = iunserializer($goods_info['spec_list']);
        }
        if(!empty($goods_info['sku_list'])){
            $goods_info['sku_list'] = iunserializer($goods_info['sku_list']);
        }
        if(!empty($goods_info['thumbs'])){
            $goods_info['thumbs'] = iunserializer($goods_info['thumbs']);
        }

        if(!empty($sku_key) && $goods_info['is_open_spec']){
            if(!in_array($sku_key,array_keys($goods_info['sku_list']))){
                to_json(1,"商品：{$goods_info['title']},{$sku_desc}不存在");
            }
            if($sku_desc != $goods_info['sku_list'][$sku_key]['filed_1'].SPLIT_GOODS_SKU_DESC.$goods_info['sku_list'][$sku_key]['filed_2']){
               to_json(1,"商品：{$goods_info['title']}的规格发生改变，请重新购买");
            }
        }
        //插入订单数据
        $pay_price = getGoodsTruePrice($goods_info,$sku_key);
        $postage_price = getGoodsPostPrice($goods_info,$sku_key,floor($buy_num));
        $cost_price = getGoodsTrueCostPrice($goods_info,$sku_key);
        $market_price = getGoodsTrueMarketPrice($goods_info,$sku_key);
        $order_data = array(
            'verify_code' => random(8,true),
            'order_no' => $order_no,
            'uniacid' => $_W['uniacid'],
            'uid' => $user_info['uid'],
            'goods_id' => $goods_id,
            'company_id' => $goods_info['company_id'],
            'username' => $address['username'],
            'mobile' => $address['mobile'],
            'province' => $address['province'],
            'city' => $address['city'],
            'district' => $address['district'],
            'address' => $address['address'],
            'leave_words' => isset($words[$goods_info['company_id']])?$words[$goods_info['company_id']]:'',
            'buy_num' => floor($buy_num),
            'pay_price' => doubleval($pay_price),
            'pay_postage_fee' => doubleval($postage_price),
            'pay_total_price' => doubleval($pay_price*$buy_num+$postage_price),
            'sku_key' => $sku_key,
            'sku_desc' => $sku_desc,
            'createtime' => TIMESTAMP
        );
        //订单分销金额
        $order_data['distribution_money'] = floatval($order_data['pay_total_price']-$order_data['pay_postage_fee']-$cost_price*$order_data['buy_num']);
        if($order_data['distribution_money'] < 0){
            $order_data['distribution_money'] = 0;
        }
        if($order_data['buy_num'] < 1){
            message("商品：{$goods_info['title']}的购买数目不能小于1",'','error');
        }
        if($order_data['pay_price'] <= 0){
            message("商品：{$goods_info['title']}的购买价格异常",'','error');
        }
        //开启事务
        pdo_begin();
        $insert_order_status = pdo_insert('vapp_order',$order_data);
        if(!$insert_order_status){
            pdo_rollback();
            message("商品：{$goods_info['title']}提交失败",'','error');
        }
        $insert_order_id = pdo_insertid();
        //插入订单商品信息
        $order_goods_data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $user_info['uid'],
            'order_id' => $insert_order_id,
            'company_id' => $goods_info['company_id'],
            'goods_id' => $goods_info['id'],
            'goods_name'=>$goods_info['title'],
            'thumb' => $goods_info['thumb'],
            'cost_price' => $cost_price,
            'market_price' => $market_price,
            'sale_price' => $pay_price,
            'unit' => $goods_info['unit'],
            'weight' => $goods_info['weight'],
            'sku_desc' => $sku_desc,
            'give_credit1' => $goods_info['give_credit1'],
            'createtime' => TIMESTAMP
        );
        $insert_order_goods_status = pdo_insert('vapp_order_goods',$order_goods_data);
        if(!$insert_order_goods_status){
            pdo_rollback();
            to_json(1,"商品：{$goods_info['title']}信息记录失败");
        }

        //插入支付记录
        $pay_log_data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $user_info['uid'],
            'pay_method' => $pay_method,
            'order_ids' => $insert_order_id,
            'out_trade_no' => $order_no,
            'pay_price' => $order_data['pay_total_price'],
            'order_type' => ORDER_TYPE_VAPP_GOODS,
            'createtime' => TIMESTAMP
        );
        $insert_pay_log_status = pdo_insert('pay_log',$pay_log_data);
        if(!$insert_pay_log_status){
            pdo_rollback();
            to_json(1,'支付信息，提交失败');
        }
        //提交事务
        $insert_pay_log_id = pdo_insertid();
        pdo_commit();
        to_json(0,'提交成功，正在跳转到支付',array(
            'pay_log_id' => $insert_pay_log_id
        ));
    }
}

//商品详情
if($op == 'get_goods_detail'){
    $id = floor(trim($_GPC['id']));
    $goods = pdo_fetch("SELECT id,title,thumb,thumbs,market_price,sale_price,unit,total,sale_count,attr_list,spec_list,sku_list FROM ".tablename('vapp_goods')." WHERE uniacid='{$_W['uniacid']}' AND id='{$id}' AND is_display='1' AND is_check='1'");
    if(!check_data($goods)){
        to_json(1,'商品信息不存在');
    }
    if(!empty($goods['attr_list'])){
        $goods['attr_list'] = iunserializer($goods['attr_list']);
        arrayTransByKey($goods['attr_list']);
    }
    if(!empty($goods['spec_list'])){
        $goods['spec_list'] = iunserializer($goods['spec_list']);
        arrayTransByKey($goods['spec_list']);
    }
    if(!empty($goods['sku_list'])){
        $goods['sku_list'] = iunserializer($goods['sku_list']);
    }
    if(!empty($goods['thumbs'])){
        $goods['thumbs'] = iunserializer($goods['thumbs']);
    }else{
        $goods['thumbs'] = array();
    }
    array_push($goods['thumbs'],$goods['thumb']);
    foreach ($goods['thumbs'] as $k => &$v){
        $v = tomedia($v);
    }
    $goods['thumb'] = tomedia($goods['thumb']);
    $goods['detail'] = htmlspecialchars_decode($goods['detail']);
    to_json(0,'返回商品详情',$goods);
}

//商品列表
if($op == 'get_category_list'){
    $keyword = trim($_GPC['keyword']);
    $categories = pdo_fetchall("SELECT id,title FROM ".tablename('vapp_goods_category')." WHERE uniacid='{$_W['uniacid']}' AND is_display='1' ORDER BY order_by DESC LIMIT 0,50");
    if(!check_data($categories)){
        to_json(1,'分类信息不存在');
    }
    foreach ($categories as $k => &$v){
        $where = "uniacid='{$_W['uniacid']}' AND is_display='1' AND is_recommend='1' AND is_check='1' AND category_id='{$v['id']}'";
        $v['is_show'] = 0;
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        $v['goods_list'] = pdo_fetchall("SELECT id,title,thumb,market_price,sale_price,unit,total,sale_count FROM ".tablename('vapp_goods')." WHERE {$where} ORDER BY order_by DESC LIMIT 0,20");
        if(check_data($v['goods_list'])){
            $v['is_show'] = 1;
            foreach ($v['goods_list'] as $k1 => &$v2){
                $v2['thumb'] = tomedia($v2['thumb']);
            }
        }
    }
    to_json(0,'返回分类和商品信息',$categories);
}

//评论圈子
if($op == 'push_circle_talk'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $circle_id = floor(trim($_GPC['circle_id']));
    if(!check_id($circle_id)){
        to_json(1,'圈子ID错误');
    }
    $circle = pdo_get('vapp_circle',array(
        'uniacid' => $_W['uniacid'],
        'id' => $circle_id
    ));
    if(!check_data($circle)){
        to_json(1,'圈子信息不存在');
    }
    $thumb = trim($_GPC['thumb']);
    if(!empty($thumb)){
        $thumb = base64ToImage($thumb);
    }
    $content = trim($_GPC['content']);
    if(empty($content) && empty($thumb)){
        message(1,'请输入评论内容或上传图片');
    }
    pdo_begin();
    $status1 = pdo_update('vapp_circle',array(
        'updatetime' => TIMESTAMP,
        'talk_num' => $circle['talk_num']+1
    ),array(
        'uniacid' => $_W['uniacid'],
        'id' => $circle_id
    ));
    if(!$status1){
        pdo_rollback();
        to_json(1,'评论数量增加失败');
    }
    $status2 = pdo_insert('vapp_circle_talk',array(
        'uniacid' => $_W['uniacid'],
        'uid' => $user_info['uid'],
        'circle_id' => $circle_id,
        'content' => $content,
        'thumb' => $thumb,
        'createtime' => TIMESTAMP
    ));
    if(!$status2){
        pdo_rollback();
        to_json(1,'评论记录失败');
    }
    $member = pdo_get('vapp_member',array(
        'uniacid' => $_W['uniacid'],
        'uid' => $user_info['uid']
    ),array(
        'uid','mobile','nickname','realname'
    ));
    if(!check_data($member)){
        pdo_rollback();
        to_json(1,'当前会员信息不存在');
    }
    $member['show_name'] = !!$member['nickname']?$member['nickname']:(!!$member['realname']?$member['realname']:$member['mobile']);
    $member['content'] = $content;
    $member['thumb'] = tomedia($thumb);
    pdo_commit();
    to_json(0,'评论成功',$member);
}

//点赞圈子
if($op == 'push_circle_like'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $circle_id = floor(trim($_GPC['circle_id']));
    if(!check_id($circle_id)){
        to_json(1,'圈子ID错误');
    }
    $circle = pdo_get('vapp_circle',array(
        'uniacid' => $_W['uniacid'],
        'id' => $circle_id
    ));
    if(!check_data($circle)){
        to_json(1,'圈子信息不存在');
    }
    $is_exist_like = pdo_get('vapp_circle_like',array(
        'uniacid' => $_W['uniacid'],
        'circle_id' => $circle_id,
        'uid' => $user_info['uid']
    ));
    if(check_data($is_exist_like)){
        //已经点赞就取消
        pdo_begin();
        $status1 = pdo_update('vapp_circle',array(
            'updatetime' => TIMESTAMP,
            'like_num' => $circle['like_num']-1
        ),array(
            'uniacid' => $_W['uniacid'],
            'id' => $circle_id
        ));
        if(!$status1){
            pdo_rollback();
            to_json(1,'点赞数量减少失败');
        }
        $status2 = pdo_delete('vapp_circle_like',array(
            'uniacid' => $_W['uniacid'],
            'uid' => $user_info['uid'],
            'circle_id' => $circle_id
        ));
        if(!$status2){
            pdo_rollback();
            to_json(1,'点赞记录删除失败');
        }
        $member = pdo_get('vapp_member',array(
            'uniacid' => $_W['uniacid'],
            'uid' => $user_info['uid']
        ),array(
            'uid','mobile','nickname','realname'
        ));
        if(!check_data($member)){
            pdo_rollback();
            to_json(1,'当前会员信息不存在');
        }
        $member['show_name'] = !!$member['nickname']?$member['nickname']:(!!$member['realname']?$member['realname']:$member['mobile']);
        pdo_commit();
        to_json(0,'取消成功',$member);
    }else{
        pdo_begin();
        $status1 = pdo_update('vapp_circle',array(
            'updatetime' => TIMESTAMP,
            'like_num' => $circle['like_num']+1
        ),array(
            'uniacid' => $_W['uniacid'],
            'id' => $circle_id
        ));
        if(!$status1){
            pdo_rollback();
            to_json(1,'点赞数量增加失败');
        }
        $status2 = pdo_insert('vapp_circle_like',array(
            'uniacid' => $_W['uniacid'],
            'uid' => $user_info['uid'],
            'circle_id' => $circle_id,
            'createtime' => TIMESTAMP
        ));
        if(!$status2){
            pdo_rollback();
            to_json(1,'点赞记录失败');
        }
        $member = pdo_get('vapp_member',array(
            'uniacid' => $_W['uniacid'],
            'uid' => $user_info['uid']
        ),array(
            'uid','mobile','nickname','realname'
        ));
        if(!check_data($member)){
            pdo_rollback();
            to_json(1,'当前会员信息不存在');
        }
        $member['show_name'] = !!$member['nickname']?$member['nickname']:(!!$member['realname']?$member['realname']:$member['mobile']);

        pdo_commit();
        to_json(0,'点赞成功',$member);
    }
}

//获取圈子列表
if($op == 'get_circle_list'){
    $page = getApartPageNo();
    $psize = 20;
    $pindex = ($page - 1)*$psize;
    $type = floor(trim($_GPC['type']));
    $user_info = auth_check($_GPC['token']);
    if($type == 4 && empty($user_info)){
        to_json(-1,'请先登录');
    }
    $where = "a.uniacid='{$_W['uniacid']}' AND a.is_display='1'";

    //关注
    if($type == 4){
       $where .= " AND a.uid IN (SELECT focus_uid FROM ".tablename('vapp_focus')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$user_info['uid']}')";
    }

    //红人
    if($type == 3){
        $where .= " AND a.uid IN (SELECT focus_uid FROM (SELECT COUNT(1) AS count,focus_uid FROM ".tablename('vapp_focus')." GROUP BY focus_uid ORDER BY count DESC LIMIT 0,100) A)";
    }
    switch ($type){
        case 1:$where.=" ORDER BY a.order_by DESC";break;
        case 2:$where .= " ORDER BY a.look_num DESC";break;
        default:$where .= " ORDER BY a.id DESC";
    }
    $list = pdo_fetchall("SELECT a.id,a.type,a.content,a.src,a.createtime,b.mobile,b.nickname,b.realname,b.avatar FROM ".tablename('vapp_circle')." a LEFT JOIN ".tablename('vapp_member')." b ON a.uid=b.uid WHERE {$where} LIMIT {$pindex},{$psize}");
    //处理$list
    if(check_data($list)){
        foreach ($list as $k => &$v){
            $v['avatar'] = tomedia($v['avatar']);
            $v['src'] = tomedia($v['src']);
            $v['createtime'] = friend_date($v['createtime']);
            $v['show_name'] = !!$v['nickname']?$v['nickname']:(!!$v['realname']?$v['realname']:$v['mobile']);
            $v['talk_list'] = pdo_fetchall("SELECT a.id,a.content,a.thumb,b.uid,b.avatar,b.mobile,b.nickname,b.realname FROM ".tablename('vapp_circle_talk')." a LEFT JOIN ".tablename('vapp_member')." b ON a.uid=b.uid WHERE a.uniacid='{$_W['uniacid']}' AND a.circle_id='{$v['id']}' ORDER BY a.id DESC LIMIT 0,100");;
            if(check_data($v['talk_list'])){
                foreach ($v['talk_list'] as $k2 => &$v2){
                    $v2['show_name'] = !!$v2['nickname']?$v2['nickname']:(!!$v2['realname']?$v2['realname']:$v2['mobile']);
                    $v2['avatar'] = tomedia($v2['avatar']);
                    $v2['thumb'] = tomedia($v2['thumb']);
                }
            }
            $v['like_list'] = pdo_fetchall("SELECT a.id,b.uid,b.avatar,b.mobile,b.nickname,b.realname FROM ".tablename('vapp_circle_like')." a LEFT JOIN ".tablename('vapp_member')." b ON a.uid=b.uid WHERE a.uniacid='{$_W['uniacid']}' AND a.circle_id='{$v['id']}' ORDER BY a.id DESC LIMIT 0,100");
            if(check_data($v['like_list'])){
                foreach ($v['like_list'] as $k3 => &$v3){
                    $v3['show_name'] = !!$v3['nickname']?$v3['nickname']:(!!$v3['realname']?$v3['realname']:$v3['mobile']);
                    $v3['avatar'] = tomedia($v3['avatar']);
                }
            }
            $v['is_like'] = 0;
            if(check_data($user_info)){
                $is_exist_like = pdo_get('vapp_circle_like',array(
                    'uniacid' => $_W['uniacid'],
                    'circle_id' => $v['id'],
                    'uid' => $user_info['uid']
                ));
                if(check_data($is_exist_like)){
                   $v['is_like'] = 1;
                }
            }
        }
    }
    to_json(0,'',array(
        'push_num' => pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('vapp_circle')." WHERE uniacid='{$_W['uniacid']}' AND is_display='1'"),
        'member_num' => pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('vapp_member')." WHERE uniacid='{$_W['uniacid']}'"),
        'sum_credit2' => pdo_fetchcolumn("SELECT SUM(credit2) FROM ".tablename('vapp_member')." WHERE uniacid='{$_W['uniacid']}'"),
        'list' => $list
    ));
}

//提交圈子
if($op == 'push_circle'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $data = array(
        'uniacid' => $_W['uniacid'],
        'uid' => $user_info['uid'],
        'content' => $_GPC['content'],
        'src' => trim($_GPC['src']),
        'type' => 0,
        'createtime' => TIMESTAMP
    );
    if(empty($data['content'])){
        to_json(1,'请输入内容');
    }
    if(!empty($data['src'])){
        $src = base64ToImage($data['src']);
        if(!empty($src)){
            $ext = strtolower(trim(substr(strrchr($src, '.'), 1)));
            if(!in_array($ext,array('gif','png','jpeg','jpg'))){
                $data['type'] = 1;
            }
        }
        $data['src'] = $src;
    }
    $status = pdo_insert('vapp_circle',$data);
    if(!$status){
        to_json(1,'发表失败');
    }
    to_json(0,'发表成功');
}

//获取发现菜单
if($op == 'get_find'){
    $menus = pdo_fetchall("SELECT id,title,thumb,page FROM ".tablename('vapp_find')." WHERE uniacid='{$_W['uniacid']}' AND is_display='1' ORDER BY order_by DESC LIMIT 0,20");
    if(!check_data($menus)){
        to_json(1,'没有设置相关菜单');
    }
    foreach ($menus as $k => &$v){
        $v['thumb'] = tomedia($v['thumb']);
    }
    to_json(0,'返回菜单列表',$menus);
}

//获取app配置信息
if($op == 'get_config'){
    $config = pdo_get('vapp_config',array(
        'uniacid' => $_W['uniacid']
    ));
    if(!check_data($config)){
        to_json(1,'配置错误：-1');
    }
    $setting = iunserializer($config['setting']);
    if(!check_data($setting)){
        to_json(1,'配置错误：-2');
    }
    to_json(0,'返回配置信息',$setting);
}

//获取栏目文章信息
if($op == 'get_article'){
    $cid = floor(trim($_GPC['cid']));
    if(!check_id($cid)){
        to_json(1,'栏目ID不存在');
    }
    $id = floor(trim($_GPC['id']));
    if(check_id($id)){
        $item = pdo_fetch("SELECT id,category_id,title,detail,createtime FROM ".tablename('vapp_article')." WHERE uniacid='{$_W['uniacid']}' AND category_id='{$cid}' AND id='{$id}' AND is_display='1'");
    }else{
        $item = pdo_fetch("SELECT id,category_id,title,detail,createtime FROM ".tablename('vapp_article')." WHERE uniacid='{$_W['uniacid']}' AND category_id='{$cid}' AND is_display='1' ORDER BY order_by DESC LIMIT 0,1");
    }
    if(!check_data($item)){
        to_json(1,'没有找到相关文章');
    }
    $item['detail'] = htmlspecialchars_decode($item['detail']);
    $item['createtime'] = date('Y年m月d日',$item['createtime']);
    $list = pdo_fetchall("SELECT id,title,detail,thumb,createtime FROM ".tablename('vapp_article')." WHERE uniacid='{$_W['uniacid']}' AND category_id='{$cid}' AND id!='{$item['id']}' AND is_display='1' ORDER BY order_by DESC LIMIT 0,50");
    if(check_data($list)){
        foreach ($list as $k => &$v){
            $v['thumb'] = tomedia($v['thumb']);
            $v['detail'] = htmlspecialchars_decode($v['detail']);
            $v['createtime'] = date('Y年m月d日',$v['createtime']);
        }
    }
    to_json(0,'返回文章详情和推荐列表',array(
        'info' => $item,
        'list' => $list
    ));
}

//获取公司信息
if($op == 'get_company_info'){
    $id = floor(trim($_GPC['id']));
    if(!check_id($id)){
        to_json(1,'公司ID错误');
    }
    $info = pdo_fetch("SELECT id,title,tel,mobile,email,wechat,lat,lng,province,city,district,address,copyright FROM ".tablename('vapp_company')." WHERE uniacid='{$_W['uniacid']}' AND id='{$id}'");
    if(!check_data($info)){
        to_json(1,'公司信息不存在');
    }
    $menus = pdo_fetchall("SELECT id,title,thumb FROM ".tablename('vapp_article_category')." WHERE uniacid='{$_W['uniacid']}' AND company_id='{$info['id']}' AND is_display='1' ORDER BY order_by DESC LIMIT 0,50");
    if(check_data($menus)){
        foreach ($menus as $k => &$v){
            $v['thumb'] = tomedia($v['thumb']);
        }
    }
    to_json(0,'返回公司信息',array(
        'info' => $info,
        'menus' => $menus
    ));
}

//联盟
if($op == 'union'){
    //获取所有的分类
    $categories = array(
        0 => array(
            'id' => 0,
            'title' => '特别推荐'
        )
    );
    $sql_categories = pdo_fetchall("SELECT id,title FROM ".tablename('vapp_company_category')." WHERE uniacid='{$_W['uniacid']}' AND is_display='1' ORDER BY order_by DESC LIMIT 0,50");
    if(check_data($sql_categories)){
        foreach ($sql_categories as $k => $v){
            $categories[$k+1]['id'] = $v['id'];
            $categories[$k+1]['title'] = $v['title'];
        }
    }
    //获取所有的公司
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $category_id = $_GPC['cid'];
    $where = "uniacid='{$_W['uniacid']}' AND is_display='1'";
    if(!check_id($category_id)){
        $where .= " AND is_recommend='1'";
    }else{
        $where .= " AND category_id='{$category_id}'";
    }
    $where .= " ORDER BY order_by DESC,createtime DESC LIMIT {$pindex},{$psize}";
    $companies = array();
    $sql_companies = pdo_fetchall("SELECT id,thumb,logo,title,`desc` FROM ".tablename('vapp_company')." WHERE {$where}");
    if(check_data($sql_companies)){
        foreach ($sql_companies as $k => &$v){
            $v['logo'] = tomedia($v['logo']);
            $v['thumb'] = tomedia($v['thumb']);
        }
        $companies = $sql_companies;
    }
    //返回数据
    to_json(0,'返回分类列表',array(
        'categories' => $categories,
        'companies' => $companies
    ));
}

//发送验证码
if($op == 'send_code'){
    $res = sendCode($_GPC['mobile']);
    if(is_error($res)){
        to_json(1,$res['message']);
    }
    to_json(0,'发送成功');
}

//会员登录
if($op == 'login'){
    $mobile = trim($_GPC['mobile']);
    if(!check_mobile($mobile)){
        to_json(1,'手机号格式错误');
    }
    $password = $_GPC['password'];
    if(empty($password)){
        to_json(1,'请输入密码');
    }
    $member = pdo_get('vapp_member',array(
        'uniacid' => $_W['uniacid'],
        'mobile' => $mobile
    ));
    if(!check_data($member)){
        to_json(1,'会员信息不存在');
    }
    if(!check_md5_password($member['password'],$password,$member['salt'])){
        to_json(1,'密码输入错误，请重试');
    }
    $token = auth_login(array(
        'uniacid' => $_W['uniacid'],
        'uid' => $member['uid'],
        'ip' => CLIENT_IP,
        'time' => TIMESTAMP
    ));
    to_json(0,'登录成功',array('token' => $token));
}

//会员注册
if($op == 'register'){
    $mobile = trim($_GPC['mobile']);
    if(!check_mobile($mobile)){
        to_json(1,'手机号格式错误');
    }
    $is_exist = pdo_get('vapp_member',array(
        'uniacid' => $_W['uniacid'],
        'mobile' => $mobile
    ));
    if(check_data($is_exist)){
        to_json(1,'手机号已被注册');
    }
    $password = $_GPC['password'];
    if(empty($password)){
        to_json(1,'请输入密码');
    }
    if(mb_strlen($password,'utf-8') < 6){
        to_json(1,'密码不能少于6位');
    }
    $repassword = $_GPC['repassword'];
    if(empty($repassword)){
        to_json(1,'请再次输入密码');
    }
    if($password != $repassword){
        to_json(1,'两次输入密码不一致');
    }
    $code = trim($_GPC['code']);
    if(empty($code)){
        to_json(1,'请输入验证码');
    }
    $check = verifyCode($mobile,$code);
    if(is_error($check)){
        to_json(1,$check['message']);
    }
    $salt = random(8);
    $status = pdo_insert('vapp_member',array(
        'uniacid' => $_W['uniacid'],
        'mobile' => $mobile,
        'password' => md5_password($password,$salt),
        'salt' => $salt,
        'parent_uid' => floor(trim($_GPC['parent_uid'])),
        'createtime' => TIMESTAMP
    ));
    if(!$status){
        to_json(1,'注册失败');
    }
    $token = auth_login(array(
        'uniacid' => $_W['uniacid'],
        'uid' => pdo_insertid(),
        'ip' => CLIENT_IP,
        'time' => TIMESTAMP
    ));
    to_json(0,'注册成功',array('token'=>$token));
}

//找回密码
if($op == 'forget'){
    $mobile = trim($_GPC['mobile']);
    if(!check_mobile($mobile)){
        to_json(1,'手机号格式错误');
    }
    $member = pdo_get('vapp_member',array(
        'uniacid' => $_W['uniacid'],
        'mobile' => $mobile
    ));
    if(!check_data($member)){
        to_json(1,'会员信息不存在');
    }
    $password = $_GPC['password'];
    if(empty($password)){
        to_json(1,'请输入新密码');
    }
    if(mb_strlen($password,'utf-8') < 6){
        to_json(1,'新密码不能少于6位');
    }
    $repassword = $_GPC['repassword'];
    if(empty($repassword)){
        to_json(1,'请再次输入新密码');
    }
    if($password != $repassword){
        to_json(1,'两次输入密码不一致');
    }
    $code = trim($_GPC['code']);
    if(empty($code)){
        to_json(1,'请输入验证码');
    }
    $check = verifyCode($mobile,$code);
    if(is_error($check)){
       to_json(1,$check['message']);
    }
    $status = pdo_update('vapp_member',array(
        'password' => md5_password($password,$member['salt']),
        'updatetime' => TIMESTAMP
    ),array(
        'uniacid' => $_W['uniacid'],
        'uid' => $member['uid'],
        'mobile' => $mobile
    ));
    if(!$status){
        to_json(1,'找回失败');
    }
    $token = auth_login(array(
        'uniacid' => $_W['uniacid'],
        'uid' => $member['uid'],
        'ip' => CLIENT_IP,
        'time' => TIMESTAMP
    ));
    to_json(0,'找回成功',array('token'=>$token));
}

//修改资料
if($op == 'update_user_info'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $data = array(
        'avatar' => trim($_GPC['avatar']),
        'nickname' => trim($_GPC['nickname']),
        'gender' => floor(trim($_GPC['gender'])) == 1?'男':(floor(trim($_GPC['gender'])) == 2?'女':'保密'),
        'age' => floor(trim($_GPC['age'])),
        'province' => trim($_GPC['province']),
        'city' => trim($_GPC['city']),
        'district' => trim($_GPC['district']),
        'updatetime' => TIMESTAMP
    );
    //如果是数据库以前的头像地址，则删除，不进行修改
    if(preg_match("/^(http:\/\/|https:\/\/).*$/",$data['avatar'])){
        unset($data['avatar']);
    }
    //base64转图像
    if(!empty($data['avatar'])){
        $data['avatar'] = base64ToImage($data['avatar']);
    }
    $status = pdo_update('vapp_member',$data,array(
        'uniacid' => $_W['uniacid'],
        'uid' => $user_info['uid']
    ));
    if(!$status){
        to_json(1,'修改失败');
    }
    to_json(0,'修改成功');
}

//获取会员信息
if($op == 'get_user_info'){
    $user_info = auth_check($_GPC['token']);
    if(empty($user_info)){
        to_json(-1,'请先登录');
    }
    $member = pdo_get('vapp_member',array(
        'uniacid' => $_W['uniacid'],
        'uid' => $user_info['uid']
    ));
    if(!check_data($member)){
        to_json(1,'会员信息不存在');
    }
    $member['avatar'] = tomedia($member['avatar']);
    $member['gender'] == 1?'男':($member['gender'] == 2?'女':'保密');
    $member['createtime'] = date('Y年m月d日',$member['createtime']);
    to_json(0,'返回会员信息',$member);
}


message('访问出错','','error');
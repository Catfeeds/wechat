<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/14
 * Time: 10:29
 */
defined('IN_IA') or exit('Access Denied');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
load()->app('tpl');
load()->func('check');
load()->func('notice');
$setting = iunserializer(pdo_fetchcolumn("SELECT setting FROM ".tablename('fangyuanbao_old_config')." WHERE uniacid='{$_W['uniacid']}'"));
if(!check_data($setting) || $setting['status'] != IS_STATUS){
    message('平台未打开二手物品服务','','error');
}
$user = pdo_get('fangyuanbao_user',array(
    'uniacid' => $_W['uniacid'],
    'uid' => $_W['member']['uid']
));
if($do == 'fee'){
    if($_W['isajax']){
        if(!empty($user) && is_array($user)){
            if($user['product_key'] == 3){
                message('您已购买最高等级的套餐，无需重复购买','','error');
            }
        }
        $ac = trim($_GPC['ac']);
        //检查所属
        $parent_uid = floor(trim($_GPC['parent_uid']));
        if(empty($parent_uid)){
            message('请输入所属会员ID','','error');
        }
        $relation = pdo_fetchcolumn("SELECT relation FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'");
        if(!empty($relation)){
            $parents = explode('-',$relation);
            if($parents[0] != $parent_uid){
                message('输入的会员ID与所属的会员ID不匹配','','error');
            }
        }
        $parent = pdo_get('mc_members',array(
            'uniacid' => $_W['uniacid'],
            'uid' => $parent_uid
        ));
        if(empty($parent)){
            message('所属信息不存在','','error');
        }
        if($ac == 'check'){
            message($parent,'','success');
        }else{
            $status = pdo_update('mc_members',array(
                'relation' => $parent['uid'].SPLIT_RELATION.$parent['relation'],
                'updatetime' => TIMESTAMP
            ),array(
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['member']['uid']
            ));
            if(!$status){
                message('关系建立失败','','error');
            }
            $type = floor(trim($_GPC['type']));
            if(!in_array($type,array(1,2,3,4))){
                message('套餐类型选择错误','','error');
            }
            $product_key = in_array($type,array(3,4))?3:1;
            if($type == 1){
                $pay_price = 300;
            }elseif($type == 2){
                //消耗10个方圆宝
                $reduce_num = 10;
                $fybCount = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND status=0");
                if($fybCount < $reduce_num){
                    message("你当前拥有{$fybCount}个方圆宝，不足抵扣",'','error');
                }
                //获取的时候截取到所需个数
                $fybList = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND status=0 ORDER BY createtime DESC LIMIT 0,{$reduce_num}");
                foreach($fybList as $k => $fyb){
                    if($k+1 > $reduce_num)break;
                    pdo_update('fangyuanbao_queue',array(
                        'status' => 1,
                        'is_buy' => 1 //购物抵扣
                    ),array(
                        'uniacid' => $_W['uniacid'],
                        'uid'=>$_W['member']['uid'],
                        'id'=>$fyb['id'],
                        'status'=>0
                    ));
                }
                $user_data = array(
                    'product_key' => $product_key,
                    'price' => '300',
                    'note' => '10个方圆宝'
                );
                if(!empty($user)){
                    $user_data['updatetime'] = TIMESTAMP;
                    pdo_update('fangyuanbao_user',$user_data,array(
                        'uniacid' => $_W['uniacid'],
                        'uid' => $_W['member']['uid']
                    ));
                }else{
                    $user_data['uniacid'] = $_W['uniacid'];
                    $user_data['uid'] = $_W['member']['uid'];
                    $user_data['createtime'] = TIMESTAMP;
                    pdo_insert('fangyuanbao_user',$user_data);
                }
                notice_send_simple_text($_W['member']['uid'],"您已成功购买A套餐，共消费10个方圆宝");
                message('购买成功',referer(),'success');
            }elseif($type == 3){
                $pay_price = 1500;
            }elseif($type == 4){
                //消耗10个方圆宝
                $reduce_num = 10;
                $fybCount = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND status=0");
                if($fybCount < $reduce_num){
                    message("你当前拥有{$fybCount}个方圆宝，不足抵扣",'','error');
                }
                //获取的时候截取到所需个数
                $fybList = pdo_fetchall("SELECT * FROM ".tablename('fangyuanbao_queue')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND status=0 ORDER BY createtime DESC LIMIT 0,{$reduce_num}");
                foreach($fybList as $k => $fyb){
                    if($k+1 > $reduce_num)break;
                    pdo_update('fangyuanbao_queue',array(
                        'status' => 1,
                        'is_buy' => 1 //购物抵扣
                    ),array(
                        'uniacid' => $_W['uniacid'],
                        'uid'=>$_W['member']['uid'],
                        'id'=>$fyb['id'],
                        'status'=>0
                    ));
                }
                $pay_price = 1300;
            }
            $order_no = generateOrderSnByBuyTodayTradeCount();
            $data = array(
                'uniacid' => $_W['uniacid'],
                'uid'=>$_W['member']['uid'],
                'order_no' => $order_no,
                'product_key' => $product_key,
                'pay_price' => $pay_price,
                'createtime' => TIMESTAMP
            );
            if($data['pay_price'] <= 0){
                message('支付金额错误','','error');
            }
            pdo_begin();
            $status1 = pdo_insert('fangyuanbao_user_order',$data);
            if(!$status1){
                pdo_rollback();
                message('订单生成失败','','error');
            }
            $insert_order_id = pdo_insertid();
            if(!$insert_order_id){
                pdo_rollback();
                message('订单生成失败','','error');
            }
            //插入支付记录
            $pay_log_data = array(
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['member']['uid'],
                'order_ids' => $insert_order_id,
                'out_trade_no' => $order_no,
                'pay_price' => $data['pay_price'],
                'order_type' => ORDER_TYPE_OLD_FEE,
                'createtime' => TIMESTAMP
            );
            $status2  = pdo_insert('pay_log',$pay_log_data);
            if(!$status2){
                pdo_rollback();
                message('支付信息，提交失败','','error');
            }
            $insert_pay_log_id = pdo_insertid();
            if($insert_pay_log_id == false){
                pdo_rollback();
                message('支付信息，提交失败','','error');
            }
            //提交事务
            pdo_commit();
            message('提交成功，正在跳转',url('mc/pay/display',array('id'=>$insert_pay_log_id)),'success');
        }
    }
}elseif($do == 'display'){
    if ($_W['isajax']) {
        $user = pdo_get('fangyuanbao_user',array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid']
        ));
        if(!check_data($user) && $do != 'fee'){
            message('请先缴纳服务费',url('old/display/fee'),'error');
        }
        if(floor(trim($_GPC['is_agree'])) != AGREE_YES){
            message('请阅读并同意二手交易规则','','error');
        }
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'title' => trim($_GPC['title']),
            'price' => round(floatval(trim($_GPC['price']))),
            'desc' => $_GPC['desc'],
            'province' => $_GPC['area']['province'],
            'city' => $_GPC['area']['city'],
            'district' => $_GPC['area']['district'],
            'address' => $_GPC['address'],
            'username' => trim($_GPC['username']),
            'tel' => trim($_GPC['tel']),
            'qq' => trim($_GPC['qq']),
            'wechat' => trim($_GPC['wechat']),
            'total' => floor(trim($_GPC['total'])),
            'createtime' => TIMESTAMP
        );
        $error = array(
            'title' => '请输入商品名称',
            'price' => '请输入商品价格',
            'total' => '请输入商品数量',
            'desc' => '请输入商品描述',
            'province' => '请选择省份',
            'city' => '请选择城市',
            'district' => '请选择区县',
            'username' => '请输入联系人姓名',
            'tel' => '请输入联系电话'
        );
        foreach ($error as $k => $message) {
            if (empty($data[$k])) {
                message($message, '', 'error');
            }
        }
        if (empty($_FILES['thumbs']['name']) || empty($_FILES['thumbs']['name'][0]) || !is_array($_FILES['thumbs']['name'])) {
            message('请上传商品图片', '', 'error');
        }
        if (count($_FILES['thumbs']['name']) > 3) {
            message('最多只能上传3张图片', '', 'error');
        }
        $upload_info = apply_upload_multi_image_file($_FILES['thumbs']);
        if ($upload_info['status'] == 1) {
            message("商品图片上传失败！{$upload_info['message']}", '', 'error');
        }
        $data['thumbs'] = json_encode($upload_info['path']);
        if (empty($data['thumbs'])) {
            message('商品图片上传地址错误！', '', 'error');
        }
        $status = pdo_insert('old_goods',$data);
        if(!$status){
            message('发布失败','','error');
        }
        message('发布成功，请耐心等待平台审核^-^', referer(), 'success');
    }
}elseif($do == 'goods'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page - 1)*$psize;
    $list = pdo_fetchall("SELECT * FROM ".tablename('old_goods')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND total>0 ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    if(check_data($list)){
        foreach($list as $k => &$v){
            $thumbs = json_decode($v['thumbs'],true);
            if(check_data($thumbs)){
                $v['thumb'] = tomedia($thumbs[0]);
            }
            $v['createtime'] = date('Y-m-d H:i',$v['createtime']);
        }
    }
    if($_W['isajax']){
        if(trim($_GPC['ac']) == 'delete'){
            $id = floor(trim($_GPC['id']));
            $status = pdo_delete('old_goods',array(
                'uniacid'=>$_W['uniacid'],
                'uid'=>$_W['member']['uid'],
                'id'=>$id
            ));
            if(!$status){
                message('删除失败','','error');
            }
            message('删除成功',referer(),'success');
        }
        if(check_data($list)){
            message($list,'','success');
        }
        message('没有更多商品','','error');
    }
}
template('old/display');
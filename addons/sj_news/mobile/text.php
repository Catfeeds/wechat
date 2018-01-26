<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/9/24
 * Time: 13:34
 */
if($op == 'display'){
    checkauth();
    $config = pdo_get('sj_news_config',array(
        'uniacid' => $_W['uniacid']
    ));
    $setting = array();
    if(!empty($config['setting'])){
        $setting = iunserializer($config['setting']);
    }
    if($_W['isajax']){
        //发布
        load()->model('mc');
        $member = mc_fetch($_W['member']['uid']);
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'title' => trim($_GPC['title']),
            'cid' => trim($_GPC['cid']),
            'type' => 1,
            'author' => trim($_GPC['author']),
            'from' => '作者原创',
            'desc' => mb_substr($_GPC['detail'],0,120,'utf-8'),
            'detail' => $_GPC['detail'],
            'province' => $_W['location']['province'],
            'city' => $_W['location']['city'],
            'district' => $_W['location']['district'],
            'address' => $_W['location']['address'],
            'lat' => $_W['location']['lat'],
            'lng' => $_W['location']['lng'],
            'is_display' => 0, //测试阶段，不需要审核
            'createtime' => TIMESTAMP
        );
        $error = array(
            'title' => '请输入标题',
            'cid' => '请选择类别',
            'detail' => '请输入内容',
            'city' => '请选择所在城市'
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        $status = pdo_insert('sj_news_list',$data);
        if(!$status){
            message('发布失败','','error');
        }
        message('发布成功',referer(),'success');

    }
    $categories = pdo_fetchall("SELECT * FROM ".tablename('sj_news_category')." WHERE uniacid='{$_W['uniacid']}' AND is_display='1' ORDER BY order_by DESC");
}
include $this->template('text');
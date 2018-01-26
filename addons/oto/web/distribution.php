<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/2/15
 * Time: 11:00
 */
if($op == 'display'){
    $uni_setting = OtoModel::getUniSetting();
    $item = OtoModel::getDistributionConfig();
    if(isset($item['setting']['distributor']['goods_id'])){
        if(!empty($item['setting']['distributor']['goods_id']) && is_array($item['setting']['distributor']['goods_id'])){
            $goods_list = OtoModel::getDistributorSearchGoodsByIds($item['setting']['distributor']['goods_id']);
        }
    }
    if($_W['isajax']){
        pdo_begin();
        $data = array(
            'setting' => iserializer($_GPC['setting'])
        );
        if(!empty($item) && is_array($item)){
            $data['updatetime'] = TIMESTAMP;
            $status1 = OtoModel::updateDistributionConfig($data);
        }else{
            $data['uniacid'] = $_W['uniacid'];
            $data['module'] = MODULE_NAME_OTO;
            $data['createtime'] = TIMESTAMP;
            $status1 = OtoModel::insertDistributionConfig($data);
        }
        if(!empty($uni_setting) && is_array($uni_setting)){
            $status2 =OtoModel::updateUniSetting(array(
                'focus_tips' => trim($_GPC['focus_tips']),
                'updatetime' => TIMESTAMP
            ));
        }else{
            $status2 =OtoModel::insertUniSetting(array(
                'focus_tips' => trim($_GPC['focus_tips']),
                'createtime' => TIMESTAMP,
                'uniacid' => $_W['uniacid']
            ));
        }
        if(!$status1 || !$status2){
            pdo_rollback();
            message('设置失败','','error');
        }
        pdo_commit();
        message('设置成功',referer(),'success');
    }
}elseif($op == 'search_goods'){
    if($_W['isajax']){
        $list = OtoModel::getDistributorSearchGoods(trim($_GPC['keyword']),20);
        if(!empty($list) && is_array($list)){
            message($list,'','success');
        }
        message('没有相关商品','','error');
    }

}
include $this->template('distribution');
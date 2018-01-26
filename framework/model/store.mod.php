<?php
load()->func('check');
class StoreModel{
    private static $db_store_list = "store_list";//商家列表
    private static $db_store_category = "store_category";//商家分类
    private static $db_goods_category = "goods_category";//商品分类
    private static $db_store_goods_category  = "store_goods_category";//商家商品分类
    private static $db_goods_list = "goods_list";//商品列表
    private static $db_store_config = "store_config";//商家设置表
    private static $db_store_slide = "store_slide";//商家轮播
    private static $db_goods_postage_template = "goods_postage_template";//发货模板
    private static $db_goods_return_address = "goods_return_address";//退货模板
    private static $db_store_voice_config = "store_voice_config";//提示配置
    private static $db_order_list = "order_list";//订单列表
    private static $db_store_balance_account = "store_balance_account";//结算账号
    private static $db_store_balance_apply = "store_balance_apply";//结算表
    private static $db_order_goods = "order_goods";//订单商品表
    private static $db_mc_members = "mc_members";//会员表
    private static $db_order_offline = "order_offline";//线下订单表


    /**
     * @param $data
     * @return bool
     * 修改商家信息
     */
    public static function updateStoreInfo($data){
        global $_W;
        $update_status = pdo_update(self::$db_store_list,$data,array('id'=>$_W['store_id']));
        return !$update_status?false:true;
    }

    /**
     * @return array|null
     * 获取店铺信息
     */
    public static function getStoreInfo(){
        global $_W;
        $store_info = pdo_get(self::$db_store_list,array('uniacid'=>$_W['uniacid'],'id'=>$_W['store_id']));
        return !empty($store_info) && is_array($store_info)?$store_info:null;
    }

    /**
     * @return array|null
     * 获取公众平台所有的行业分类
     */
    public static function getStoreCategory(){
        global $_W;
        $category = pdo_getall(self::$db_store_category,array('uniacid'=>$_W['uniacid']));
        return !empty($category) && is_array($category)?$category:null;
    }


    /**
     * @param $is_show_all
     * @param string $keyword
     * @param int $display_status
     * @param int $pindex
     * @param int $psize
     * @param int $is_export
     * @return array|null
     * 获取商品分类
     */
    public static function getStoreGoodsCategoryList($is_show_all = CATEGORY_SHOW_ALL,$keyword = '',$display_status = 0,$pindex = 0,$psize = 15,$is_export = EXPORT_NO){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}'";
        if($is_show_all == CATEGORY_SHOW_PARENT){
            $where .= " AND parent_id='0'";
        }
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        if($display_status == 1){
            $where .= " AND is_display=".DISPLAY_YES;
        }elseif($display_status == 2){
            $where .= " AND is_display=".DISPLAY_NO;
        }
        $where .= " ORDER BY order_by DESC";
        if($is_export == EXPORT_NO){
            $where .= " LIMIT {$pindex},{$psize}";
        }
        $category_list = pdo_fetchall("SELECT * FROM ".tablename(self::$db_store_goods_category)." WHERE {$where}");
        return !empty($category_list) && is_array($category_list)?$category_list:null;
    }

    /**
     * @param string $keyword
     * @param int $display_status
     * @return bool
     * 获取符合条件的商品分类数目
     */
    public static function getStoreGoodsCategoryCount($keyword = '',$display_status = 0){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}'";
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        if($display_status == 1){
            $where .= " AND is_display=".DISPLAY_YES;
        }elseif($display_status == 2){
            $where .= " AND is_display=".DISPLAY_NO;
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$db_store_goods_category)." WHERE {$where}");
    }

    /**
     * @return bool
     * 添加商品分类
     */
    public static function insertStoreGoodsCategoryInfo($data =array()){
        if(!empty($data) && is_array($data)){
            $insert_status = pdo_insert(self::$db_store_goods_category,$data);
            return !$insert_status?false:true;
        }
        return false;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 根据商品分类id修改商品分类
     */
    public static function updateStoreGoodsCategoryInfo($id = 0,$data =array()){
        global $_W;
        if(!preg_match("/^[1-9][0-9]*$/",$id) || !is_array($data)){
            return false;
        }
        $update_status = pdo_update(self::$db_store_goods_category,$data,array('uniacid'=>$_W['uniacid'],'store_id'=>$_W['store_id'],'store_type'=>$_W['store_type'],'id'=>$id));
        return !$update_status?false:true;
    }

    /**
     * @param array $ids
     * @return bool
     * 根据商品分类id修改商品分类
     */
    public static function deleteStoreGoodsCategoryByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$db_store_goods_category)." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}' AND (id IN ($ids) OR parent_id IN ($ids))");
            return !$delete_status?false:true;
        }
        return false;
    }

    /**
     * @param int $parent_id
     * @return bool
     * 根据商品分类父级id删除商品分类
     */
    public static function deleteStoreGoodsCategoryByParentId($parent_id = 0){
        global $_W;
        $delete_status = pdo_delete(self::$db_store_goods_category,array('uniacid'=>$_W['uniacid'],'store_id'=>$_W['store_id'],'store_type'=>$_W['store_type'],'parent_id'=>$parent_id));
        return !$delete_status?false:true;
    }

    /**
     * @param int $id
     * @return array|bool|null
     * 根据分类id获取分类信息
     */
    public static function getStoreGoodsCategoryInfoById($id = 0){
        global $_W;
        if(!preg_match("/^[1-9][0-9]*$/",$id)){
            return false;
        }
        $category_info = pdo_get(self::$db_store_goods_category,array('uniacid'=>$_W['uniacid'],'store_id'=>$_W['store_id'],'store_type'=>$_W['store_type'],'id'=>$id));
        return !empty($category_info) && is_array($category_info)?$category_info:null;
    }

    /**
     * @return array|null
     * 获取平台所有商品分类
     */
    public static function getPlatformGoodsCategory(){
        global $_W;
        $category_list = pdo_getall(self::$db_goods_category,array('uniacid'=>$_W['uniacid'],'store_type'=>$_W['store_type']));
        return !empty($category_list) && is_array($category_list)?$category_list:null;
    }

    /**
     * @param int $id
     * @return array|bool|null
     * 通过商品ID获取商品信息
     */
    public static function getStoreGoodsInfoById($id = 0){
        global $_W;
        if(!preg_match("/^[1-9][0-9]*$/",$id)){
            return false;
        }
        $goods_info = pdo_get(self::$db_goods_list,array('uniacid'=>$_W['uniacid'],'store_id'=>$_W['store_id'],'id'=>$id));
        if(!empty($goods_info) && is_array($goods_info)){
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
            return $goods_info;
        }
        return null;
    }

    /**
     * @param array $data
     * @return bool
     * 插入商品信息
     */
    public static function insertStoreGoodsInfo($data = array()){
        if(!is_array($data)){
            return false;
        }
        $status = pdo_insert(self::$db_goods_list,$data);
        return !$status?false:true;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 根据商品id修改商品信息
     */
    public static function updateStoreGoodsInfo($id = 0,$data = array()){
        global $_W;
        if(!preg_match("/^[1-9][0-9]*$/",$id) || !is_array($data)){
            return false;
        }
        $status = pdo_update(self::$db_goods_list,$data,array('uniacid'=>$_W['uniacid'],'store_id'=>$_W['store_id'],'id'=>$id));
        return !$status?false:true;
    }

    /**
     * @param string $keywords
     * @param int $category_id
     * @param int $display_status
     * @param int $pindex
     * @param int $psize
     * @param int $is_export
     * @return array|null
     * 获取商品列表
     */
    public static function getStoreGoodsList($keywords = '',$category_id = 0 ,$display_status = 0,$pindex =0 ,$psize = 15,$is_export = EXPORT_NO){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}'";
        if(!empty($keywords)){
            $where .= " AND (title LIKE '%{$keywords}%')";
        }
        if(!empty($category_id)){
            $where .= " AND (store_category_id='{$category_id}' OR sub_store_category_id='{$category_id}')";
        }
        if(!empty($display_status) && is_array($display_status)){
            $where .= " AND is_display IN (".implode(',',$display_status).")";
        }
        $where .= " ORDER BY order_by DESC";
        if($is_export == EXPORT_NO){
            $where .= " LIMIT {$pindex},{$psize}";
        }
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$db_goods_list)." WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @param string $keywords
     * @param int $category_id
     * @param int $display_status
     * @return bool
     * 获取商品数目
     */
    public static function getStoreGoodsCount($keywords = '',$category_id = 0 ,$display_status = 0){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}'";
        if(!empty($keywords)){
            $where .= " AND (title LIKE '%{$keywords}%')";
        }
        if(!empty($category_id)){
            $where .= " AND (store_category_id='{$category_id}' OR sub_store_category_id='{$category_id}')";
        }
        if(!empty($display_status) && is_array($display_status)){
            $where .= " AND is_display IN (".implode(',',$display_status).")";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$db_goods_list)." WHERE {$where}");
    }

    /**
     * @param array $ids
     * @return bool
     * 批量删除商品
     */
    public static function deleteStoreGoodsInfoByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$db_goods_list)." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}' AND id IN ($ids)");
            return !$delete_status?false:true;
        }
        return false;
    }

    /**
     * @return array|bool|null
     * 获取商家店铺设置
     */
    public static function getStoreShopConfig(){
        global $_W;
        $info = pdo_get(self::$db_store_config,array('uniacid'=>$_W['uniacid'],'store_id'=>$_W['store_id'],'store_type'=>$_W['store_type']));
        if(!empty($info) && is_array($info)){
            if(!empty($info['setting'])){
                $info['setting'] = iunserializer($info['setting']);
            }
            return $info;
        }
        return null;
    }

    /**
     * @param array $data
     * @return bool
     * 插入商城店铺设置
     */
    public static function insertStoreShopConfig($data = array()){
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$db_store_config,$data);
            return !$status?false:true;
        }
        return false;
    }

    /**
     * @param array $data
     * @return bool
     * 修改店铺设置信息
     */
    public static function updateStoreShopConfig($data = array()){
        global $_W;
        if(!empty($data) && is_array($data)){
            $status = pdo_update(self::$db_store_config,$data,array('uniacid'=>$_W['uniacid'],'store_id'=>$_W['store_id'],'store_type'=>$_W['store_type']));
            return !$status?false:true;
        }
        return false;
    }

    /**
     * @return array|null
     * 获取店铺所有的轮播图
     */
    public static function getStoreShopAllSlide(){
        global $_W;
        $list = pdo_getall(self::$db_store_slide,array('uniacid'=>$_W['uniacid'],'store_id'=>$_W['store_id'],'store_type'=>$_W['store_type']));
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['thumb'] =tomedia($v['thumb']);
            }
            return $list;
        }
        return null;
    }

    /**
     * @param array $ids
     * @return bool
     * 批量删除店铺轮播图
     */
    public static function deleteStoreSlideByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$db_store_slide)." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}' AND id IN ($ids)");
            return !$delete_status?false:true;
        }
        return false;
    }

    /**
     * @param int $id
     * @return array|bool|null
     * 根据轮播ID获取店铺轮播信息
     */
    public static function getStoreSlideInfoById($id = 0){
        global $_W;
        if(!check_id($id)){
            return false;
        }
        $info = pdo_get(self::$db_store_slide,array('uniacid'=>$_W['uniacid'],'store_id'=>$_W['store_id'],'store_type'=>$_W['store_type'],'id'=>$id));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param array $data
     * @return bool
     * 插入店铺轮播信息
     */
    public static function insertStoreSlideInfo($data = array()){
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$db_store_slide,$data);
            return !$status?false:true;
        }
        return false;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 修改店铺轮播信息
     */
    public static function updateStoreSlideInfo($id = 0,$data = array()){
        global $_W;
        if(!check_id($id) || !is_array($data)){
            return false;
        }
        $status = pdo_update(self::$db_store_slide,$data,array('uniacid'=>$_W['uniacid'],'store_id'=>$_W['store_id'],'store_type'=>$_W['store_type'],'id'=>$id));
        return !$status?false:true;
    }

    /**
     * @param int $id
     * @return array|null
     * 获取运费模板信息
     */
    public static function getStoreDeliverInfoById($id = 0){
        global $_W;
        if(!check_id($id)){
            return null;
        }
        $info = pdo_get(self::$db_goods_postage_template,array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $_W['store_id'],
            'store_type' => $_W['store_type'],
            'id' => $id
        ));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param array $data
     * @return bool
     * 插入商品信息
     */
    public static function insertStoreDeliverInfo($data = array()){
        if(empty($data) || !is_array($data)){
            return false;
        }
        $insert = pdo_insert(self::$db_goods_postage_template,$data);
        return !$insert?false:true;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 修改运费模板信息
     */
    public static function updateStoreDeliverInfoById($id = 0,$data = array()){
        global $_W;
        if(!check_id($id) || empty($data) || !is_array($data)){
            return false;
        }
        $update = pdo_update(self::$db_goods_postage_template,$data,array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $_W['store_id'],
            'store_type' => $_W['store_type'],
            'id' => $id
        ));
        return !$update?false:true;
    }

    /**
     * @param int $id
     * @return array|null
     * 获取退货地址信息
     */
    public static function getStoreReturnAddressInfoById($id = 0){
        global $_W;
        if(!check_id($id)){
            return null;
        }
        $info = pdo_get(self::$db_goods_return_address,array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $_W['store_id'],
            'store_type' => $_W['store_type'],
            'id' => $id
        ));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param array $data
     * @return bool
     * 插入退货地址信息
     */
    public static function insertStoreReturnAddressInfo($data = array()){
        if(empty($data) || !is_array($data)){
            return false;
        }
        $insert = pdo_insert(self::$db_goods_return_address,$data);
        return !$insert?false:true;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 修改退货地址信息
     */
    public static function updateStoreReturnAddressInfo($id = 0,$data = array()){
        global $_W;
        if(!check_id($id) || empty($data) || !is_array($data)){
            return false;
        }
        $update = pdo_update(self::$db_goods_return_address,$data,array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $_W['store_id'],
            'store_type' => $_W['store_type'],
            'id' => $id
        ));
        return !$update?false:true;
    }

    /**
     * @param int $pindex
     * @param int $psize
     * @param int $is_export
     * @return array|null
     * 获取运费模板列表
     */
    public static function getStoreDeliverList($pindex = 0,$psize = 20,$is_export = 0){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}'";
        $where .= " ORDER BY order_by DESC";
        if(empty($is_export)){
            $where .= " LIMIT {$pindex},{$psize}";
        }
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$db_goods_postage_template)." WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @return bool
     * 获取运费模板列表
     */
    public static function getStoreDeliverCount(){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}'";
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$db_goods_postage_template)." WHERE {$where}");
    }

    /**
     * @param array $ids
     * @return bool
     * 批量删除运费模板
     */
    public static function deleteStoreDeliverByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$db_goods_postage_template)." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}' AND id IN ($ids)");
            return !$delete_status?false:true;
        }
        return false;
    }

    /**
     * @param int $pindex
     * @param int $psize
     * @return array|null
     * 获取所有退货地址
     */
    public static function getStoreReturnGoodsAddressList($pindex = 0,$psize = 20){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}'";
        $where .= " ORDER BY order_by DESC LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$db_goods_return_address)." WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @return bool
     * 获取退货地址数目
     */
    public static function getStoreReturnGoodsAddressCount(){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}'";
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$db_goods_return_address)." WHERE {$where}");
    }

    /**
     * @param array $ids
     * @return bool
     * 批量删除退货地址
     */
    public static function deleteStoreReturnAddressByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$db_goods_return_address)." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}' AND id IN ($ids)");
            return !$delete_status?false:true;
        }
        return false;
    }

    /**
     * @return array|null
     * 获取店铺提示音设置
     */
    public static function getStoreVoiceConfig(){
        global $_W;
        $info = pdo_get(self::$db_store_voice_config,array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $_W['store_id'],
            'store_type' => $_W['store_type']
        ));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param array $data
     * @return bool
     * 修改系统提示音
     */
    public static function updateStoreVoiceConfig($data = array()){
        if(empty($data) || !is_array($data)){
            return false;
        }
        global $_W;
        $update = pdo_update(self::$db_store_voice_config,$data,array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $_W['store_id'],
            'store_type' => $_W['store_type']
        ));
        return !$update?false:true;
    }

    /**
     * @param array $data
     * @return bool
     * 插入提示音设置
     */
    public static function insertStoreVoiceConfig($data = array()){
        if(empty($data) || !is_array($data)){
            return false;
        }
        $insert = pdo_insert(self::$db_store_voice_config,$data);
        return !$insert?false:true;
    }

    /**
     * @return bool
     * 获取不同状态的订单数目
     */
    public static function getStoreOrderCountGroupOrderStatus(){
        global $_W;
        $sql = "SELECT order_status,COUNT(1) AS count FROM ".tablename(self::$db_order_list)." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}' GROUP BY order_status";
        $list = pdo_fetchall($sql,array(),'order_status');
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @param int $id
     * @return array|null
     * 获取结算账号 信息
     */
    public static function getStoreBalanceAccountInfo($id = 0){
        global $_W;
        if(!check_id($id)){
            return null;
        }
        $info  = pdo_get(self::$db_store_balance_account,array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $_W['store_id'],
            'store_type' => $_W['store_type'],
            'id' => $id
        ));
        if(!empty($info) && is_array($info)){
            if(!empty($info['info'])){
                $info['info'] = iunserializer($info['info']);
            }
            return $info;
        }
        return null;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 修改账号信息
     */
    public static function updateStoreBalanceAccountInfoById($id= 0 ,$data =array()){
        global $_W;
        if(!empty($data) && is_array($data) && check_id($id)){
            $status = pdo_update(self::$db_store_balance_account,$data,array(
                'uniacid' => $_W['uniacid'],
                'store_id' => $_W['store_id'],
                'store_type' => $_W['store_type'],
                'id' => $id
            ));
            return !$status?false:true;
        }
        return false;
    }

    /**
     * @param array $data
     * @return bool
     * 插入账号信息
     */
    public static function insertStoreBalanceAccountInfo($data = array()){
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$db_store_balance_account,$data);
            return !$status?false:true;
        }
        return false;
    }

    /**
     * @return array|null
     * 获取帐号列表
     */
    public static function getStoreBalanceAccountList(){
        global $_W;
        $list = pdo_getall(self::$db_store_balance_account,array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $_W['store_id'],
            'store_type' => $_W['store_type']
        ));
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['info'] = iunserializer($v['info']);
            }
            return $list;
        }
        return null;
    }

    /**
     * @param array $ids
     * @return bool
     * 批量删除结算账户
     */
    public static function deleteStoreBalanceAccountByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$db_store_balance_account)." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}' AND id IN ($ids)");
            return !$delete_status?false:true;
        }
        return false;
    }

    /**
     * @param array $data
     * @return bool
     * 插入结算记录
     */
    public static function insertStoreBalanceApplyInfo($data = array()){
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$db_store_balance_apply,$data);
            return !$status?false:true;
        }
        return false;
    }

    /**
     * @param $starttime
     * @param $endtime
     * @param $balance_status
     * @param $pindex
     * @param $psize
     * @param int $is_export
     * @return array|null
     * 获取商家结算列表
     */
    public static function getStoreBalanceApplyList($starttime,$endtime,$status,$pindex,$psize,$is_export = EXPORT_NO){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}'";
        $where .= " AND createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($status)){
            $where .= " AND status IN (".implode(',',$status).")";
        }
        if($is_export == EXPORT_NO){
            $where .= " ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
        }
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$db_store_balance_apply)." WHERE {$where}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['info'] = iunserializer($v['info']);
            }
            return $list;
        }
        return null;
    }

    /**
     * @param $starttime
     * @param $endtime
     * @param $balance_status
     * @return bool
     * 获取商家结算数目
     */
    public static function getStoreBalanceApplyCount($starttime,$endtime,$status){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}'";
        $where .= " AND createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($status)){
            $where .= " AND status IN (".implode(',',$status).")";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$db_store_balance_apply)." WHERE {$where}");
    }

    /**
     * @param $keyword
     * @param $pay_status
     * @param $order_status
     * @param $starttime
     * @param $endtime
     * @param $pindex
     * @param $psize
     * @param int $is_export
     * @return array|null
     *获取订单列表
     */
    public static function getStoreOrderList($keyword,$pay_methods,$pay_status,$order_status,$starttime,$endtime,$pindex,$psize,$is_export = EXPORT_NO){
        global $_W;
        $where = "a.uniacid='{$_W['uniacid']}' AND a.store_id='{$_W['store_id']}' AND a.store_type='{$_W['store_type']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($keyword) && is_string($keyword)){
            $where .= " AND (a.order_no LIKE '%{$keyword}%' OR a.username LIKE '%{$keyword}%' OR a.mobile LIKE '%{$keyword}%')";
        }
        if(!empty($pay_status) && is_array($pay_status)){
            $where .= " AND a.pay_status IN (".implode(',',$pay_status).")";
        }
        if(!empty($pay_methods) && is_array($pay_methods)){
            $where .= " AND a.pay_method IN (".implode(',',$pay_methods).")";
        }
        if(!empty($order_status) && is_array($order_status)){
            $where .= " AND a.order_status IN (".implode(',',$order_status).")";
        }
        $where .= " ORDER BY a.id DESC";
        if($is_export == EXPORT_NO){
            $where .= " LIMIT {$pindex},{$psize}";
        }
        $list = pdo_fetchall("SELECT a.*,b.`goods_name`,b.`thumb`,b.`market_price` FROM ".tablename(self::$db_order_list)." a LEFT JOIN ".tablename(self::$db_order_goods)." b ON a.id=b.order_id WHERE {$where}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['thumb'] = tomedia($v['thumb']);
            }
            return $list;
        }
        return null;
    }

    /**
     * @param $keyword
     * @param $pay_methods
     * @param $pay_status
     * @param $order_status
     * @param $starttime
     * @param $endtime
     * @return bool
     * 获取支付总价格
     */
    public static function getStoreOrderTotalPayPrice($keyword,$pay_methods,$pay_status,$order_status,$starttime,$endtime){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}' AND createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($keyword) && is_string($keyword)){
            $where .= " AND (order_no LIKE '%{$keyword}%' OR username LIKE '%{$keyword}%' OR mobile LIKE '%{$keyword}%')";
        }
        if(!empty($pay_status) && is_array($pay_status)){
            $where .= " AND pay_status IN (".implode(',',$pay_status).")";
        }
        if(!empty($pay_methods) && is_array($pay_methods)){
            $where .= " AND pay_method IN (".implode(',',$pay_methods).")";
        }
        if(!empty($order_status) && is_array($order_status)){
            $where .= " AND order_status IN (".implode(',',$order_status).")";
        }
        return pdo_fetchcolumn("SELECT SUM(pay_total_price) FROM ".tablename(self::$db_order_list)." WHERE {$where}");
    }

    /**
     * @param $keyword
     * @param $pay_status
     * @param $order_status
     * @param $starttime
     * @param $endtime
     * @return bool
     * 获取订单数目
     */
    public static function getStoreOrderCount($keyword,$pay_methods,$pay_status,$order_status,$starttime,$endtime){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}' AND createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($keyword) && is_string($keyword)){
            $where .= " AND (order_no LIKE '%{$keyword}%' OR username LIKE '%{$keyword}%' OR mobile LIKE '%{$keyword}%')";
        }
        if(!empty($pay_status) && is_array($pay_status)){
            $where .= " AND pay_status IN (".implode(',',$pay_status).")";
        }
        if(!empty($pay_methods) && is_array($pay_methods)){
            $where .= " AND pay_method IN (".implode(',',$pay_methods).")";
        }
        if(!empty($order_status) && is_array($order_status)){
            $where .= " AND order_status IN (".implode(',',$order_status).")";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$db_order_list)." WHERE {$where}");
    }

    /**
     * @param int $id
     * @return array|null
     *获取相关店铺订单信息
     */
    public static function getStoreOrderInfoById($id = 0){
        global $_W;
        if(!check_id($id)){
            return null;
        }
        $info = pdo_get(self::$db_order_list,array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $_W['store_id'],
            'store_type' => $_W['store_type'],
            'id' => $id
        ));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param $id
     * @param $deliver_no
     * @return bool
     * 店铺发货
     */
    public static function storeDeliverOrder($id,$deliver_no){
        global $_W;
        if(!check_id($id)){
            return false;
        }
        $status = pdo_update('order_list',array(
            'deliver_no' => $deliver_no,
            'order_status' => ORDER_STATUS_NOT_CONFIRM,
            'updatetime' => TIMESTAMP
        ),array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $_W['store_id'],
            'store_type' => $_W['store_type'],
            'order_status' => ORDER_STATUS_NOT_DELIVER,
            'pay_status' => PAY_YES,
            'id' => $id
        ));
        return !$status?false:true;
    }


    /**
     * @param $order_no
     * @param $verify_code
     * @return array|null
     * 根据核销码获取订单信息
     */
    public static function getOrderInfoByNoVerifyCode($order_no,$verify_code){
        global $_W;
        $info = pdo_get(self::$db_order_list,array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $_W['store_id'],
            'order_no' => $order_no,
            'verify_code' => $verify_code
        ));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     * 修改订单信息
     */
    public static function updateOrderInfoById($id,$data){
        global $_W;
        if(!check_id($id) || !check_data($data)){
            return false;
        }
        $status = pdo_update(self::$db_order_list,$data,array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $_W['store_id'],
            'id' => $id
        ));
        return !$status?false:true;
    }


    /**
     * @param $keyword
     * @param $pay_status
     * @param $starttime
     * @param $endtime
     * @param $pindex
     * @param $psize
     * @param int $is_export
     * @return array|null
     * 获取线下支付列表
     */
    public static function getStoreOfflineOrderList($keyword,$pay_methods,$pay_status,$starttime,$endtime,$pindex,$psize,$is_export = EXPORT_NO){
        global $_W;
        $where = "a.uniacid='{$_W['uniacid']}' AND a.store_id='{$_W['store_id']}' AND a.store_type='{$_W['store_type']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($keyword) && is_string($keyword)){
            $where .= " AND (a.order_no LIKE '%{$keyword}%' OR b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
        }
        if(!empty($pay_status) && is_array($pay_status)){
            $where .= " AND a.pay_status IN (".implode(',',$pay_status).")";
        }
        if(!empty($pay_methods) && is_array($pay_methods)){
            $where .= " AND a.pay_method IN (".implode(',',$pay_methods).")";
        }
        $where .= " ORDER BY a.id DESC";
        if($is_export == EXPORT_NO){
            $where .= " LIMIT {$pindex},{$psize}";
        }
        $list = pdo_fetchall("SELECT a.*,b.`nickname`,b.`realname` FROM ".tablename(self::$db_order_offline)." a LEFT JOIN ".tablename(self::$db_mc_members)." b ON a.uid=b.uid WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @param $keyword
     * @param $pay_status
     * @param $starttime
     * @param $endtime
     * @return array|null
     * 获取线下支付总价格
     */
    public static function getStoreOfflineOrderTotalPayPrice($keyword,$pay_methods,$pay_status,$starttime,$endtime){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$_W['store_id']}' AND store_type='{$_W['store_type']}' AND createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($keyword) && is_string($keyword)){
            $where .= " AND (order_no LIKE '%{$keyword}%' OR uid IN (SELECT uid FROM ".tablename(self::$db_mc_members)." WHERE uniacid='{$_W['uniacid']}' AND (nickname LIKE '%{$keyword}%' OR realname LIKE '%{$keyword}%')))";
        }
        if(!empty($pay_status) && is_array($pay_status)){
            $where .= " AND pay_status IN (".implode(',',$pay_status).")";
        }
        return pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename(self::$db_order_offline)." WHERE {$where}");
    }

    /**
     * @param $keyword
     * @param $pay_status
     * @param $starttime
     * @param $endtime
     * @return bool
     * 线下订单数量
     */
    public static function getStoreOfflineOrderCount($keyword,$pay_methods,$pay_status,$starttime,$endtime){
        global $_W;
        $where = "a.uniacid='{$_W['uniacid']}' AND a.store_id='{$_W['store_id']}' AND a.store_type='{$_W['store_type']}' AND a.createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($keyword) && is_string($keyword)){
            $where .= " AND (a.order_no LIKE '%{$keyword}%' OR b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
        }
        if(!empty($pay_status) && is_array($pay_status)){
            $where .= " AND a.pay_status IN (".implode(',',$pay_status).")";
        }
        if(!empty($pay_methods) && is_array($pay_methods)){
            $where .= " AND a.pay_method IN (".implode(',',$pay_methods).")";
        }
        return pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename(self::$db_order_offline)." a LEFT JOIN ".tablename(self::$db_mc_members)." b ON a.uid=b.uid WHERE {$where}");
    }
}
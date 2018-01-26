<?php
load()->func('check');
class ServicerModel{
    private static $_db_store_list = "store_list";//商家列表
    private static $_db_store_category = "store_category";//商家分类表
    private static $_db_store_apply = "store_apply";//商家申请表
    private static $_db_goods_list = "goods_list";//商品表
    private static $db_servicer_slide = "servicer_slide";//客服轮播表
    private static $_db_old_goods = "old_goods";//二手物品
    private static $_db_mc_members = "mc_members";//会员表
    /**
     * @var array
     * 省会、直辖市按照区来分
     */
    public static $bigCity = array(
        '北京市',
        '天津市',
        '上海市',
        '重庆市',
        '石家庄市',
        '郑州市',
        '武汉市',
        '长沙市',
        '南京市',
        '南昌市',
        '沈阳市',
        '长春市',
        '哈尔滨市',
        '西安市',
        '太原市',
        '济南市',
        '成都市',
        '西宁市',
        '合肥市',
        '海口市',
        '广州市',
        '贵阳市',
        '杭州市',
        '福州市',
        '台北市',
        '兰州市',
        '昆明市',
        '拉萨市',
        '银川市',
        '南宁市',
        '乌鲁木齐市',
        '呼和浩特市'
    );

    /**
     * @param $keyword
     * @param $is_display
     * @param $pindex
     * @param $psize
     * @return array|null
     * 获取轮播列表
     */
    public static function getSlideList($keyword,$is_display,$pindex,$psize){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND servicer_id='{$_W['servicer_id']}'";
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        if(check_data($is_display)){
            $where .= " AND is_display IN (".implode(',',$is_display).")";
        }
        $where .= " ORDER BY order_by DESC LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$db_servicer_slide)." WHERE {$where}");
        return check_data($list)?$list:null;
    }

    /**
     * @param $keyword
     * @param $is_display
     * @return bool
     * 获取轮播数目
     */
    public static function getSlideCount($keyword,$is_display){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND servicer_id='{$_W['servicer_id']}'";
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        if(check_data($is_display)){
            $where .= " AND is_display IN (".implode(',',$is_display).")";
        }
       return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$db_servicer_slide)." WHERE {$where}");
    }


    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 修改商城首页轮播图
     */
    public static function updateSlideById($id = 0,$data = array()){
        global $_W;
        if(!check_id($id) || !is_array($data)){
            return false;
        }
        $status = pdo_update(self::$db_servicer_slide,$data,array('uniacid'=>$_W['uniacid'],'servicer_id'=>$_W['servicer_id'],'id'=>$id));
        return !$status?false:true;
    }

    /**
     * @param int $id
     * @return array|bool|null
     * 获取平台首页轮播图
     */
    public static function getSlideById($id = 0){
        global $_W;
        if(!check_id($id)){
            return false;
        }
        $info = pdo_get(self::$db_servicer_slide,array('uniacid'=>$_W['uniacid'],'servicer_id'=>$_W['servicer_id'],'id'=>$id));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param array $data
     * @return bool
     * 添加商城首页轮播图
     */
    public static function insertSlide($data = array()){
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$db_servicer_slide,$data);
            return !$status?false:true;
        }
        return false;
    }

    /**
     * @param array $ids
     * @return bool
     * 批量删除商城首页轮播
     */
    public static function deleteSlideByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$db_servicer_slide)." WHERE uniacid='{$_W['uniacid']}' AND servicer_id='{$_W['servicer_id']}' AND id IN ($ids)");
            return !$delete_status?false:true;
        }
        return false;
    }

    /**
     * @param $keyword
     * @param $search_type
     * @param $pindex
     * @param $psize
     * @return array|null
     * 获取二手商品列表
     */
    public static function getOldGoodsList($goods_name,$store_name,$is_check,$province,$city,$district,$pindex,$psize){
        global $_W;
        $where = "a.uniacid='{$_W['uniacid']}' AND a.province='{$province}' AND a.city='{$city}'";
        if(!empty($goods_name)){
            $where .= " AND a.title LIKE '%{$goods_name}%'";
        }
        if(!empty($store_name)){
            $where .= " AND a.uid IN (SELECT uid FROM ".tablename(self::$_db_mc_members)." WHERE uniacid='{$_W['uniacid']}' AND (uid='{$store_name}' OR nickname LIKE '%{$store_name}%' OR realname LIKE '%{$store_name}%'))";
        }
        if(!empty($is_check)){
            $where .= " AND a.is_check IN (".implode(',',$is_check).")";
        }
        if(!empty($district)){
            $where .= " AND a.district='{$district}'";
        }
        $where .= " ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT a.*,b.nickname,b.realname FROM ".tablename(self::$_db_old_goods)." a LEFT JOIN ".tablename(self::$_db_mc_members)." b ON a.uid=b.uid WHERE {$where}");
        return check_data($list)?$list:null;
    }



    /**
     * @param $id
     * @param $is_check
     * @return bool
     * 审核二手货
     */
    public static function checkOldGoods($id,$is_check){
        global $_W;
        $where = array(
            'uniacid'=>$_W['uniacid'],
            'province'=>$_W['province'],
            'city'=>$_W['city'],
            'id'=>$id
        );
        if($_W['is_big_city']){
            $where['district'] = $_W['district'];
        }
        $status = pdo_update(self::$_db_old_goods,array(
            'is_check'=>$is_check,
            'updatetime'=>TIMESTAMP
        ),$where);
        return !$status?false:true;
    }

    /**
     * @param $ids
     * @return bool
     * 删除二手物品
     */
    public static function deleteOldGoodsByIds($ids){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND province='{$_W['province']}' AND city='{$_W['city']}' AND id IN (".implode(',',$ids).")";
        if($_W['is_big_city']){
            $where .= " AND district='{$_W['district']}'";
        }
        $status = pdo_query("DELETE FROM ".tablename(self::$_db_old_goods)." WHERE {$where}");
        return !$status?false:true;
    }

    /**
     * @param $goods_name
     * @param $store_name
     * @param $is_check
     * @param $province
     * @param $city
     * @param $district
     * @return array|null
     * 获取二手物品数目
     */
    public static function getOldGoodsCount($goods_name,$store_name,$is_check,$province,$city,$district,$is_second_hand = true){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND province='{$province}' AND city='{$city}'";
        if(!empty($goods_name)){
            $where .= " AND title LIKE '%{$goods_name}%'";
        }
        if(!empty($store_name)){
            $where .= " AND uid IN (SELECT uid FROM ".tablename(self::$_db_mc_members)." WHERE uniacid='{$_W['uniacid']}' AND (uid='{$store_name}' OR nickname LIKE '%{$store_name}%' OR realname LIKE '%{$store_name}%'))";
        }
        if(!empty($is_check)){
            $where .= " AND is_check IN (".implode(',',$is_check).")";
        }
        if(!empty($district)){
            $where .= " AND district='{$district}'";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_old_goods)." WHERE {$where}");
    }

    /**
     * @param string $keyword
     * @param string $province
     * @param string $city
     * @param string $district
     * @param int $type
     * @param int $is_check
     * @param int $starttime
     * @param int $endtime
     * @param int $pindex
     * @param int $psize
     * @param int $is_export
     * @return array|null
     * 获取商家申请列表
     */
    public static function getStoreApplyList($keyword = '',$province = '',$city = '',$district = '',$is_check = 0,$starttime = 0,$endtime = 0,$pindex = 0, $psize = 15,$is_export = EXPORT_NO){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO;
        if(!empty($keyword)){
            $where .= " AND (contacts LIKE '%{$keyword}%' OR tel LIKE '%{$keyword}%' OR saler_uid LIKE '%{$keyword}%' OR referrer_uid LIKE '%{$keyword}%')";
        }
        if(!empty($province)){
            $where .= " AND province='{$province}'";
        }
        if(!empty($city)){
            $where .= " AND city='{$city}'";
        }
        if(!empty($district)){
            $where .= " AND district='{$district}'";
        }
        if(!empty($is_check) && is_array($is_check)){
            $where .= " AND is_check IN (".implode(',',$is_check).")";
        }
        if(!empty($starttime)){
            $where .= " AND createtime>{$starttime}";
        }
        if(!empty($endtime)){
            $where .= " AND createtime<{$endtime}";
        }
        $where .= " ORDER BY createtime DESC";
        if($is_export == EXPORT_NO){
            $where .= " LIMIT {$pindex},{$psize}";
        }
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_store_apply)." WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @param string $keyword
     * @param string $province
     * @param string $city
     * @param string $district
     * @param int $is_check
     * @param int $starttime
     * @param int $endtime
     * @param int $pindex
     * @param int $psize
     * @param int $is_export
     * @return array|null
     * 商家申请数目
     */
    public static function getStoreApplyCount($keyword = '',$province = '',$city = '',$district = '',$is_check = 0,$starttime = 0,$endtime = 0){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO;
        if(!empty($keyword)){
            $where .= " AND (contacts LIKE '%{$keyword}%' OR tel LIKE '%{$keyword}%' OR saler_uid LIKE '%{$keyword}%' OR referrer_uid LIKE '%{$keyword}%')";
        }
        if(!empty($province)){
            $where .= " AND province='{$province}'";
        }
        if(!empty($city)){
            $where .= " AND city='{$city}'";
        }
        if(!empty($district)){
            $where .= " AND district='{$district}'";
        }
        if(!empty($is_check) && is_array($is_check)){
            $where .= " AND is_check IN (".implode(',',$is_check).")";
        }
        if(!empty($starttime)){
            $where .= " AND createtime>{$starttime}";
        }
        if(!empty($endtime)){
            $where .= " AND createtime<{$endtime}";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_store_apply)." WHERE {$where}");
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 修改商家申请
     */
    public static function updateStoreApplyInfoById($id = 0,$data = array()){
        global $_W;
        if(!check_id($id)){
            return false;
        }
        $where = array(
            'uniacid'=>$_W['uniacid'],
            'id'=>$id,
            'province' => $_W['province'],
            'city' => $_W['city']
        );
        if($_W['is_big_city']){
            $where['district'] = $_W['district'];
        }
        $update_status = pdo_update(self::$_db_store_apply,$data,$where);
        return !$update_status?false:true;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 根据店铺ID修改店铺信息
     */
    public static function updateStoreInfoById($id = 0,$data = array()){
        global $_W;
        if(!check_id($id) || !is_array($data)){
            return false;
        }
        $where = array(
            'uniacid' => $_W['uniacid'],
            'id' => $id,
            'province' => $_W['province'],
            'city' => $_W['city']
        );
        if($_W['is_big_city']){
            $where['district'] = $_W['district'];
        }
        $update_status = pdo_update(self::$_db_store_list,$data,$where);
        return !$update_status?false:true;
    }

    /**
     * @param array $ids
     * @return bool
     * 根据店铺ID删除店铺信息
     */
    public static function deleteStoreInfoByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $where = "uniacid='{$_W['uniacid']}' AND id IN ($ids) AND province='{$_W['province']}' AND city='{$_W['city']}'";
            if($_W['is_big_city']){
                $where .= " AND district='{$_W['district']}'";
            }
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$_db_store_list)." WHERE {$where}");
            return !$delete_status?false:true;
        }
        return false;
    }

    /**
     * 获取商家分类
     */
    public static function getStoreCategoryList(){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO;
        $category_list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_store_category)." WHERE {$where}",array(),'id');
        return check_data($category_list)?$category_list:null;
    }

    /**
     * @param int $id
     * @return array|null
     * 根据店铺ID获取店铺信息
     */
    public static function getStoreInfoById($id = 0){
        global $_W;
        if(!check_id($id)){
            return null;
        }
        $where = array(
            'uniacid' => $_W['uniacid'],
            'id' => $id,
            'province' => $_W['province'],
            'city' => $_W['city']
        );
        if($_W['is_big_city']){
            $where['district'] = $_W['district'];
        }
        $store_info = pdo_get(self::$_db_store_list,$where);
        return !empty($store_info) && is_array($store_info)?$store_info:null;
    }

    /**
     * @param string $username
     * @return array|null
     * 通过用户名查询店铺信息
     * 可用来判断是否已经存在
     */
    public static function getStoreInfoByUsername($username = ''){
        $store_info = pdo_get(self::$_db_store_list,array('username'=>$username));
        return !empty($store_info) && is_array($store_info)?$store_info:null;
    }

    /**
     * @param array $data
     * @return bool
     * 插入商家信息
     */
    public static function insertStoreInfo($data = array()){
        if(!empty($data) && is_array($data)){
            $insert_status = pdo_insert(self::$_db_store_list,$data);
            return !$insert_status?false:true;
        }
        return false;
    }

    /**
     * @param string $keyword
     * @param int $is_display
     * @param int $pindex
     * @param int $psize
     * @param int $is_export
     * @return array|null
     * 获取商家列表
     */
    public static function getStoreList($keyword = '',$is_display = 0,$province,$city,$district,$pindex = 0, $psize = 15,$is_export = EXPORT_NO){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND type=".STORE_TYPE_OTO." AND province='{$_W['province']}' AND city='{$_W['city']}'";
        if($_W['is_big_city']){
            $where .= " AND district='{$_W['district']}'";
        }
        if(!empty($keyword)){
            $where .= " AND (username LIKE '%{$keyword}%' OR title LIKE '%{$keyword}%' OR contacts LIKE '%{$keyword}%' OR saler_uid LIKE '%{$keyword}%')";
        }
        if(!empty($is_display) && is_array($is_display)){
            $where .= " AND is_display IN(".implode(',',$is_display).")";
        }
        if(!empty($province)){
            $where .= " AND province='{$province}'";
        }
        if(!empty($city)){
            $where .= " AND city='{$city}'";
        }
        if(!empty($district)){
            $where .= " AND district='{$district}'";
        }
        $where .= " ORDER BY order_by DESC";
        if($is_export == EXPORT_NO){
            $where .= " LIMIT {$pindex},{$psize}";
        }
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_store_list)." WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }


    /**
     * @param string $keyword
     * @param int $is_display
     * @return bool
     * 获取商家数目
     */
    public static function getStoreCount($keyword = '',$is_display = 0,$province,$city,$district){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND type=".STORE_TYPE_OTO." AND province='{$_W['province']}' AND city='{$_W['city']}'";
        if($_W['is_big_city']){
            $where .= " AND district='{$_W['district']}'";
        }
        if(!empty($keyword)){
            $where .= " AND (username LIKE '%{$keyword}%' OR title LIKE '%{$keyword}%' OR contacts LIKE '%{$keyword}%' OR saler_uid LIKE '%{$keyword}%')";
        }
        if(!empty($is_display) && is_array($is_display)){
            $where .= " AND is_display IN(".implode(',',$is_display).")";
        }
        if(!empty($province)){
            $where .= " AND province='{$province}'";
        }
        if(!empty($city)){
            $where .= " AND city='{$city}'";
        }
        if(!empty($district)){
            $where .= " AND district='{$district}'";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_store_list)." WHERE {$where}");
    }

    /**
     * @param $id
     * @return bool
     * 删除申请
     */
    public static function deleteStoreApplyById($id){
        global $_W;
        $where  = array(
            'uniacid' => $_W['uniacid'],
            'id' => $id,
            'province' => $_W['province'],
            'city' => $_W['city']
        );
        if($_W['is_big_city']){
            $where['district'] = $_W['district'];
        }
        $status = pdo_delete(self::$_db_store_apply,$where);
        return !$status?false:true;
    }

}
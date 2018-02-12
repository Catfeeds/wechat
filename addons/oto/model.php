<?php
load()->func('check');
class OtoModel{

    private static $_db_mc_check = "mc_check";//老会员审核表
    private static $_db_store_category = "store_category";//店铺分类
    private static $_db_store_list = "store_list";//店铺列表
    private static $_db_store_apply = "store_apply";//商家申请列表
    private static $_db_goods_category = "goods_category";//商品分类
    private static $_db_goods_list = "goods_list";//商品表
    private static $_db_shop_config = "shop_config";//商城首页设置表
    private static $_db_shop_slide = "shop_slide";//首页轮播图表
    private static $_db_store_goods_category = "store_goods_category";//商家商品分类
    private static $_db_order_shopping_cart = "order_shopping_cart";//购物车表
    private static $_db_deliver_address = "mc_member_address";//会员收货地址
    private static $_db_order_list = "order_list";//订单列表
    private static $_db_order_goods = "order_goods"; //订单商品列表
    private static $_db_pay_log = "pay_log";//订单支付日志
    private static $_db_store_config = "store_config";//店铺设置
    private static $_db_store_slide = "store_slide";//店铺轮播
    private static $_db_mc_member_collect = "mc_member_collect";//会员收藏列表
    private static $_db_distribution_config = "distribution_config";//分销设置表
    private static $_db_rebate_list = "rebate_list";//返佣列表
    private static $_db_order_offline = "order_offline";//线下支付
    private static $_db_fangyuanbao_op_log  = "fangyuanbao_op_log";//操作员发放方圆宝操作记录表
    private static $_db_mc_member_footmark = "mc_member_footmark";//足迹列表
    private static $_db_uni_settings = "uni_settings";//公众号设置表
    private static $_db_mc_members = "mc_members";//会员表
    private static $_db_servicer_slide = "servicer_slide";//代理轮播表
    private static $_db_servicer_user = "servicer_user";//客服
    private static $_db_agent_user = "agent_user";//代理表
    private static $_db_account_user = "account_user";//会计表
    private static $_db_fangyuanbao_queue = "fangyuanbao_queue";//方圆宝队列
    private static $_db_fangyuanbao_rebate = "fangyuanbao_rebate";//方圆宝兑换表
    private static $_db_old_goods = "old_goods";//二手货
    private static $_db_fangyuanbao_user = "fangyuanbao_user";//方圆宝用户

    /* 省会城市 */
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
        '乌鲁木齐市'
//        '呼和浩特市'
    );

    /* 订单状态 */
    public static  $orderStatusArr = array(
        0 => '未付款',
        1 => '等待卖家发货',
        2 => '未确认',
        3 => '交易成功',
        4 => '退款中',
        5 => '已退款',
        6 => '已关闭'
    );

    public static $newLevel = array(
        1 => '9.5成新以上',
        2 => '9成新以上',
        3 => '8.5成新以上',
        4 => '8成新以上',
        5 => '7.5成新以上',
        6 => '7成新以上',
        7 => '7成新以下'
    );

    const SORT_DEFAULT = 0;
    const SORT_DISTANCE_NEAR = 1;
    const SORT_GOOD_TALK = 2;
    const SORT_LAST_PUSH = 3;
    const SORT_POPULARITY = 4;
    const SORT_MIN_PRICE = 5;
    const SORT_MAX_PRICE = 6;

    /**
     * @param $keyword
     * @param $search_type
     * @param $pindex
     * @param $psize
     * @return null
     * 获取二手商品
     */
    public static function getOldGoodsList($keyword,$search_type,$pindex,$psize){
        global $_W;
        if(empty($_W['location']['city'])){
            return null;
        }
        $where = "uniacid='{$_W['uniacid']}' AND total>0 AND is_check=".CHECK_PASS;
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        if($search_type != SEARCH_COUNTRY){
            $where .= " AND city='{$_W['location']['city']}'";
        }
        $where .= " ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_old_goods)." WHERE {$where}");
        if(check_data($list)){
            foreach($list as $k => &$v){
                $v['thumb'] = '';
                if(!empty($v['thumbs'])){
                    $v['thumbs'] = json_decode($v['thumbs'],true);
                    if(check_data($v['thumbs'])){
                        foreach($v['thumbs'] as $k1 => &$thumb){
                            $thumb = tomedia($thumb);
                            if($k1 == 0){
                                $v['thumb'] = $thumb;
                            }
                        }
                    }
                }
            }
        }
        return check_data($list)?$list:null;
    }

    /**
     *
     * 积分商品。需要购买方圆宝第三套餐
     */
    public static function getCreditGoodsList($keyword,$search_type,$pindex,$psize){
        global $_W;
        if(empty($_W['location']['city'])){
            return null;
        }
        $where = "uniacid='{$_W['uniacid']}' AND is_check=".CHECK_PASS." AND is_display=".DISPLAY_YES." AND total>0";
        $store_where = "uniacid='{$_W['uniacid']}' AND saler_uid IN (SELECT uid FROM ".tablename(self::$_db_fangyuanbao_user)." WHERE uniacid='{$_W['uniacid']}' AND product_key='3')";
        if($search_type != SEARCH_COUNTRY){
            $store_where .= " AND city='{$_W['location']['city']}'";
        }
        $where .= " AND store_id IN (SELECT id FROM ".tablename(self::$_db_store_list)." WHERE {$store_where})";
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        $where .= " ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_goods_list)." WHERE {$where}");
        return check_data($list)?$list:null;
    }



    /**
     * @param $keyword
     * @param $starttime
     * @param $endtime
     * @param $province
     * @param $city
     * @param $district
     * @param $status

     * @param $pindex
     * @param $psize
     * @return array|null
     * 获取方圆宝队列
     */
    public static function getFangyuanbaoQueueList($keyword,$starttime,$endtime,$province,$city,$district,$status,$pindex,$psize){
        global $_W;
        $where = "a.uniacid='{$_W['uniacid']}'";
        if(!empty($keyword)){
            $where .= " AND (a.uid='{$keyword}' OR b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
        }
        if(!empty($starttime)){
            $where .= " AND a.createtime>={$starttime}";
        }
        if(!empty($endtime)){
            $where .= " AND a.createtime<={$endtime}";
        }
        if(!empty($province)){
            $where .= " AND a.province='{$province}'";
        }
        if(!empty($city)){
            $where .= " AND a.city='{$city}'";
        }
        if(!empty($district)){
            $where .= " AND a.district='{$district}'";
        }
        if(check_data($status)){
            $where .= " AND a.status IN (".implode(',',$status).")";
        }
        $where .= " ORDER BY a.createtime ASC LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT a.*,b.nickname,b.realname FROM ".tablename(self::$_db_fangyuanbao_queue)." a LEFT JOIN ".tablename(self::$_db_mc_members)." b ON a.uid=b.uid WHERE {$where}");
        return check_data($list)?$list:null;
    }

    /**
     * @param $keyword
     * @param $starttime
     * @param $endtime
     * @param $province
     * @param $city
     * @param $district
     * @param $status
     * @param $pindex
     * @param $psize
     * @return bool
     * 获取方圆宝队列数量
     */
    public static function getFangyuanbaoQueueCount($keyword,$starttime,$endtime,$province,$city,$district,$status){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}'";
        if(!empty($keyword)) {
            $where .= " AND uid IN (SELECT uid FROM " . tablename(self::$_db_mc_members) . " WHERE uniacid='{$_W['uniacid']}' AND (uid='{$keyword}' OR nickname LIKE '%{$keyword}%' OR realname LIKE '%{$keyword}%'))";

        }
        if(!empty($starttime)){
            $where .= " AND createtime>={$starttime}";
        }
        if(!empty($endtime)){
            $where .= " AND createtime<={$endtime}";
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
        if(check_data($status)){
            $where .= " AND status IN (".implode(',',$status).")";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_fangyuanbao_queue)." WHERE {$where}");
        
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
        $store_info = pdo_get(self::$_db_store_list,array('uniacid'=>$_W['uniacid'],'id'=>$id));
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
        $update_status = pdo_update(self::$_db_store_list,$data,array('uniacid'=>$_W['uniacid'],'id'=>$id));
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
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$_db_store_list)." WHERE uniacid='{$_W['uniacid']}' AND id IN ($ids)");
            return !$delete_status?false:true;
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
        $where = "uniacid='{$_W['uniacid']}' AND type=".STORE_TYPE_OTO;
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
     * @param $pindex
     * @param $psize
     * @return array|null
     * 获取首页附近商家列表
     */
    public static function getNearbyStoreList($pindex,$psize,$distance_range = 5){
        global $_W;
        if(empty($_W['location']['city'])){
            return null;
        }
        $where = "uniacid='{$_W['uniacid']}' AND type=".STORE_TYPE_OTO;
        if(empty($distance_range)){
            $where .= " AND city='{$_W['location']['city']}'";
        }else{
            $distance_range_limit = getLocationRange($_W['location']['lng'],$_W['location']['lat'],$distance_range);
            $where .= " AND lat BETWEEN {$distance_range_limit['lat_start']} AND {$distance_range_limit['lat_end']} AND lng BETWEEN {$distance_range_limit['lng_start']} AND {$distance_range_limit['lng_end']}";
        }
        $where .= " ORDER BY order_by DESC LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_store_list)." WHERE {$where}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['logo'] = tomedia($v['logo']);
                $v['distance'] = getDistanceByLocations($_W['location']['lat'],$_W['location']['lng'],$v['lat'],$v['lng']);
                $v['distance'] = $v['distance'] > 1000?round(floatval($v['distance']/1000),1).'km':round(floatval($v['distance']),2).'m';
            }
        }
        return !empty($list) && is_array($list)?$list:null;
    }


    /**
     * @param string $keyword
     * @param int $is_display
     * @return bool
     * 获取所有商家的数目
     */
    public static function getStoreCount($keyword = '',$is_display = 0,$province,$city,$district){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND type=".STORE_TYPE_OTO;
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
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_store_list)." WHERE {$where}");
    }


    /**
     * @param int $id
     * @return array|bool|null
     * 根据商家分类ID获取商家分类信息
     */
    public static function getStoreCategoryInfoById($id = 0){
        global $_W;
        if(!check_id($id)){
            return false;
        }
        $category_info = pdo_get(self::$_db_store_category,array('uniacid'=>$_W['uniacid'],'id'=>$id));
        return !empty($category_info) && is_array($category_info)?$category_info:null;
    }

    /**
     * @param array $data
     * @return bool
     * 插入商家分类信息
     */
    public static function insertStoreCategoryInfo($data = array()){
        if(!empty($data) && is_array($data)){
            $insert_status = pdo_insert(self::$_db_store_category,$data);
            return !$insert_status?false:true;
        }
        return false;
    }

    /**
     * @param $title
     * @return array|null
     * 根据标题获取分类信息
     */
    public static function getStoreCategoryInfoByTitle($title){
        global $_W;
        $category_info = pdo_get(self::$_db_store_category,array('uniacid'=>$_W['uniacid'],'title'=>$title,'store_type'=>STORE_TYPE_OTO));
        return !empty($category_info) && is_array($category_info)?$category_info:null;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 根据商家分类ID修改商家分类信息
     */
    public static function updateStoreCategoryInfoById($id = 0,$data = array()){
        global $_W;
        if(!check_id($id) || !is_array($data)){
            return false;
        }
        $update_status = pdo_update(self::$_db_store_category,$data,array('uniacid'=>$_W['uniacid'],'id'=>$id));
        return !$update_status?false:true;
    }

    /**
     * @param array $ids
     * @return bool
     * 根据商家分类ID删除店铺分类信息
     */
    public static function deleteStoreCategoryInfoByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$_db_store_category)." WHERE uniacid='{$_W['uniacid']}' AND id IN ($ids)");
            return !$delete_status?false:true;
        }
        return false;
    }

    /**
     * @param $id
     * @return bool
     * 删除申请
     */
    public static function deleteStoreApplyById($id){
        global $_W;
        $status = pdo_delete(self::$_db_store_apply,array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
        return !$status?false:true;
    }

    /**
     * @param string $keyword
     * @param int $is_display
     * @param int $pindex
     * @param int $pszie
     * @param int $is_export
     * @return array|null
     * 获取店铺分类列表
     */
    public static function getStoreCategoryList($keyword = '',$is_display = 0,$pindex = 0,$pszie = 15,$is_export = EXPORT_NO){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO;
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        if(!empty($is_display) && is_array($is_display)){
            $where .= " AND is_display IN (".implode(',',$is_display).")";
        }
        $where .= " ORDER BY order_by DESC";
        if($is_export == EXPORT_NO){
            $where .= " LIMIT {$pindex},{$pszie}";
        }
        $category_list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_store_category)." WHERE {$where}",array(),'id');
        if(!empty($category_list) && is_array($category_list)){
            foreach($category_list as $k => &$v){
                $v['thumb'] = tomedia($v['thumb']);
            }
            return $category_list;
        }
        return null;
    }

    /**
     * @param string $keyword
     * @param int $is_display
     * @return bool
     * 获取店铺类别数目
     */
    public static function getStoreCategoryCount($keyword = '',$is_display = 0){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO;
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        if(!empty($is_display) && is_array($is_display)){
            $where .= " AND is_display IN (".implode(',',$is_display).")";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_store_category)." WHERE {$where}");
    }

    /**
     * @param array $data
     * @return bool
     * 记录商家申请记录
     */
    public static function insertStoreApply($data = array()){
        if(!empty($data) && is_array($data)){
            $insert_status = pdo_insert(self::$_db_store_apply,$data);
            return !$insert_status?false:true;
        }
        return false;
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
     * @param int $type
     * @param int $is_check
     * @param int $starttime
     * @param int $endtime
     * @return bool
     * 获取商家申请数目
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
        $update_status = pdo_update(self::$_db_store_apply,$data,array('uniacid'=>$_W['uniacid'],'id'=>$id));
        return !$update_status?false:true;
    }

    /**
     * @param int $is_show_all
     * @param string $keyword
     * @param int $is_display
     * @param int $pindex
     * @param int $psize
     * @param int $is_export
     * @return array|null
     * 获取商品分类列表
     */
    public static function getGoodsCategoryList($is_show_all = CATEGORY_SHOW_ALL,$keyword = '',$is_display = 0,$pindex = 0,$psize = 15,$is_export = EXPORT_NO){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO;
        if($is_show_all == CATEGORY_SHOW_PARENT){
            $where .= " AND parent_id='0'";
        }
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        if(!empty($is_display) && is_array($is_display)){
            $where .= " AND is_display IN (".logging_implode(',',$is_display).")";
        }
        $where .= " ORDER BY order_by DESC";
        if($is_export == EXPORT_NO){
                $where .= " LIMIT {$pindex},{$psize}";
        }
        $category_list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_goods_category)." WHERE {$where}",array(),'id');
        if(!empty($category_list) && is_array($category_list)){
            foreach($category_list as $k => &$v){
                $v['thumb'] = tomedia($v['thumb']);
            }
            return $category_list;
        }
        return null;
    }

    /**
     * @return array|null
     * 获取平台一级二级所有分类
     */
    public static function getMaxTwoLevelCategory(){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO;
        $where .= " AND is_display=".DISPLAY_YES;
        $where2 = $where." AND parent_id=0";
        $where .= " AND (parent_id=0 OR parent_id IN (SELECT id FROM ".tablename(self::$_db_goods_category)." WHERE {$where2}))";
        $where .= " ORDER BY order_by DESC";
        $category_list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_goods_category)." WHERE {$where}");
        return !empty($category_list) && is_array($category_list)?$category_list:null;
    }


    /**
     * @param string $keyword
     * @param int $is_display
     * @return bool
     * 获取商品分类数目
     */
    public static function getGoodsCategoryCount($keyword = '',$is_display =0){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO;
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        if(!empty($is_display) && is_array($is_display)){
            $where .= " AND is_display IN (".logging_implode(',',$is_display).")";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_goods_category)." WHERE {$where}");
    }

    /**
     * @param int $id
     * @return array|bool|null
     * 获取商品分类信息
     */
    public static function getGoodsCategoryInfoById($id = 0){
        global $_W;
        if(!check_id($id)){
            return false;
        }
        $category_info = pdo_get(self::$_db_goods_category,array('uniacid'=>$_W['uniacid'],'id'=>$id));
        return !empty($category_info) && is_array($category_info)?$category_info:null;
    }

    /**
     * @param array $data
     * @return bool
     * 插入商品分类信息
     */
    public static function insertGoodsCategory($data =array()){
        if(!empty($data) && is_array($data)){
            $insert_status = pdo_insert(self::$_db_goods_category,$data);
            return !$insert_status?false:true;
        }
        return false;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 修改商品分类信息
     */
    public static function updateGoodsCategoryInfoById($id = 0, $data =array()){
        global $_W;
        if(!check_id($id) || !is_array($data)){
            return false;
        }
        $update_status = pdo_update(self::$_db_goods_category,$data,array('uniacid'=>$_W['uniacid'],'id'=>$id));
        return !$update_status?false:true;
    }

    /**
     * @param array $ids
     * @return bool
     * 根据商品分类id删除商品分类
     */
    public static function deleteGoodsCategoryInfoByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$_db_goods_category)." WHERE uniacid='{$_W['uniacid']}' AND (id IN ($ids) OR parent_id IN ($ids))");
            return !$delete_status?false:true;
        }
        return false;
    }

    /**
     * @param int $parent_id
     * @return bool
     * 删除商品同一父级的所有子分类
     */
    public static function deleteGoodsCategoryInfoByParentId($parent_id = 0){
        global $_W;
        $delete_status = pdo_delete(self::$_db_goods_category,array('uniacid'=>$_W['uniacid'],'parent_id'=>$parent_id));
        return !$delete_status?false:true;
    }


    /**
     * @param string $keywords
     * @param int $category_id
     * @param int $is_display
     * @param int $is_check
     * @param int $pindex
     * @param int $psize
     * @param int $is_export
     * @return array|null
     * 获取商品列表
     */
    public static function getGoodsList($keywords = '',$category_id = 0 ,$is_display = 0,$is_check = 0,$pindex =0 ,$psize = 15,$is_export = EXPORT_NO){
        global $_W;
        $where = "a.uniacid='{$_W['uniacid']}' AND a.store_type=".STORE_TYPE_OTO;
        if(!empty($keywords)){
            $where .= " AND (a.title LIKE '%{$keywords}%')";
        }
        if(!empty($category_id)){
            $where .= " AND (a.category_id='{$category_id}' OR a.sub_category_id='{$category_id}')";
        }
        if(!empty($is_display) && is_array($is_display)){
            $where .= " AND a.is_display IN (".implode(',',$is_display).")";
        }
        if(!empty($is_check) && is_array($is_check)){
            $where .= " AND a.is_check IN (".implode(',',$is_check).")";
        }
        $where .= " ORDER BY a.order_by DESC";
        if($is_export == EXPORT_NO){
            $where .= " LIMIT {$pindex},{$psize}";
        }
        $list = pdo_fetchall("SELECT a.*,b.title AS store_name FROM ".tablename(self::$_db_goods_list)." a LEFT JOIN ".tablename(self::$_db_store_list)." b ON a.store_id=b.id WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @param string $keywords
     * @param int $category_id
     * @param int $is_display
     * @param int $is_check
     * @return bool
     *
     */
    public static function getGoodsCount($keywords = '',$category_id = 0,$is_display = 0,$is_check = 0){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO;
        if(!empty($keywords)){
            $where .= " AND (title LIKE '%{$keywords}%')";
        }
        if(!empty($category_id)){
            $where .= " AND (category_id='{$category_id}' OR sub_category_id='{$category_id}')";
        }
        if(!empty($is_display) && is_array($is_display)){
            $where .= " AND is_display IN (".implode(',',$is_display).")";
        }
        if(!empty($is_check) && is_array($is_check)){
            $where .= " AND is_check IN (".implode(',',$is_check).")";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_goods_list)." WHERE {$where}");
    }

    /**
     * @param int $id
     * @return array|bool|null
     * 根据商品id获取商品信息
     */
    public static function getGoodsInfoById($id = 0){
        global $_W;
        if(!check_id($id)){
            return false;
        }
        $goods_info = pdo_get('goods_list',array('uniacid'=>$_W['uniacid'],'is_display'=>DISPLAY_YES,'store_type'=>STORE_TYPE_OTO,'is_check'=>CHECK_PASS,'id'=>$id));
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
     * @return array|bool|null
     * 获取平台的首页设置
     */
    public static function getPlatformIndexSetting(){
        global $_W;
        $info = pdo_get(self::$_db_shop_config,array('uniacid'=>$_W['uniacid'],'store_type'=>STORE_TYPE_OTO));
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
     * 插入平台首页设置
     */
    public static function insertPlatformIndexSetting($data = array()){
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$_db_shop_config,$data);
            return !$status?false:true;
        }
        return false;
    }

    /**
     * @param array $data
     * @return bool
     * 修改平台首页设置
     */
    public static function updatePlatformIndexSetting($data = array()){
        global $_W;
        if(!empty($data) && is_array($data)){
            $status = pdo_update(self::$_db_shop_config,$data,array('uniacid'=>$_W['uniacid'],'store_type'=>STORE_TYPE_OTO));
            return !$status?false:true;
        }
        return false;
    }

    /**
     * @param int $id
     * @return array|bool|null
     * 获取平台首页轮播图
     */
    public static function getPlatformSlideById($id = 0){
        global $_W;
        if(!check_id($id)){
            return false;
        }
        $info = pdo_get(self::$_db_shop_slide,array('uniacid'=>$_W['uniacid'],'store_type'=>STORE_TYPE_OTO,'id'=>$id));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param array $data
     * @return bool
     * 添加商城首页轮播图
     */
    public static function insertPlatformSlide($data = array()){
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$_db_shop_slide,$data);
            return !$status?false:true;
        }
        return false;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 修改平台商城首页轮播图
     */
    public static function updatePlatformSlide($id = 0,$data = array()){
        global $_W;
        if(!check_id($id) || !is_array($data)){
            return false;
        }
        $status = pdo_update(self::$_db_shop_slide,$data,array('uniacid'=>$_W['uniacid'],'store_type'=>STORE_TYPE_OTO,'id'=>$id));
        return !$status?false:true;
    }

    /**
     * @return array|null
     * 获取平台的所有轮播图
     */
    public static function getPlatformSlideList(){
        global $_W;
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_shop_slide)." WHERE uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO." ORDER BY order_by DESC");
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
     * 批量删除平台商城首页轮播
     */
    public static function deletePlatformSlideByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$_db_shop_slide)." WHERE uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO." AND id IN ($ids)");
            return !$delete_status?false:true;
        }
        return false;
    }

    /**
     * @param int $store_id
     * @return array|null
     * 获取商家的所有商品分类
     */
    public static function getStoreGoodsCategoryListByStoreId($store_id = 0){
        global $_W;
        if(!check_id($store_id)){
            return null;
        }
        $list = pdo_getall(self::$_db_store_goods_category,array('uniacid'=>$_W['uniacid'], 'store_id' => $store_id, 'store_type' => STORE_TYPE_OTO));
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool|null
     * 修改商品信息
     */
    public static function updateGoodsInfoById($id = 0,$data = array()){
        global $_W;
        if(!check_id($id) || !is_array($data)){
            return null;
        }
        $status = pdo_update(self::$_db_goods_list,$data,array('uniacid'=>$_W['uniacid'],'store_type'=>STORE_TYPE_OTO,'id'=>$id));
        return !$status?false:true;
    }

    /**
     * @param array $ids
     * @return bool
     * 平台删除商家的产品，只是隐藏
     */
    public static function deleteGoodsByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$_db_goods_list)." WHERE uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO." AND id IN ($ids)");
            return !$delete_status?false:true;
        }
        return false;
    }



    /**
     * @param int $store_id
     * @param int $num
     * @return array|null
     * 获取店铺中，平台推荐的商品
     */
    public static function getPlatformRecommendGoodsByStoreId($store_id = 0,$num = 20){
        global $_W;
        if(!check_id($store_id)){
            return null;
        }
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$store_id}' AND total>0 AND store_type=".STORE_TYPE_OTO." AND is_platform_recommend=".RECOMMEND_YES;
        $where .= " AND store_id IN (SELECT id FROM ".tablename(self::$_db_store_list)." WHERE uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO." AND city='{$_W['location']['city']}')";
        $where .= " ORDER BY platform_order_by DESC LIMIT {$num}";
        $list = pdo_fetchall("SELECT `id`,`sale_count`,`title`,`thumb`,`market_price`,`sale_price`,`limit_time_price`,`limit_time_buy_start`,`limit_time_buy_end`,`is_open_limit_time_buy` FROM ".tablename(self::$_db_goods_list)." WHERE {$where}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['thumb'] = tomedia($v['thumb']);
                $v['sale_price'] = getGoodsTruePrice($v);
            }
            return $list;
        }
        return null;
    }



    /**
     * @param int $id
     * @return array|bool|null
     * 获取商品详情
     */
    public static function getGoodsDetailById($id = 0){
        global $_W;
        if(!check_id($id)){
            return false;
        }
        $goods_info = pdo_get('goods_list',array('uniacid'=>$_W['uniacid'],'is_display'=>DISPLAY_YES,'store_type'=>STORE_TYPE_OTO,'is_check'=>CHECK_PASS,'id'=>$id));
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
            if(!empty($goods_info['thumbs']) && $goods_info['thumbs'] != 'N;'){
                $goods_info['thumbs'] = iunserializer($goods_info['thumbs']);
            }else{
                $goods_info['thumbs'] = array();
            }
            array_push($goods_info['thumbs'],$goods_info['thumb']);
            $goods_info['detail'] = htmlspecialchars_decode($goods_info['detail']);
            return $goods_info;
        }
        return null;
    }

    /**
     * @param int $store_id
     * @param int $num
     * @return array|bool|null
     * 获取商家推荐商品
     */
    public static function getStoreRecommendGoods($store_id = 0,$num = 20){
        global $_W;
        if(!check_id($store_id)){
            return null;
        }
        $where = "uniacid='{$_W['uniacid']}' AND total>0 AND store_type=".STORE_TYPE_OTO." AND is_recommend=".RECOMMEND_YES." AND store_id={$store_id} ORDER BY order_by DESC LIMIT {$num}";
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_goods_list)." WHERE {$where}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['thumb'] = tomedia($v['thumb']);
                $v['sale_price'] = getGoodsTruePrice($v);
            }
            return $list;
        }
        return null;
    }


    /**
     * @param string $keyword
     * @param int $category_id
     * @param int $sub_category_id
     * @param int $sort_type
     * @param int $distance_range
     * @param int $pindex
     * @param int $psize
     * @return array|null
     * 获取附近商品列表
     */
    public static function getPointGoodsList($keyword = '',$category_id = 0,$sub_category_id = 0,$sort_type = 0,$distance_range = 0,$pindex = 0,$psize = 15){
        global $_W;
        $where = "a.uniacid='{$_W['uniacid']}' AND a.total>0 AND a.store_type=".STORE_TYPE_OTO;
        if(!empty($keyword)){
            $where .= " AND a.title LIKE '%{$keyword}%'";
        }
        if(!empty($category_id)){
            $where .= " AND a.category_id='{$category_id}'";
        }
        if(!empty($sub_category_id)){
            $where .= " AND a.sub_category_id='{$sub_category_id}'";
        }
        if(empty($distance_range)){
            $where .= " AND a.store_id IN (SELECT id FROM ".tablename(self::$_db_store_list)." WHERE uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO." AND city='{$_W['location']['city']}')";
        }else{
            $distance_range_limit = getLocationRange($_W['location']['lng'],$_W['location']['lat'],$distance_range);
            $where .= " AND a.store_id IN (SELECT id FROM ".tablename(self::$_db_store_list)." WHERE uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO." AND lat BETWEEN {$distance_range_limit['lat_start']} AND {$distance_range_limit['lat_end']} AND lng BETWEEN {$distance_range_limit['lng_start']} AND {$distance_range_limit['lng_end']})";
        }
        if($sort_type == self::SORT_DEFAULT){
            $where .= " ORDER BY a.platform_order_by DESC";
        }
        $where .= " LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT a.`id`,a.`good_talk_count`,a.`talk_count`,a.`title`,a.`thumb`,a.`market_price`,a.`sale_price`,a.`desc`,a.`sale_count`,a.`limit_time_price`,a.`limit_time_buy_start`,a.`limit_time_buy_end`,a.`is_open_limit_time_buy`,b.`lat`,b.`lng`,b.`title` AS 'store_title',b.`address` AS 'store_address' FROM ".tablename(self::$_db_goods_list)." a LEFT JOIN ".tablename(self::$_db_store_list)." b ON store_id=b.id WHERE {$where}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['sale_price'] = getGoodsTruePrice($v);
                $v['thumb'] = tomedia($v['thumb']);
                $v['distance'] = getDistanceByLocations($_W['location']['lat'],$_W['location']['lng'],$v['lat'],$v['lng']);
                $v['distance'] = $v['distance'] > 1000?round(floatval($v['distance']/1000),2).'km':round(floatval($v['distance']),2).'m';
            }
        }
        return !empty($list) && is_array($list)?$list:null;
    }


    /**
     * @param int $goods_id
     * @param string $sku_key
     * @param string $sku_desc
     * @return array|null
     * 获取购物车信息
     */
    public static function getShoppingCartInfoBySkuGoodsId($goods_id = 0,$sku_key = '',$sku_desc = ''){
        global $_W;
        if(!check_id($goods_id)){
            return false;
        }
        $where = array(
            'uniacid' =>  $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'store_type' => STORE_TYPE_OTO,
            'goods_id' => $goods_id,
            'sku_key' => $sku_key,
            'sku_desc' => $sku_desc
        );
        $info = pdo_get(self::$_db_order_shopping_cart,$where);
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param array $data
     * @return bool
     * 插入购物车
     */
    public static function insertShoppingCart($data = array()){
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$_db_order_shopping_cart,$data);
            return !$status?false:true;
        }
        return false;
    }


    /**
     * @param array $data
     * @param int $goods_id
     * @param string $sku_key
     * @param string $sku_desc
     * @return bool
     * 修改购物车信息
     */
    public static function updateShoppingCartInfoBySkuGoodsId($data = array(),$goods_id = 0,$sku_key = '',$sku_desc = ''){
        global $_W;
        if(!check_id($goods_id) || empty($data) || !is_array($data)){
            return false;
        }
        $where = array(
            'uniacid' =>  $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'store_type' => STORE_TYPE_OTO,
            'goods_id' => $goods_id,
            'sku_key' => $sku_key,
            'sku_desc' => $sku_desc
        );
        $status = pdo_update(self::$_db_order_shopping_cart,$data,$where);
        return !$status?false:true;
    }


    /**
     * @return array|null
     * 获取购物车列表
     */
    public static function getShoppingCartList(){
        global $_W;
        $where = "a.uniacid='{$_W['uniacid']}' AND a.uid='{$_W['member']['uid']}' AND a.store_type=".STORE_TYPE_OTO;
        $list = pdo_fetchall("SELECT a.*,b.`title` AS goods_name,b.`thumb`,b.`sale_price`,b.`limit_time_price`,b.`total`,b.`is_open_limit_time_buy`,b.`limit_time_buy_start`,b.`limit_time_buy_end`,b.`sku_list`,b.`is_open_spec`,b.`unit`,c.`title` FROM ".tablename(self::$_db_order_shopping_cart)." a LEFT JOIN ".tablename(self::$_db_goods_list)." b ON a.goods_id=b.id LEFT JOIN ".tablename(self::$_db_store_list)." c ON a.store_id=c.id  WHERE {$where}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['thumb'] = tomedia($v['thumb']);
                if(!empty($v['sku_list'])){
                    $v['sku_list'] = iunserializer($v['sku_list']);
                }
            }
            return $list;
        }
        return null;
    }


    /**
     * @param int $pindex
     * @param int $psize
     * @return array|null
     * 获取会员收货地址
     */
    public static function getDeliverAddressList($pindex =0 ,$psize = 15){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_deliver_address)." WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }


    /**
     * @return array|null
     * 获取默认地址
     */
    public static function getDefaultDeliverAddress(){
        global $_W;
        $info = pdo_fetch("SELECT * FROM ".tablename(self::$_db_deliver_address)." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' ORDER BY isdefault DESC LIMIT 1");
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param int $id
     * @return array|null
     * 根据ID获取地址信息
     */
    public static function getDeliverAddressById($id = 0){
        global $_W;
        if(!check_id($id)){
            return null;
        }
        $info = pdo_get(self::$_db_deliver_address,array('uniacid'=>$_W['uniacid'],'uid'=>$_W['member']['uid'],'id'=>$id));
        return !empty($info) && is_array($info)?$info:null;
    }


    /**
     * @param array $ids
     * @return bool
     * 批量删除购物车商品
     */
    public static function deleteShoppingCartByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $status = pdo_query("DELETE FROM ".tablename(self::$_db_order_shopping_cart)." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND store_type=".STORE_TYPE_OTO." AND id IN (".implode(',',$ids).")");
            return !$status?false:true;
        }
        return false;
    }

    /**
     * @param int $id
     * @return array|bool|null
     * 获取购物车信息
     */
    public static function getShoppingCartInfoById($id= 0){
        global $_W;
        if(!check_id($id)){
            return false;
        }
        $where = array(
            'uniacid'=>$_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'store_type' => STORE_TYPE_OTO,
            'id' => $id
        );
        $info = pdo_get(self::$_db_order_shopping_cart,$where);
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 修改购物车信息
     */
    public static function updateShoppingCartInfoById($id =0 ,$data =array()){
        global $_W;
        if(!check_id($id) || empty($data) || !is_array($data)){
            return false;
        }
        $where = array(
            'uniacid'=>$_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'store_type' => STORE_TYPE_OTO,
            'id' => $id
        );
        $status = pdo_update(self::$_db_order_shopping_cart,$data,$where);
        return !$status?false:true;
    }


    /**
     * @param array $ids
     * @return array|null
     * 根据购物车ID获取商品信息
     */
    public static function getGoodsInfoByCartIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $sql = "SELECT a.*,b.`is_free_post`,b.`postage_type`,b.`postage_money`,b.`postage_id`,b.`title` AS goods_title,b.`is_open_spec`,b.`title` AS goods_name,b.`thumb`,b.`cost_price`,b.`market_price`,b.`sale_price`,b.`limit_time_price`,b.`weight`,b.`total`,b.`is_open_limit_time_buy`,b.`limit_time_buy_start`,b.`limit_time_buy_end`,b.`sku_list`,c.`title` AS store_title,c.`logo` AS store_logo FROM "
                .tablename(self::$_db_order_shopping_cart). " a LEFT JOIN "
                .tablename(self::$_db_goods_list)." b ON a.goods_id=b.id LEFT JOIN "
                .tablename(self::$_db_store_list)." c ON a.store_id=c.id"
                ." WHERE a.uniacid='{$_W['uniacid']}' AND a.uid='{$_W['member']['uid']}' AND a.store_type=".STORE_TYPE_OTO." AND a.id IN(".implode(',',$ids).")";
            $list = pdo_fetchall($sql);
            if(!empty($list) && is_array($list)){
                foreach($list as $k => &$v){
                    $v['thumb'] = tomedia($v['thumb']);
                    $v['store_logo'] = tomedia($v['store_logo']);
                    $v['sku_list'] = iunserializer($v['sku_list']);
                }
                return $list;
            }
        }
        return null;
    }

    /**
     * @param int $goods_id
     * @return array|null
     * 获取商品和店铺信息
     */
    public static function getGoodsStoreInfoByGoodsId($goods_id = 0){
        global $_W;
        if(!check_id($goods_id)){
            return false;
        }
        $info = pdo_fetch("SELECT `store_id`,`title` AS goods_title,`is_open_spec`,`title` AS goods_name,`thumb`,`cost_price`,`market_price`,`sale_price`,`limit_time_price`,`weight`,`total`,`is_open_limit_time_buy`,`limit_time_buy_start`,`limit_time_buy_end`,`sku_list`,`is_free_post`,`postage_type`,`postage_money`,`postage_id` FROM ".tablename(self::$_db_goods_list)." WHERE uniacid='{$_W['uniacid']}' AND id='{$goods_id}' AND store_type=".STORE_TYPE_OTO);
        if(!empty($info) && is_array($info)){
            $store_info = pdo_fetch("SELECT `title` AS store_title,`logo` AS store_logo FROM ".tablename(self::$_db_store_list)." WHERE uniacid='{$_W['uniacid']}' AND id='{$info['store_id']}' AND type=".STORE_TYPE_OTO);
            if(!empty($store_info) && is_array($store_info)){
                $info['store_title'] = $store_info['store_title'];
                $info['store_logo'] = $store_info['store_logo'];
            }
            $info['thumb'] = tomedia($info['thumb']);
            $info['store_logo'] = tomedia($info['store_logo']);
            $info['sku_list'] = iunserializer($info['sku_list']);
            return $info;
        }
        return null;
    }

    /**
     * @param array $data
     * @return bool
     * 插入订单信息，成功返回插入的订单ID
     */
    public static function insertOrderInfoReturnInsertId($data = array()){
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$_db_order_list,$data);
            if(!$status){
                return false;
            }
            return pdo_insertid();
        }
        return false;
    }


    /**
     * @param array $ids
     * @param array $data
     * @return bool
     * 批量修改订单信息
     */
    public static function updateMemberOrderInfoByIds($ids = array(),$data = array()){
        global $_W;
        if(empty($ids) || !is_array($ids) || empty($data) || !is_array($data)){
            return false;
        }
        $set = "";
        foreach($data as $k => $v){
            $set .= "`{$k}`='{$v}',";
        }
        $set = mb_substr($set,0,-1,'utf-8');
        $status = pdo_query("UPDATE ".tablename(self::$_db_order_list)." SET {$set} WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND store_type=".STORE_TYPE_OTO." AND id IN (".implode(',',$ids).")");
        return !$status?false:true;
    }

    /**
     * @param array $data
     * @return bool
     * 插入订单商品信息
     */
    public static function insertOrderGoodsInfo($data = array()){
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$_db_order_goods,$data);
            return !$status?false:true;
        }
        return false;
    }

    /**
     * @param array $data
     * @return bool
     * 插入支付记录，返回记录Id
     */
    public static function insertOrderPayLogReturnInsertId($data = array()){
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$_db_pay_log,$data);
            if(!$status){
                return false;
            }
            return pdo_insertid();
        }
        return false;
    }

    /**
     * @param array $data
     * @return bool
     * 店内支付订单生成
     */
    public static function insertOfflineOrderInfoReturnInsertId($data = array()){
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$_db_order_offline,$data);
            if(!$status){
                return false;
            }
            return pdo_insertid();
        }
        return false;
    }

    /**
     * @param string $status
     * @param int $pindex
     * @param int $psize
     * @return array|null
     * 获取订单列表
     */
    public static function getMemberOrderList($status = '0',$pindex=0,$psize = 20){
        global $_W;
        $where = "a.uniacid='{$_W['uniacid']}' AND a.uid='{$_W['member']['uid']}' AND a.store_type=".STORE_TYPE_OTO;
        if($status == 1){
            $where .= " AND a.order_status=".ORDER_STATUS_NOT_PAY;
        }elseif($status == 2){
            $where .= " AND a.order_status=".ORDER_STATUS_NOT_DELIVER;
        }elseif($status == 3){
            $where .= " AND a.order_status=".ORDER_STATUS_NOT_CONFIRM;
        }elseif($status == 4){
            $where .= " AND a.is_talk=".TALK_NO." AND a.order_status=".ORDER_STATUS_COMPLETE;
        }elseif($status == 5){
            $where .= " AND a.order_status=".ORDER_STATUS_NOT_RETURN;
        }

        $where .= " ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT a.*,b.`goods_name`,b.`thumb` FROM ".tablename(self::$_db_order_list)." a LEFT JOIN ".tablename(self::$_db_order_goods)." b ON a.id=b.order_id WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }


    /**
     * @param int $store_id
     * @param int $pindex
     * @param int $psize
     * @return array|null
     * 获取会员店内支付订单
     */
    public static function getMemberOfflineOrderList($pindex = 0, $psize = 50){
        global $_W;
        $where = "a.uniacid='{$_W['uniacid']}' AND a.uid='{$_W['member']['uid']}' AND a.store_type=".STORE_TYPE_OTO." AND a.is_delete=".DELETE_NO;
        $where .= " ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT a.*,b.title FROM ".tablename(self::$_db_order_offline)." a LEFT JOIN ".tablename(self::$_db_store_list)." b ON a.store_id=b.id WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @return bool
     * 支付日志数量
     */
    public static function getTodayPayLogCount(){
        global $_W;
        $starttime = strtotime(date('Y-m-d').' 00:00:00');
        $endtime = strtotime(date('Y-m-d').' 23:59:59');
        $sql = "SELECT COUNT(1) FROM ".tablename(self::$_db_pay_log)." WHERE uniacid='{$_W['uniacid']}' AND createtime BETWEEN {$starttime} AND {$endtime}";
        return pdo_fetchcolumn($sql);
    }


    /**
     * @return bool
     * 获取今天线上订单数目     */
    public static function getTodayOfflineOrderCount(){
        global $_W;
        $starttime = strtotime(date('Y-m-d').' 00:00:00');
        $endtime = strtotime(date('Y-m-d').' 23:59:59');
        $sql = "SELECT COUNT(1) FROM ".tablename(self::$_db_order_offline)." WHERE uniacid='{$_W['uniacid']}' AND createtime BETWEEN {$starttime} AND {$endtime}";
        return pdo_fetchcolumn($sql);
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 修改订单信息
     */
    public static function updateMemberOrderInfoById($id = 0,$data = array()){
        global $_W;
        if(!check_id($id) || empty($data) || !is_array($data)){
            return false;
        }
        $where = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'store_type' => STORE_TYPE_OTO,
            'id' => $id
        );
        $status = pdo_update(self::$_db_order_list,$data,$where);
        return !$status?false:true;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     * 修改线上支付订单
     */
    public static function updateMemberOfflineOrderInfoById($id = 0,$data = array()){
        global $_W;
        if(!check_id($id) || empty($data) || !is_array($data)){
            return false;
        }
        $where = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'store_type' => STORE_TYPE_OTO,
            'id' => $id
        );
        $status = pdo_update(self::$_db_order_offline,$data,$where);
        return !$status?false:true;
    }

    /**
     * @return null
     * 获取不同状态的订单数目
     */
    public static function getOrderCountGroupByStatus(){
        global $_W;
        $list = pdo_fetchall("SELECT order_status,COUNT(1) AS count FROM ".tablename(self::$_db_order_list)." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND store_type=".STORE_TYPE_OTO." GROUP BY order_status",array(),'order_status');
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @return bool
     * 获取待评价订单数目
     */
    public static function getOrderNotTalkCount(){
        global $_W;
        return pdo_fetchcolumn("SELECT COUNT(1) AS count FROM ".tablename(self::$_db_order_list)." WHERE uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}' AND store_type=".STORE_TYPE_OTO." AND order_status=".ORDER_STATUS_COMPLETE." AND is_talk=".TALK_NO);
    }

    /**
     * @param int $store_id
     * @return array|bool|null
     * 根据商家ID获取商家店铺设置信息
     */
    public static function getStoreShopConfigByStoreId($store_id = 0){
        global $_W;
        if(!check_id($store_id)){
            return null;
        }
        $info = pdo_get(self::$_db_store_config,array(
            'uniacid' => $_W['uniacid'],
            'store_id' => $store_id,
            'store_type' => STORE_TYPE_OTO
        ));
        if(!empty($info) && is_array($info)){
            $info['setting'] = iunserializer($info['setting']);
            if(isset($info['setting']['header_bg'])){
                $info['setting']['header_bg'] = tomedia($info['setting']['header_bg']);
            }
            return $info;
        }
        return null;
    }

    /**
     * @param int $store_id
     * @return array|null
     * 根据商家ID获取所有轮播
     */
    public static function getStoreSlideList($store_id = 0){
        global $_W;
        if(!check_id($store_id)){
            return null;
        }
        $list = pdo_getall(self::$_db_store_slide,array(
            'uniacid'=>$_W['uniacid'],
            'store_id' => $store_id,
            'store_type' => STORE_TYPE_OTO
        ));
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['thumb'] = tomedia($v['thumb']);
            }
            return $list;
        }
        return null;
    }

    /**
     * @param int $store_id
     * @return bool
     * 根据店铺ID获取商品数目
     */
    public static function getStoreGoodsTotalCount($store_id = 0){
        global $_W;
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_goods_list)." WHERE uniacid='{$_W['uniacid']}' AND store_id='{$store_id}' AND store_type=".STORE_TYPE_OTO." AND is_display=".DISPLAY_YES." AND is_check=".CHECK_PASS);
    }



    /**
     * @param int $store_id
     * @param int $pindex
     * @param int $pszie
     * @return array|null
     * 获取商家商品列表
     */
    public static function getStoreGoodsList($store_id = 0,$pindex = 0,$psize = 20){
        global $_W;
        if(!check_id($store_id)){
            return null;
        }
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$store_id}' AND store_type=".STORE_TYPE_OTO;
        $where .= " AND total>0 AND is_display=".DISPLAY_YES;
        $where .= " AND is_check=".CHECK_PASS;
        $where .= " ORDER BY order_by DESC";
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_goods_list)." WHERE {$where} LIMIT {$pindex},{$psize}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['thumb'] = tomedia($v['thumb']);
                $v['sale_price'] = getGoodsTruePrice($v);
            }
            return $list;
        }
        return null;
    }

    /**
     * @param int $store_id
     * @param string $keywords
     * @param int $store_category_id
     * @param int $sub_store_category_id
     * @return bool|null
     * 返回符合条件的商家商品数目
     */
    public static function getStoreGoodsCount($store_id = 0,$keywords = '',$store_category_id = 0,$sub_store_category_id = 0){
        global $_W;
        if(!check_id($store_id)){
            return null;
        }
        $where = "uniacid='{$_W['uniacid']}' AND store_id='{$store_id}' AND store_type=".STORE_TYPE_OTO;
        $where .= " AND total>0 AND is_display=".DISPLAY_YES;
        $where .= " AND is_check=".CHECK_PASS;
        if(!empty($keywords)){
            $where .= " AND title LIKE '%{$keywords}%'";
        }
        if(!empty($store_category_id)){
            $where .= " AND store_category_id ='{$store_category_id}'";
        }
        if(!empty($sub_store_category_id)){
            $where .= " AND sub_store_category_id='{$sub_store_category_id}'";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_goods_list)." WHERE {$where}");
    }


    /**
     * @param store_id
     * @param $sort
     * @param $keyword
     * @param $distance_range
     * @param int $category_id
     * @param $pindex
     * @param $psize
     * @return array|null
     * 获取同城城市商品
    */
    public static function getLocationCityPlatformGoodsList($keyword = '',$store_id = 0,$sort,$category_id =0,$s_cid =0,$s_sub_cid=0,$pindex,$psize,$distance_range){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO." AND total>0";
        $where .= " AND is_display=".DISPLAY_YES;
        $where .= " AND is_check=".CHECK_PASS;
        if(!empty($store_id)){
            $where .= " AND store_id='{$store_id}'";
        }
        if(!empty($category_id)){
            $where .= " AND category_id='{$category_id}'";
        }
        if(!empty($s_cid)){
            $where .= " AND store_category_id IN (SELECT id FROM ".tablename('store_goods_category')." WHERE uniacid='{$_W['uniacid']}' AND parent_id='{$s_cid}')";
        }
        if(!empty($s_sub_cid)){
            $where .= " AND store_category_id='{$s_sub_cid}'";
        }
        if(empty($distance_range) || $store_id == '3928'){
            $where .= " AND store_id IN (SELECT id FROM ".tablename(self::$_db_store_list)." WHERE uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO." AND city='{$_W['location']['city']}')";
        }else{
            $distance_range_limit = getLocationRange($_W['location']['lng'],$_W['location']['lat'],$distance_range);
            $where .= " AND store_id IN (SELECT id FROM ".tablename(self::$_db_store_list)." WHERE uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO." AND city='{$_W['location']['city']}' AND lat BETWEEN {$distance_range_limit['lat_start']} AND {$distance_range_limit['lat_end']} AND lng BETWEEN {$distance_range_limit['lng_start']} AND {$distance_range_limit['lng_end']})";
        }
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        if($sort == 1){//人气最高
            $where .= " ORDER BY sale_price DESC";
        }elseif($sort == 2){//人气最低
            $where .= " ORDER BY sale_price ASC";
        }elseif($sort == 3){//浏览量最高
            $where .= " ORDER BY collect_count DESC";
        }elseif($sort == 4){//浏览量最低
            $where .= " ORDER BY collect_count ASC";
        }elseif($sort == 5){ //信誉最高
            $where .= " ORDER BY sale_count DESC";
        }elseif($sort == 6){//信誉最低
            $where .= " ORDER BY sale_count ASC";
        }else{
            $where .= " ORDER BY order_by DESC";
        }
        $where .= " LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_goods_list)." WHERE {$where}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['thumb'] = tomedia($v['thumb']);
                $v['sale_price'] = getGoodsTruePrice($v);
            }
            return $list;
        }
        return null;
    }


    /**
     * @param $category_id
     * @param $keyword
     * @param $sort
     * @param $pindex
     * @param $psize
     * @return array|null
     * 获取同城店铺
     */
    public static function getLocationCityPlatformStoreList($category_id,$keyword,$sort,$pindex,$psize,$distance_range){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND type=".STORE_TYPE_OTO;
        if(empty($distance_range)){
            $where .=" AND city='{$_W['location']['city']}'";
        }else{
            $distance_range_limit = getLocationRange($_W['location']['lng'],$_W['location']['lat'],$distance_range);
            $where .= " AND city='{$_W['location']['city']}' AND lat BETWEEN {$distance_range_limit['lat_start']} AND {$distance_range_limit['lat_end']} AND lng BETWEEN {$distance_range_limit['lng_start']} AND {$distance_range_limit['lng_end']}";
        }
        if(!empty($category_id)){
            $where .= " AND category_id='{$category_id}'";
        }
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        if($sort == 1){//人气最高
            $where .= " ORDER BY collect_count DESC";
        }elseif($sort == 2){//人气最低
            $where .= " ORDER BY collect_count ASC";
        }elseif($sort == 3){//浏览量最高
            $where .= " ORDER BY look_count DESC";
        }elseif($sort == 4){//浏览量最低
            $where .= " ORDER BY look_count ASC";
        }elseif($sort == 5){ //信誉最高
            $where .= " ORDER BY credit_count DESC";
        }elseif($sort == 6){//信誉最低
            $where .= " ORDER BY credit_count ASC";
        }else{
            $where .= " ORDER BY order_by DESC";
        }
        $where .= " LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_store_list)." WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }


    /**
     * @param int $id
     * @return array|null
     * 获取订单信息
     */
    public static function getMemberOrderInfoById($id = 0){
        global $_W;
        if(!check_id($id)){
            return null;
        }
        $info = pdo_get(self::$_db_order_list,array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'id' => $id
        ));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param int $id
     * @return array|null
     */
    public static function getMemberOfflineOrderInfoById($id = 0){
        global $_W;
        if(!check_id($id)){
            return null;
        }
        $info = pdo_get(self::$_db_order_offline,array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'id' => $id
        ));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param int $id
     * @param int $type
     * @param int $store_type
     * @return array|null
     * 获取搜藏信息
     */
    public static function getMemberCollectInfo($id = 0,$type = COLLECT_TYPE_GOODS,$store_type = STORE_TYPE_OTO){
        global $_W;
        if(!check_id($id)){
            return null;
        }
        $info = pdo_get(self::$_db_mc_member_collect,array(
            'uniacid'=>$_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'store_type' => $store_type,
            'type' => $type,
            'flag_id' => $id
        ));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param int $id
     * @param int $type
     * @param int $store_type
     * @return array|null
     * 删除搜藏的
     */
    public static function deleteMemberCollectInfo($id = 0,$type = COLLECT_TYPE_GOODS,$store_type = STORE_TYPE_OTO){
        global $_W;
        if(!check_id($id)){
            return null;
        }
        pdo_begin();
        $status1 = pdo_delete(self::$_db_mc_member_collect,array(
            'uniacid'=>$_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'store_type' => $store_type,
            'type' => $type,
            'flag_id' => $id
        ));
        $status_2 = pdo_query("UPDATE ".tablename($type == COLLECT_TYPE_STORE?self::$_db_store_list:self::$_db_goods_list)." SET collect_count=collect_count-1 WHERE uniacid='{$_W['uniacid']}' AND id='{$id}' AND ".($type == COLLECT_TYPE_STORE?'type':'store_type')."='{$store_type}'");
        if($status1 && $status_2){
            pdo_commit();
            return true;
        }
        pdo_rollback();
        return false;
    }

    /**
     * @param array $data
     * @return bool
     * 添加收藏
     */
    public static function insertMemberCollectInfo($data = array()){
        if(!empty($data) && is_array($data)){
            pdo_begin();
            $status1 = pdo_insert(self::$_db_mc_member_collect,$data);
            $status_2 = pdo_query("UPDATE ".tablename($data['type'] == COLLECT_TYPE_STORE?self::$_db_store_list:self::$_db_goods_list)." SET collect_count=collect_count+1 WHERE uniacid='{$data['uniacid']}' AND id='{$data['flag_id']}' AND ".($data['type'] == COLLECT_TYPE_STORE?'type':'store_type')."='{$data['store_type']}'");
            if($status1 && $status_2){
                pdo_commit();
                return true;
            }
            pdo_rollback();
        }
        return false;
    }


    /**
     * @param $keyword
     * @param $pay_status
     * @param $order_status
     * @param $province
     * @param $city
     * @param $district
     * @param $starttime
     * @param $endtime
     * @param $pindex
     * @param $psize
     * @param int $is_export
     * @return array|null
     * 获取订单列表
     */
    public static function getOrderList($store_name,$keyword,$pay_methods,$pay_status,$order_status,$province,$city,$district,$starttime,$endtime,$pindex,$psize,$is_export = EXPORT_NO){
        global $_W;
        $where = "a.uniacid='{$_W['uniacid']}' AND a.store_type=".STORE_TYPE_OTO." AND a.createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($keyword) && is_string($keyword)){
            $where .= " AND (a.order_no LIKE '%{$keyword}%' OR a.username LIKE '%{$keyword}%' OR a.mobile LIKE '%{$keyword}%')";
        }
        $store_where = "uniacid='{$_W['uniacid']}'";
        if(!empty($store_name)){
            $store_where .= " AND title LIKE '%{$store_name}%'";
        }
        if(!empty($province)){
            $store_where .= " AND province='{$province}'";
        }
        if(!empty($city)){
            $store_where .= "AND city='{$city}'";
        }
        if(!empty($district)){
            $store_where .= " AND district='{$district}'";
        }
        $where .= " AND a.store_id IN (SELECT id FROM ".tablename(self::$_db_store_list)." WHERE {$store_where})";
        if(!empty($pay_status) && is_array($pay_status)){
            $where .= " AND a.pay_status IN (".implode(',',$pay_status).")";
        }
        if(!empty($order_status) && is_array($order_status)){
            $where .= " AND a.order_status IN (".implode(',',$order_status).")";
        }
        if(!empty($pay_methods) && is_array($pay_methods)){
            $where .= " AND a.pay_method IN (".implode(',',$pay_methods).")";
        }
        $where .= " ORDER BY a.id DESC";
        if($is_export == EXPORT_NO){
            $where .= " LIMIT {$pindex},{$psize}";
        }
        $list = pdo_fetchall("SELECT a.*,b.title AS store_name FROM ".tablename('order_list')." a LEFT JOIN ".tablename(self::$_db_store_list)." b ON a.store_id=b.id WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @param $store_name
     * @param $keyword
     * @param $pay_methods
     * @param $pay_status
     * @param $order_status
     * @param $province
     * @param $city
     * @param $district
     * @param $starttime
     * @param $endtime
     * @return array|null
     * 获取总订单金额
     */
    public static function getOrderTotalPayPrice($store_name,$keyword,$pay_methods,$pay_status,$order_status,$province,$city,$district,$starttime,$endtime){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO." AND createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($keyword) && is_string($keyword)){
            $where .= " AND (order_no LIKE '%{$keyword}%' OR username LIKE '%{$keyword}%' OR mobile LIKE '%{$keyword}%')";
        }
        $store_where = "uniacid='{$_W['uniacid']}'";
        if(!empty($store_name)){
            $store_where .= " AND title LIKE '%{$store_name}%'";
        }
        if(!empty($province)){
            $store_where .= " AND province='{$province}'";
        }
        if(!empty($city)){
            $store_where .= "AND city='{$city}'";
        }
        if(!empty($district)){
            $store_where .= " AND district='{$district}'";
        }
        $where .= " AND store_id IN (SELECT id FROM ".tablename(self::$_db_store_list)." WHERE {$store_where})";
        if(!empty($pay_status) && is_array($pay_status)){
            $where .= " AND pay_status IN (".implode(',',$pay_status).")";
        }
        if(!empty($order_status) && is_array($order_status)){
            $where .= " AND order_status IN (".implode(',',$order_status).")";
        }
        if(!empty($pay_methods) && is_array($pay_methods)){
            $where .= " AND pay_method IN (".implode(',',$pay_methods).")";
        }
        return pdo_fetchcolumn("SELECT SUM(pay_total_price) FROM ".tablename('order_list')." WHERE {$where}");
    }

    /**
     * @param $keyword
     * @param $pay_status
     * @param $order_status
     * @param $province
     * @param $city
     * @param $district
     * @param $starttime
     * @param $endtime
     * @return bool
     * 获取订单数目
     */
    public static  function getOrderCount($store_name,$keyword,$pay_methods,$pay_status,$order_status,$province,$city,$district,$starttime,$endtime){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO." AND createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($keyword) && is_string($keyword)){
            $where .= " AND (order_no LIKE '%{$keyword}%' OR username LIKE '%{$keyword}%' OR mobile LIKE '%{$keyword}%')";
        }
        $store_where = "uniacid='{$_W['uniacid']}'";
        if(!empty($store_name)){
            $store_where .= " AND title LIKE '%{$store_name}%'";
        }
        if(!empty($province)){
            $store_where .= " AND province='{$province}'";
        }
        if(!empty($city)){
            $store_where .= "AND city='{$city}'";
        }
        if(!empty($district)){
            $store_where .= " AND district='{$district}'";
        }
        $where .= " AND store_id IN (SELECT id FROM ".tablename(self::$_db_store_list)." WHERE {$store_where})";
        if(!empty($pay_status) && is_array($pay_status)){
            $where .= " AND pay_status IN (".implode(',',$pay_status).")";
        }
        if(!empty($pay_methods) && is_array($pay_methods)){
            $where .= " AND pay_method IN (".implode(',',$pay_methods).")";
        }
        if(!empty($order_status) && is_array($order_status)){
            $where .= " AND order_status IN (".implode(',',$order_status).")";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('order_list')." WHERE {$where}");
    }

    /**
     * @return array|null
     *获取分销信息
     */
    public static function getDistributionConfig(){
        global $_W;
        $info = pdo_get(self::$_db_distribution_config,array(
            'uniacid' => $_W['uniacid'],
            'module' => MODULE_NAME_OTO
        ));
        if(!empty($info) && is_array($info)){
            $info['setting'] = iunserializer($info['setting']);
            return $info;
        }
        return null;
    }

    /**
     * @param array $data
     * @return bool
     * 插入分销设置
     */
    public static function insertDistributionConfig($data = array()){
        if(empty($data) || !is_array($data)){
            return false;
        }
        $status = pdo_insert(self::$_db_distribution_config,$data);
        return !$status?false:true;
    }

    /**
     * @param array $data
     * @return bool
     * 修改分销信息
     */
    public static function updateDistributionConfig($data =array()){
        if(empty($data) || !is_array($data)){
            return false;
        }
        global $_W;
        $status = pdo_update(self::$_db_distribution_config,$data,array(
            'uniacid'=>$_W['uniacid'],
            'module' => MODULE_NAME_OTO
        ));
        return !$status?false:true;
    }

    /**
     * @param string $type
     * @return bool
     * 获取会员收藏数目
     */
    public static function getMemberCollectCount($type = '3'){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND uid='{$_W['member']['uid']}'";
       if($type != 3){
           $where .= " AND type='{$type}'";
       }
       return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_mc_member_collect)." WHERE {$where}");
    }

    /**
     * @param array $data
     * @return bool
     * 插入足迹列表
     */
    public static function insertMemberFootMark($data = array()){
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$_db_mc_member_footmark,$data);
            return !$status?false:true;
        }
        return false;
    }


    /**
     * @param int $goods_id
     * @return null
     */
    public static function getMemberFootMark($goods_id = 0){
        global $_W;
        if(!check_id($goods_id)){
            return null;
        }
        $info = pdo_get(self::$_db_mc_member_footmark,array('uniacid' => $_W['uniacid'],'goods_id'=>$goods_id,'uid'=>$_W['member']['uid']));
        return !empty($info) && is_array($info)?$info:null;
    }


    /**
     * @param $keyword
     * @param int $num
     * @return array|null
     * 获取成为分销商时搜索的商品
     */
    public static function getDistributorSearchGoods($keyword,$num = 20){
        global $_W;
        $where  = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO;
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }
        $where .= " ORDER BY order_by DESC LIMIT {$num}";
        $list = pdo_fetchall("SELECT id,thumb,title,sale_price FROM ".tablename(self::$_db_goods_list)." WHERE {$where}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['thumb'] = tomedia($v['thumb']);
            }
            return $list;
        }
        return null;
    }

    /**
     * @param $ids
     * @return array|null
     *分销商已选择的商品
     */
    public static function getDistributorSearchGoodsByIds($ids){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $where  = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO;
            $where .= " AND id IN (".implode(',',$ids).")";
            $where .= " ORDER BY order_by DESC";
            $list = pdo_fetchall("SELECT id,title FROM ".tablename(self::$_db_goods_list)." WHERE {$where}");
            return !empty($list) && is_array($list)?$list:null;
        }
        return null;
    }

    /**
     * @return bool|null
     * 获取公众号信息
     */
    public static function getUniSetting(){
        global $_W;
        $info = pdo_get(self::$_db_uni_settings,array('uniacid' => $_W['uniacid']));
        if(!empty($info) && is_array($info)){
            return $info;
        }
        return null;
    }

    /**
     * @param $data
     * @return bool
     * 修改公众号设置
     */
    public static function updateUniSetting($data){
        global $_W;
        if(!empty($data) && is_array($data)){
            $status = pdo_update(self::$_db_uni_settings,$data,array('uniacid'=>$_W['uniacid']));
            return !$status?false:true;
        }
        return false;
    }

    /**
     * @param $data
     * @return bool
     * 插入公众号设置
     */
    public static function insertUniSetting($data){
        global $_W;
        if(!empty($data) && is_array($data)){
            $status = pdo_insert(self::$_db_uni_settings,$data);
            return !$status?false:true;
        }
        return false;
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
     * 获取线下订单列表
     */
    public static function getOrderOfflineList($keyword,$pay_status,$starttime,$endtime,$pindex,$psize,$is_export = EXPORT_NO){
        global $_W;
        $where = "a.uniacid='{$_W['uniacid']}' AND a.store_type=".STORE_TYPE_OTO." AND a.createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($keyword)){
            $where .= " AND (a.uid='{$keyword}' OR a.order_no LIKE '%{$keyword}%')";
        }
        if(!empty($pay_status) && is_array($pay_status)){
            $where .= " AND a.pay_status IN (".implode(',',$pay_status).")";
        }
        $where .= " ORDER BY createtime DESC";
        if($is_export != EXPORT_YES){
            $where .= " LIMIT {$pindex},{$psize}";
        }
        $list = pdo_fetchall("SELECT a.*,b.title AS store_title,c.nickname,c.realname FROM ".tablename(self::$_db_order_offline)." a LEFT JOIN ".tablename(self::$_db_store_list)." b ON a.store_id=b.id LEFT JOIN ".tablename(self::$_db_mc_members)." c ON a.uid=c.uid WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }


    /**
     * @param $keyword
     * @param $pay_status
     * @param $starttime
     * @param $endtime
     * @return array|null
     */
    public static function getOrderOfflineTotalPayPrice($keyword,$pay_status,$starttime,$endtime){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO." AND createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($keyword)){
            $where .= " AND ( uid='{$keyword}' OR order_no LIKE '%{$keyword}%')";
        }
        if(!empty($pay_status) && is_array($pay_status)){
            $where .= " AND pay_status IN (".implode(',',$pay_status).")";
        }
        return pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename(self::$_db_order_offline)." WHERE {$where}");
    }

    /**
     * @param $keyword
     * @param $pay_status
     * @param $starttime
     * @param $endtime
     * @return bool
     * 获取线下订单数量
     */
    public static function getOrderOfflineCount($keyword,$pay_status,$starttime,$endtime){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND store_type=".STORE_TYPE_OTO." AND createtime BETWEEN {$starttime} AND {$endtime}";
        if(!empty($keyword)){
            $where .= " AND (uid='{$keyword}' OR order_no LIKE '%{$keyword}%')";
        }
        if(!empty($pay_status) && is_array($pay_status)){
            $where .= " AND pay_status IN (".implode(',',$pay_status).")";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_order_offline)." WHERE {$where}");
    }

    /**
     * @return array|null
     * 获取代理轮播图
     */
    public static function getServicerSlideList(){
        global $_W;
        $slides = null;
        if(!empty($_W['location']['city'])){
            $where = "uniacid='{$_W['uniacid']}' AND city='{$_W['location']['city']}'";
            if(in_array($_W['location']['city'],self::$bigCity)){
                $where .= " AND district='{$_W['location']['district']}'";
            }
            $slides = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_servicer_slide)." WHERE uniacid='{$_W['uniacid']}' AND is_display=".DISPLAY_YES." AND servicer_id IN (SELECT id FROM ".tablename(self::$_db_servicer_user)." WHERE {$where}) ORDER BY order_by DESC");
            if(check_data($slides)){
                foreach($slides as $k => &$v){
                    $v['thumb'] = tomedia($v['thumb']);
                }
            }
        }
        return $slides;
    }

    /**
     * @param $id
     * @return array|null
     * 获取代理信息表
     */
    public static function getAgentInfoById($id){
        global $_W;
        $info = pdo_get(self::$_db_agent_user,array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param $uid
     * @return array|null
     * 根据uid获取代理信息
     */
    public static function getAgentInfoByUid($uid){
        global $_W;
        $info = pdo_get(self::$_db_agent_user,array(
            'uniacid' => $_W['uniacid'],
            'uid' => $uid
        ));
        return !empty($info) && is_array($info)?$info:null;
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     * 修改会员信息
     */
    public static function updateAgentInfoById($id,$data){
        global $_W;
        $status = pdo_update(self::$_db_agent_user,$data,array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
        return !$status?false:true;
    }

    /**
     * @param $data
     * @return bool
     * 插入代理信息
     */
    public static function insertAgentInfo($data){
        global $_W;
        $status = pdo_insert(self::$_db_agent_user,$data);
        return !$status?false:true;
    }

    /**
     * @param $keyword
     * @param $is_check
     * @param $pindex
     * @param $psize
     * @return array|null
     * 获取代理列表
     */
    public static function getAgentList($keyword,$is_check,$province,$city,$district,$pindex,$psize){
        global $_W;
        $where = "a.uniacid='{$_W['uniacid']}'";
        if(!empty($keyword)){
            if(is_numeric($keyword)){
                $where .= " AND (a.uid='{$keyword}' OR b.nickname LIKE '%{$keyword}%' OR b.realname LIKE '%{$keyword}%')";
            }else{
                $where .= " AND (a.nickname LIKE '%{$keyword}%' OR a.realname LIKE '%{$keyword}%')";
            }
        }
        if(check_data($is_check)){
           $where .= " AND a.is_check IN (".implode(',',$is_check).")";
        }
        if(!empty($province)){
            $where .= " AND a.province='{$province}'";
        }
        if(!empty($city)){
            $where .= " AND a.city='{$city}'";
        }
        if(!empty($district)){
            $where .= " AND a.district='{$district}'";
        }
        $list = pdo_fetchall("SELECT a.* ,b.nickname,b.realname FROM ".tablename(self::$_db_agent_user)." a LEFT JOIN ".tablename(self::$_db_mc_members)." b ON a.uid=b.uid WHERE {$where} ");
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @param $keyword
     * @param $is_check
     * @return bool
     * 获取代理数目
     */
    public static function getAgentCount($keyword,$is_check,$province,$city,$district){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}'";
        if(!empty($keyword)){
            $where .= " AND uid IN (SELECT id FROM ".tablename(self::$_db_mc_members)." WHERE uniacid='{$_W['uniacid']}' AND nickname LIKE '%{$keyword}%' AND realname LIKE '%{$keyword}%')";
        }
        if(check_data($is_check)){
            $where .= " AND is_check IN (".implode(',',$is_check).")";
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

        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_agent_user)." WHERE {$where} ");
    }


    /**
     * @param array $ids
     * @return bool
     *批量删除代理
     */
    public static function deleteAgentByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$_db_agent_user)." WHERE uniacid='{$_W['uniacid']}' AND id IN ($ids)");
            return !$delete_status?false:true;
        }
        return false;
    }


    /**
     * @param array $ids
     * @return bool
     * 删除客服
     */
    public static function deleteServicerByIds($ids = array()){
        global $_W;
        if(!empty($ids) && is_array($ids)){
            $ids = implode(',',$ids);
            $delete_status = pdo_query("DELETE FROM ".tablename(self::$_db_servicer_user)." WHERE uniacid='{$_W['uniacid']}' AND id IN ($ids)");
            return !$delete_status?false:true;
        }
        return false;
    }


    /**
     * @return bool
     * 获取会员核实信息
     */
    public static function getMemberCheckInfo(){
        global $_W;
        $info = pdo_get(self::$_db_mc_check,array(
            'uniacid' => $_W['uniacid'],
            'uid'=> $_W['member']['uid']
        ));
        return !empty($info) && is_array($info)?$info:null;
    }


    /**
     * @param $keyword
     * @param $search_type
     * @param $pindex
     * @param $psize
     * @return array|null
     * 获取积分商城列表
     */
    public static function getNearbyCreditStoreList($pindex,$psize,$distance_range = 5){
        global $_W;
        if(empty($_W['location']['city'])){
            return null;
        }
        $where = "uniacid='{$_W['uniacid']}' AND type=".STORE_TYPE_OTO." AND saler_uid IN (SELECT uid FROM ".tablename(self::$_db_fangyuanbao_user)." WHERE uniacid='{$_W['uniacid']}' AND product_key='3')";
        if(empty($distance_range)){
            $where .= " AND city='{$_W['location']['city']}'";
        }else{
            $distance_range_limit = getLocationRange($_W['location']['lng'],$_W['location']['lat'],$distance_range);
            $where .= " AND lat BETWEEN {$distance_range_limit['lat_start']} AND {$distance_range_limit['lat_end']} AND lng BETWEEN {$distance_range_limit['lng_start']} AND {$distance_range_limit['lng_end']}";
        }
        $where .= " ORDER BY order_by DESC LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_store_list)." WHERE {$where}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                $v['logo'] = tomedia($v['logo']);
                $v['distance'] = getDistanceByLocations($_W['location']['lat'],$_W['location']['lng'],$v['lat'],$v['lng']);
                $v['distance'] = $v['distance'] > 1000?round(floatval($v['distance']/1000),1).'km':round(floatval($v['distance']),2).'m';
            }
        }
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @param $id
     * @return array|null
     * 获取客服信息
     */
    public static function getServicerInfoById($id){
        global $_W;
        $info = pdo_get(self::$_db_servicer_user,array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
        return check_data($info)?$info:null;
    }



    /**
     * @param $username
     * @return bool|null
     * 根据用户名获取客服信息
     */
    public static function getServicerInfoByUsername($username){
        global $_W;
        $info = pdo_get(self::$_db_servicer_user,array(
            'uniacid' => $_W['uniacid'],
            'username' => $username
        ));
        return check_data($info)?$info:null;
    }


    /**
     * @param $data
     * @return bool
     * 插入客服信息
     */
    public static function insertServicerInfo($data){
        $status = pdo_insert(self::$_db_servicer_user,$data);
        return !$status?false:true;
    }


    /**
     * @param $id
     * @param $data
     * @return bool
     * 修改客服信息
     */
    public static function updateServicerInfoById($id,$data){
        global $_W;
        $status = pdo_update(self::$_db_servicer_user,$data,array(
            'uniacid'=>$_W['uniacid'],
            'id' => $id
        ));
        return !$status?false:true;
    }



    /**
     * @param $keyword
     * @param $is_check
     * @param $province
     * @param $city
     * @param $district
     * @param $pindex
     * @param $psize
     * @return array|null
     * 获取客服列表
     */
    public static function getServicerList($keyword,$is_check,$province,$city,$district,$pindex,$psize){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}'";
        if(!empty($keyword)){
            $where .= " AND username LIKE '%{$keyword}%'";
        }
        if(!empty($is_check) && is_array($is_check)){
            $where .= " AND is_check IN (".implode(',',$is_check).")";
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
        $where .= " ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT * FROM ".tablename(self::$_db_servicer_user)." WHERE {$where}");
        return !empty($list) && is_array($list)?$list:null;
    }

    /**
     * @param $keyword
     * @param $is_check
     * @param $province
     * @param $city
     * @param $district
     * @return bool
     * 获取客服数目
     */
    public static function getServicerCount($keyword,$is_check,$province,$city,$district){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}'";
        if(!empty($keyword)){
            $where .= " AND username LIKE '%{$keyword}%'";
        }
        if(!empty($is_check) && is_array($is_check)){
            $where .= " AND is_check IN (".implode(',',$is_check).")";
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
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_servicer_user)." WHERE {$where}");
    }
}


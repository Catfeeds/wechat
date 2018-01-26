<?php
load()->func('check');
class AgentModel{
    private static $_db_store_balance_apply = "store_balance_apply";//商家结算列表
    private static $_db_store_list = "store_list";//商家列表
    private static $db_person_balance = "person_balance"; //二维码结算
    private static $db_mc_member = "mc_members"; //会员表

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
        '乌鲁木齐市'
//        '呼和浩特市'
    );

    /**
     * @param $keyword
     * @param $pindex
     * @param $psize
     * @param int $is_export
     * @return array|null
     * 获取商家结算列表
     */
    public static function getStoreBalanceApplyList($keyword,$is_check,$province,$city,$district,$starttime,$endtime,$pindex,$psize){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}'";
        if(!empty($province)){
            $where .= " AND province='{$province}'";
        }
        if(!empty($city)){
            $where .= " AND city='{$city}'";
        }
        if(!empty($district)){
            $where .= " AND district='{$district}'";
        }
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }

        $where2 = "a.uniacid='{$_W['uniacid']}' AND a.store_id IN (SELECT id FROM ".tablename(self::$_db_store_list)." WHERE {$where})";
        if(check_data($is_check)){
            $where2 .= " AND a.is_check IN (".implode(',',$is_check).")";
        }
        if(!empty($starttime)){
            $where2 .=" AND a.createtime>='{$starttime}'";
        }
        if(!empty($endtime)){
            $where2 .= " AND a.createtime<='{$endtime}'";
        }
        $list = pdo_fetchall("SELECT a.*,b.title,b.username AS store_user,b.tel FROM ".tablename(self::$_db_store_balance_apply)." a LEFT JOIN ".tablename(self::$_db_store_list)." b ON a.store_id=b.id WHERE {$where2} ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}");
        return check_data($list)?$list:null;
    }

    /**
     * @param $keyword
     * @return bool
     * 获取商家结算列表数目
     */
    public static function getStoreBalanceApplyCount($keyword,$is_check,$province,$city,$district,$starttime,$endtime){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}'";
        if(!empty($province)){
            $where .= " AND province='{$province}'";
        }
        if(!empty($city)){
            $where .= " AND city='{$city}'";
        }
        if(!empty($district)){
            $where .= " AND district='{$district}'";
        }
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }

        $where2 = "uniacid='{$_W['uniacid']}' AND store_id IN (SELECT id FROM ".tablename(self::$_db_store_list)." WHERE {$where})";
        if(check_data($is_check)){
            $where2 .= " AND is_check IN (".implode(',',$is_check).")";
        }
        if(!empty($starttime)){
            $where2 .=" AND createtime>='{$starttime}'";
        }
        if(!empty($endtime)){
            $where2 .= " AND createtime<='{$endtime}'";
        }
        return pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename(self::$_db_store_balance_apply)." WHERE {$where2}");
    }

    /**
     * @param $keyword
     * @param $is_check
     * @param $pindex
     * @param $psize
     * @return null
     * 手机端获取商家申请列表
     */
    public static function getMobileStoreBalanceApplyList($keyword,$is_check,$starttime,$endtime,$pindex,$psize){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}' AND province='{$_W['province']}' AND city='{$_W['city']}'";
        if($_W['is_big_city']){
            $where .= " AND district='{$_W['district']}'";
        }
        if(!empty($keyword)){
            $where .= " AND title LIKE '%{$keyword}%'";
        }

        $where2 = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime} AND a.store_id IN (SELECT id FROM ".tablename(self::$_db_store_list)." WHERE {$where})";
        if(!empty($is_check)){
            $where2 .= " AND a.is_check='{$is_check}'";
        }
       $list = pdo_fetchall("SELECT a.*,b.title,b.logo,b.province,b.city,b.district,b.tel FROM ".tablename(self::$_db_store_balance_apply)." a LEFT JOIN ".tablename(self::$_db_store_list)." b ON a.store_id=b.id WHERE {$where2} ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}");
       if(check_data($list)){
            foreach($list as $k => &$v){
                $v['logo'] = tomedia($v['logo']);
                $v['createtime'] = date('Y-m-d H:i',$v['createtime']);
            }
            return $list;
       }
        return null;
    }

    /**
     * @param $keyword
     * @param $is_check
     * @param $pindex
     * @param $psize
     * @return null
     * 手机端获取商家申请列表
     */
    public static function getMobilePersonBalanceApplyList($keyword,$is_check,$starttime,$endtime,$pindex,$psize){
        global $_W;
        $where2 = "uniacid='{$_W['uniacid']}' AND province='{$_W['province']}' AND city='{$_W['city']}'";
        if($_W['is_big_city']){
            $where2 .= " AND district='{$_W['district']}'";
        }
        if(!empty($keyword)){
            if(check_id($keyword)){
                $where2 .= " AND uid='{$keyword}'";
            }else{
                $where2 .= " AND (nickname LIKE '%{$keyword}%' OR realname LIKE '%{$keyword}%')";
            }
        }
        $where = "a.uniacid='{$_W['uniacid']}' AND a.createtime BETWEEN {$starttime} AND {$endtime} AND a.uid IN (SELECT uid FROM ".tablename(self::$db_mc_member)." WHERE {$where2})";
        if(!empty($is_check)){
            $where .= " AND a.is_check='{$is_check}'";
        }
        $list = pdo_fetchall("SELECT a.*,b.avatar,b.nickname,b.realname,b.province,b.city,b.district FROM ".tablename(self::$db_person_balance)." a LEFT JOIN ".tablename(self::$db_mc_member)." b ON a.uid=b.uid WHERE {$where} ORDER BY a.createtime DESC LIMIT {$pindex},{$psize}");
        if(check_data($list)){
            foreach($list as $k => &$v){
                $v['avatar'] = tomedia($v['avatar']);
                $v['createtime'] = date('Y-m-d H:i',$v['createtime']);
            }
            return $list;
        }
        return null;
    }
}
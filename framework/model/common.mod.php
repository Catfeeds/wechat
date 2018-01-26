<?php
load()->func('check');
class CommonModel{
    private static $db_mc_members = "mc_members";//会员表


    /**
     * @param int $uid
     * @param int $level
     * @param int $pindex
     * @param int $psize
     * @return array|null
     * 获取团队列表
     */
    public static function getTeamByLevel($uid = 0,$level = RELATION_ONE_LEVEL,$pindex = 0,$psize = 50){
        global $_W;
        $where = "uniacid='{$_W['uniacid']}'";
        if($level == RELATION_THIRD_LEVEL_UP){
            $where .= " AND relation REGEXP '.-[0-9]+-{$uid}-'";
        }elseif($level == RELATION_THIRD_LEVEL){
            $where .= " AND relation REGEXP '^[0-9]+-[0-9]+-{$uid}-'";
        }elseif($level == RELATION_SECOND_LEVEL){
            $where .= " AND relation REGEXP '^[0-9]+-{$uid}-'";
        }else{
            $where .= " AND relation REGEXP '^{$uid}-'";
        }
        $where .= " ORDER BY createtime DESC LIMIT {$pindex},{$psize}";
        $list = pdo_fetchall("SELECT * FROM ".tablename('mc_members')." WHERE {$where}");
        if(!empty($list) && is_array($list)){
            foreach($list as $k => &$v){
                if(empty($v['avatar'])){
                    $v['avatar'] = AVATAR_DEFAULT_SRC;
                }
                $v['avatar'] = tomedia($v['avatar']);
                if(empty($v['nickname'])){
                    $v['nickname'] = '未设置昵称';
                }
                if(empty($v['createtime'])){
                    $v['createtime'] = TIMESTAMP;
                }
                $v['createtime'] = date('Y年m月d日',$v['createtime']);
            }
            return $list;
        }
        return null;
    }
}
<?php
if($op == 'display'){
    $slides = pdo_fetchall("SELECT * FROM ".tablename('sj_news_slide')." WHERE uniacid='{$_W['uniacid']}' AND is_display='1' AND type='0' ORDER BY id DESC LIMIT 0,100");
    $categories = pdo_fetchall("SELECT * FROM ".tablename('sj_news_category')." WHERE uniacid='{$_W['uniacid']}' AND is_display='1' ORDER BY order_by DESC",array(),'id');
    $page = getApartPageNo('page');
    $psize = 50;
    $pindex = ($page-1)*$psize;
    $cid = floor(trim($_GPC['cid']));
    $keyword = trim($_GPC['keyword']);
    $where = "uniacid='{$_W['uniacid']}' AND ((type IN (2,3) AND is_display!='0') OR (type=1 AND is_display='2') OR type=0)";
    if(!empty($cid)){
        $where .= " AND cid='{$cid}'";
    }
    if(!empty($_W['location']['city'])){
        $where .= " AND (city='{$_W['location']['city']}' OR city='' OR ISNULL(city) OR city='太原市')";
    }else{
        $where .= " AND city='太原市'";
    }
    if(!empty($keyword)){
        $where .= " AND title LIKE '%{$keyword}%'";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('sj_news_list')." WHERE {$where} ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    //如果是最后一页，补全20条
    if(count($list) < $psize && $page > 1){
        $pindex2 = ($page-2)*$psize;
        $psize2 = 2*$psize;
        $list = pdo_fetchall("SELECT * FROM ".tablename('sj_news_list')." WHERE {$where} ORDER BY createtime DESC LIMIT {$pindex2},{$psize2}");
    }
    $total = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('sj_news_list')." WHERE {$where}");
    $pager = mobilePagination($total,$page,$psize);

    $ad2Count = 0;
    if(check_data($list)){
        $ad2Count = floor(count($list)/5);
        foreach($list as $k => &$v){
            $v['category'] = $categories[$v['cid']]['title'];
            $v['href'] = $this->createMobileUrl('detail',array('id'=>$v['id']));
            if(!empty($v['thumbs'])){
                $v['thumbs'] = iunserializer($v['thumbs']);
            }
            //判断新闻图片张数选择类型
            $v['show_type'] = 0;
            if(check_data($v['thumbs'])){
                if(empty($v['thumb'])){
                    $v['thumb'] = tomedia($v['thumbs'][0]);
                }
                if(count($v['thumbs']) >= 3){
                    $v['show_type'] = rand(1,100)>50?2:1;
                }else{
                    $v['show_type'] = 1;
                }
                foreach($v['thumbs'] as $k1 => &$thumb1){
                    $thumb1 = tomedia($thumb1);
                }
            }
            $v['thumb'] = tomedia($v['thumb']);
            if(!empty($v['audio_src'])){
                $v['audio_src'] = tomedia($v['audio_src']);
            }
            if(!empty($v['video_src'])){
                $v['video_src'] = tomedia($v['video_src']);
            }
            unset($v['detail']);
        }
    }
    if($_W['isajax']){
        if(!check_data($list)){
            message($page==1?'没有搜索到相关新闻':'没有更多新闻','','error');
        }
        message($list,'','success');
    }

    //轮播广告
    $ad1s = pdo_fetchall("SELECT * FROM ".tablename('sj_news_ad')." WHERE uniacid='{$_W['uniacid']}' AND package_id IN (1,2) AND is_display='1' AND last_time>".TIMESTAMP." ORDER BY look_num ASC LIMIT 0,5");
    if(check_data($ad1s)){
        foreach($ad1s as $k1 => &$ad1){
            $ad1['thumb'] = tomedia($ad1['thumb']);
            pdo_update('sj_news_ad',array(
                'look_num' => $ad1['look_num']+1,
                'updatetime' => TIMESTAMP
            ),array(
                'uniacid' => $_W['uniacid'],
                'id' => $ad1['id']
            ));
        }
    }

    //间隙广告，每5条一个广告
    $ad2s = pdo_fetchall("SELECT * FROM ".tablename('sj_news_ad')." WHERE uniacid='{$_W['uniacid']}' AND package_id IN (3,4) AND is_display='1' AND last_time>".TIMESTAMP." ORDER BY look_num ASC LIMIT 0,{$ad2Count}");
    if(check_data($ad2s)){
        foreach($ad2s as $k2 => &$ad2){
            $ad2['thumb'] = tomedia($ad2['thumb']);
            pdo_update('sj_news_ad',array(
                'look_num' => $ad2['look_num']+1,
                'updatetime' => TIMESTAMP
            ),array(
                'uniacid' => $_W['uniacid'],
                'id' => $ad2['id']
            ));
        }
    }

}
include $this->template('index');
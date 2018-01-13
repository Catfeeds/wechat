<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/9/24
 * Time: 13:34
 */
if($op == 'display'){
    $slides = pdo_fetchall("SELECT * FROM ".tablename('sj_news_slide')." WHERE uniacid='{$_W['uniacid']}' AND is_display='1' ORDER BY id DESC LIMIT 0,100");
    $categories = pdo_fetchall("SELECT * FROM ".tablename('sj_news_category')." WHERE uniacid='{$_W['uniacid']}' AND is_display='1' ORDER BY order_by DESC");
    $page = getApartPageNo('page');
    $psize = 100;
    $pindex = ($page-1)*$psize;
    $cid = floor(trim($_GPC['cid']));
    $keyword = trim($_GPC['keyword']);
    $where = "uniacid='{$_W['uniacid']}' AND ((type IN (2,3) AND is_display!='0') OR (type=1 AND is_display='2') OR type=0)";
    if(!empty($cid)){
        $where .= " AND cid='{$cid}'";
    }
    if(!empty($_W['location']['city'])){
        $where .= " AND (city='{$_W['location']['city']}' OR city='' OR ISNULL(city) OR city='太原市')";
    }
    if(!empty($keyword)){
        $where .= " AND title LIKE '%{$keyword}%'";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('sj_news_list')." WHERE {$where} ORDER BY createtime DESC LIMIT {$pindex},{$psize}");
    if(check_data($list)){
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
                foreach($v['thumbs'] as $k1 => &$thumb1){
                    $thumb1 = tomedia($thumb1);
                }
                if(count($v['thumbs']) >= 3){
                    $v['show_type'] = rand(1,100)>50?2:1;
                }else{
                    $v['show_type'] = 1;
                }
            }

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
}
include $this->template('city');
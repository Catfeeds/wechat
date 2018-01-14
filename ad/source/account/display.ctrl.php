<?php
load()->func('check');
$do = empty($_GPC['do'])?'display':trim($_GPC['do']);
$categories = pdo_fetchall("SELECT * FROM ".tablename('sj_news_category')." WHERE uniacid='{$_W['uniacid']}'",array(),'id');
if($do == 'display'){
    if($_W['isajax']){
        if(empty($_GPC['ids'])){
            message('请选择要删除的文章','','error');
        }
        $ids = implode(',',$_GPC['ids']);
        $delete_status = pdo_query("DELETE FROM ".tablename('sj_news_list')." WHERE uniacid='{$_W['uniacid']}' AND id IN ($ids)");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $category_id = floor(trim($_GPC['category_id']));
    $keyword = trim($_GPC['keyword']);
    $where = "uniacid='{$_W['uniacid']}' AND type='0'";
    if(!empty($keyword)){
        $where .= " AND title LIKE '%{$keyword}%'";
    }
    if(!empty($category_id)){
        $where .= " AND cid='{$category_id}'";
    }
    $list = pdo_fetchall("SELECT * FROM ".tablename('sj_news_list')." WHERE {$where} ORDER BY id DESC LIMIT {$pindex},{$psize}");
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('sj_news_list')." WHERE {$where}"),$page,$psize);
}elseif($do == 'post'){
    $id = floor(trim($_GPC['id']));
    if(check_id($id)){
        $item = pdo_get('sj_news_list',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
    }
    if(!empty($item['thumbs'])){
        $item['thumbs'] = iunserializer($item['thumbs']);
    }
    if($_W['isajax']){
        $data = array(
            'title' => trim($_GPC['title']),
            'cid' => floor(trim($_GPC['cid'])),
            'author' => trim($_GPC['author']),
            'bianshen' => trim($_GPC['bianshen']),
            'tgemail' => trim($_GPC['tgemail']),
            'swhotline' => trim($_GPC['swhotline']),
            'from' => trim($_GPC['from']),
            'desc' => $_GPC['desc'],
            'audio_src' => trim($_GPC['audio_src']),
            'thumbs' => check_data($_GPC['thumbs'])?iserializer($_GPC['thumbs']):'',
            'detail' => $_GPC['detail'],
            'province' => $_W['ad_type'] == 1?$_GPC['area']['province']:$_W['province'],
            'city' =>  $_W['ad_type'] == 1?$_GPC['area']['city']:$_W['city'],
            'district' =>  $_W['ad_type'] == 1?$_GPC['area']['district']:$_W['district'],
            'look_num' => floor(trim($_GPC['look_num'])),
            'zan_num' => floor(trim($_GPC['zan_num']))
        );
        $error = array(
            'title' => '请输入文章标题',
            'cid' => '请选择分类',
            'detail' => '请输入文章详情',
        );
        foreach($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        if(empty($item)){ //插入数据
            $data['uniacid'] = $_W['uniacid'];
            $data['createtime'] = TIMESTAMP;
            $status = pdo_insert('sj_news_list',$data);
            $tip = "添加";
        }else{
            //更新数据
            $data['updatetime'] = TIMESTAMP;
            $status = pdo_update('sj_news_list',$data,array(
                'uniacid' => $_W['uniacid'],
                'id' => $id
            ));
            $tip = "修改";
        }
        if(!$status){
            message("{$tip}失败",'','error');
        }
        message("{$tip}成功",referer(),'success');
    }
}elseif($do == 'curl'){
    load()->classs('curl.article');
    $url = trim($_GPC['url']);
    if(empty($url) || !is_string($url)){
        message('请输入正确的文章链接地址','','error');
    }
    $curl = new CurlArticle($url);
    $content = $curl -> getContent();
    if(empty($content)){
        message('文章抓取失败','','error');
    }
    message($content,'','success');
}
template('account/display');
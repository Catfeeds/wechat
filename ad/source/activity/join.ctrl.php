<?php
load()->func('check');
$do = empty($_GPC['do'])?'display':trim($_GPC['do']);
if($do == 'display'){
    if($_W['isajax']){
        if(empty($_GPC['ids'])){
            message('请选择要删除的活动','','error');
        }
        $ids = implode(',',$_GPC['ids']);
        $delete_status = pdo_query("DELETE FROM ".tablename('sj_news_activity_join')." WHERE uniacid='{$_W['uniacid']}' AND id IN ($ids)");
        if(!$delete_status){
            message('删除失败！','','error');
        }
        message('删除成功！',referer(),'success');
    }
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $keyword = trim($_GPC['keyword']);
    $where = "a.uniacid='{$_W['uniacid']}'";
    if(!empty($keyword)){
        $where .= " AND b.title LIKE '%{$keyword}%'";
    }
    if(!empty($_GPC['area']['province'])){
        $where .= " AND b.province='{$_GPC['area']['province']}'";
    }
    if(!empty($_GPC['area']['city'])){
        $where .= " AND b.city='{$_GPC['area']['city']}'";
    }
    if(!empty($_GPC['area']['district'])){
        $where .= " AND b.district='{$_GPC['area']['district']}'";
    }
    if($_W['ad_type'] != 1){
        $where .= " AND a.province='{$_W['province']}' AND a.city='{$_W['city']}'";
    }
    if(!empty($_GPC['export'])){
        $list = pdo_fetchall("SELECT a.*,b.title,b.province,b.city FROM ".tablename('sj_news_activity_join')." a LEFT JOIN ".tablename('sj_news_activity')." b ON a.activity_id=b.id WHERE {$where} ORDER BY id DESC");
        if(!check_data($list)){
            message('数据不存在','','error');
        }
        export_list($list,$list[0]['title']);
    }
    $list = pdo_fetchall("SELECT a.*,b.title,b.province,b.city FROM ".tablename('sj_news_activity_join')." a LEFT JOIN ".tablename('sj_news_activity')." b ON a.activity_id=b.id WHERE {$where} ORDER BY id DESC LIMIT {$pindex},{$psize}");
    $pager = pagination(pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename('sj_news_activity_join')." a LEFT JOIN ".tablename('sj_news_activity')." b ON a.activity_id=b.id WHERE {$where}"),$page,$psize);
}

function export_list($list = array(),$title = '测试表格'){
    if(!empty($list) && is_array($list)){
        $index = 1;
        foreach($list as $k => &$v){
            $v['no'] = $index++;
            $v['gender'] = $v['gender'] == 1?'男':'女';
            if(!empty($v['createtime'])){
                $v['createtime'] = date('Y年m月d日 H:i:s',$v['createtime']);
            }
        }
    }else{
        message('导出的数据不存在！','','error');
    }
    load()->classs('export');
    $excel = new ExcelTool();
    $columns =array(
        array(
            "title" => '序号',
            "field" => "no",
            "width" => 10
        ) ,
        array(
            "title" => '姓名',
            "field" => "realname",
            "width" => 10
        ) ,
        array(
            "title" => '手机号',
            "field" => "mobile",
            "width" => 20
        ) ,
        array(
            "title" => '微信号',
            "field" => "wxid",
            "width" => 20
        ) ,
        array(
            "title" => '身高',
            "field" => "height",
            "width" => 10
        ) ,
        array(
            "title" => '年龄',
            "field" => "age",
            "width" => 10
        ) ,
        array(
            "title" => '性别',
            "field" => "gender",
            "width" => 10
        )
    );
    $excel->export(
        $list,//导出的数据数组
        array(
            "title" => $title."-" . date("Y-m-d-H-i", time()) , //标题
            "columns" => $columns //数据列
        )
    );
}
template('activity/join');
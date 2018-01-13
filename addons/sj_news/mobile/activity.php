<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/9/24
 * Time: 13:34
 */
checkauth();
load()->model('mc');
$member = mc_fetch($_W['member']['uid']);

//列表页
if($op == 'display'){
    $page = getApartPageNo('page');
    $psize = 20;
    $pindex = ($page-1)*$psize;
    $list = pdo_fetchall("SELECT * FROM ".tablename('sj_news_activity')." WHERE uniacid='{$_W['uniacid']}' AND city='{$_W['location']['city']}' ORDER BY id DESC LIMIT {$pindex},{$psize}");
    $pager = mobilePagination(pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('sj_news_activity')." WHERE uniacid='{$_W['uniacid']}' AND city='{$_W['location']['city']}'"),$page,$psize);
    include $this->template('activity');
}

//报名的表单信息数组
$joinFields = array(
    'realname' => array(
        'title' => '姓名',
        'value' => '',
        'placeholder' => '请输入姓名',
        'type' => 'text'
    ),
    'gender' => array(
        'title' => '性别',
        'value' => array(
            0 => '保密',
            1 => '男',
            2 => '女'
        ),
        'placeholder' => '请选择性别',
        'type' => 'select'
    ),
    'age' => array(
        'title' => '年龄',
        'value' => '',
        'placeholder' => '请输入年龄',
        'type' => 'text'
    ),
    'height' => array(
        'title' => '身高',
        'value' => '',
        'placeholder' => '请输入身高',
        'type' => 'text'
    ),
    'weight' => array(
        'title' => '体重',
        'value' => '',
        'placeholder' => '请输入体重',
        'type' => 'text'
    ),
    'nation' => array(
        'title' => '民族',
        'value' => '',
        'placeholder' => '请输入民族',
        'type' => 'text'
    ),
    'major' => array(
        'title' => '专业',
        'value' => '',
        'placeholder' => '请输入专业',
        'type' => 'text'
    ),
    'staff' => array(
        'title' => '职业',
        'value' => '',
        'placeholder' => '请输入职业',
        'type' => 'text'
    ),
    'education' => array(
        'title' => '学历',
        'value' => '',
        'placeholder' => '请输入学历',
        'type' => 'text'
    ),
    'idcard' => array(
        'title' => '身份证号',
        'value' => '',
        'placeholder' => '请输入身份证号',
        'type' => 'text'
    ),
    'college' => array(
        'title' => '学校',
        'value' => '',
        'placeholder' => '请输入学校',
        'type' => 'text'
    ),
    'mobile' => array(
        'title' => '手机号',
        'value' => '',
        'placeholder' => '请输入手机号',
        'type' => 'text'
    ),
    'qq' => array(
        'title' => 'QQ号',
        'value' => '',
        'placeholder' => '请输入QQ号',
        'type' => 'text'
    ),
    'wxid' => array(
        'title' => '微信号',
        'value' => '',
        'placeholder' => '请输入微信号',
        'type' => 'text'
    ),
    'email' => array(
        'title' => '邮箱',
        'value' => '',
        'placeholder' => '请输入邮箱',
        'type' => 'text'
    ),
    'area' => array(
        'title' => '常住地址',
        'value' => '',
        'placeholder' => '请输入常住地址',
        'type' => 'area'
    )
);

//详情页
if($op == 'detail'){
    $id = floor(trim($_GPC['id']));
    load()->app('tpl');
    $item = array();
    if(check_id($id)){
        $item = pdo_get('sj_news_activity',array(
            'uniacid' => $_W['uniacid'],
            'id' => $id
        ));
        if(check_data($item)){
            pdo_query("UPDATE ".tablename('sj_news_activity')." SET look_num=look_num+1 WHERE uniacid='{$_W['uniacid']}' AND id='{$id}'");
        }
    }
    if(empty($item)){
        message('活动信息不存在','','error');
    }
    $item['content'] = htmlspecialchars_decode($item['content']);
    if(!empty($item['join_fields'])){
        $item['join_fields'] = iunserializer($item['join_fields']);
    }
	
	if(!empty($item['content'])){
		$item['content'] = htmlspecialchars_decode($item['content']);
	}


    if($_W['isajax']){
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'activity_id' => $id,
            'gender' => floor(trim($_GPC['gender'])),
            'age' => trim($_GPC['age']),
            'realname' => trim($_GPC['realname']),
            'height' => trim($_GPC['height']),
            'weight' => trim($_GPC['weight']),
            'nation' => trim($_GPC['nation']),
            'major' => trim($_GPC['major']),
            'staff' => trim($_GPC['staff']),
            'education' => trim($_GPC['education']),
            'idcard' => trim($_GPC['idcard']),
            'college' => trim($_GPC['college']),
            'mobile' => trim($_GPC['mobile']),
            'qq' => trim($_GPC['qq']),
            'wxid' => trim($_GPC['wxid']),
            'email' => trim($_GPC['email']),
            'province' => trim($_GPC['area']['province']),
            'city' => trim($_GPC['area']['city']),
            'district' => trim($_GPC['area']['district']),
            'ip' => CLIENT_IP,
            'createtime' => TIMESTAMP
        );
        if(!empty($item['join_fields']) && is_array($item['join_fields'])){
            foreach($item['join_fields'] as $k => $f_key){
                if(empty($data[$f_key]) && $f_key != 'gender'){
                    message($joinFields[$f_key]['placeholder'],'','error');
                }
                if($f_key == 'mobile' && !check_mobile($data['mobile'])){
                    message('手机号格式错误','','error');
                }
            }
        }
        $status = pdo_insert('sj_news_activity_join',$data);
        if(!$status){
            message("提交失败",'','error');
        }
        pdo_query("UPDATE ".tablename('sj_news_activity')." SET join_num=join_num+1 WHERE uniacid='{$_W['uniacid']}' AND id='{$id}'");
        message("提交成功",referer(),'success');
    }

    $link = urlencode("{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&op=detail&id={$id}&do=activity&m=sj_news");
    $img = url('mc/poster/image')."&ps={$link}";
    include $this->template('activity_detail');
}

//发布页
if($op == 'push'){
    if($_W['isajax']){
        $data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $_W['member']['uid'],
            'title' => trim($_GPC['title']),
            'content' => $_GPC['content'],
            'from_user' => trim($_GPC['from_user']),
            'starttime' => strtotime(trim($_GPC['starttime'])),
            'endtime' => strtotime(trim($_GPC['endtime'])),
            'province' => trim($_GPC['area']['province']),
            'city' => trim($_GPC['area']['city']),
            'district' => trim($_GPC['area']['district']),
            'address' => $_GPC['address'],
            'tel' => trim($_GPC['tel']),
            'wxid' => trim($_GPC['wxid']),
            'join_fields' => !empty($_GPC['join_fields'])?iserializer($_GPC['join_fields']):'',
            'createtime' => TIMESTAMP
        );
        $error = array(
            'title' => '请输入活动标题',
            'content' => '请输入活动内容',
            'from_user' => '请输入主办方',
            'starttime' => '请选择开始时间',
            'endtime' => '请选择结束时间',
            'province' => '请选择报名省份',
            'city' => '请选择报名城市',
            'district' => '请选择报名区县',
            'address' => '请输入报名详细地址',
            'tel' => '请输入联系电话',
            'wxid' => '请输入微信号',
            'join_fields' => '请选择报名必填字段'
        );
        foreach ($error as $k => $message){
            if(empty($data[$k])){
                message($message,'','error');
            }
        }
        if($data['starttime'] >= $data['endtime']){
            message('开始时间不能小于结束时间','','error');
        }
        if($data['endtime'] <= TIMESTAMP){
            message('结束时间不能小于当前时间','','error');
        }
        $status = pdo_insert('sj_news_activity',$data);
        if(!$status){
            message('提交失败','','error');
        }
        message('提交成功',referer(),'success');
    }
    load()->app('tpl');
    include $this->template('activity_push');
}
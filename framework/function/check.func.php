<?php
/* 正则验证函数,符合规则返回true */

/**
 * @param int $id
 * @return bool
 * 检查是否符合ID规范
 */
function check_id($id = 0){
    if(preg_match("/^[1-9][0-9]*$/",$id)){
        return true;
    }
    return false;
}

/**
 * @param $mobile
 * @return bool
 * 检测是否符合手机号
 */
function check_mobile($mobile){
    if(preg_match("/^1[34578]{1}\d{9}$/",$mobile)){
        return true;
    }
    return false;
}

/**
 * @param $number
 * @param int $n
 * @return bool
 * 检测是否有N位数字组成
 */
function check_n_number($number,$n = 0){
    if(preg_match("/^\d{".$n."}$/",$number)){
        return true;
    }
    return false;
}


/**
 * @param int $relation
 * @return bool
 * 检查关系树是否符合格式
 */
function check_relation($relation = 0){
    if(preg_match('/^(\d+)(-(\d)+)*$/u',$relation)){
        return true;
    }
    return false;
}

/**
 * @param int $email
 * @return bool
 * 检查邮箱
 */
function check_email($email = 0){
    if(preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $email)){
        return true;
    }
    return false;
}


/**
 * @param $data
 * @return bool
 * 检查是否符合数组，且不为空
 */
function check_data($data){

    if(!empty($data) && is_array($data)){
        return true;
    }

    return false;
}

/**
 * @param $data
 * @return bool
 * 检查信息是否存在，
 * 检查是否
 */
function check_info_exist($data){
    if(!empty($data) && is_array($data)){
        return true;
    }
    return false;
}

/**
 * @param $name
 * @return bool
 * 检测是不是中文姓名
 */
function check_chinese_name($name){
    if (preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,4}$/',$name)) {
        return true;
    }
    return false;
}


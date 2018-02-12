<?php
/* 正则验证函数,符合规则返回true */

/**
 * @param int $id
 * @return bool
 * 检查是否符合ID规范
 */
function check_id($id = 0){
  return preg_match("/^[1-9][0-9]*$/",$id);
}

/**
 * @param $mobile
 * @return bool
 * 检测是否符合手机号
 */
function check_mobile($mobile){
    return preg_match("/^1[34578]{1}\d{9}$/",$mobile);
}

/**
 * @param $number
 * @param int $n
 * @return bool
 * 检测是否有N位数字组成
 */
function check_n_number($number,$n = 0){
   return preg_match("/^\d{".$n."}$/",$number);
}


/**
 * @param int $relation
 * @return bool
 * 检查关系树是否符合格式
 */
function check_relation($relation = 0){
    return preg_match('/^(\d+)(-(\d)+)*$/u',$relation);
}

/**
 * @param int $email
 * @return bool
 * 检查邮箱
 */
function check_email($email = 0){
    return preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $email);
}


/**
 * @param $data
 * @return bool
 * 检查是否符合数组，且不为空
 */
function check_data($data){
    return !empty($data) && is_array($data);
}

/**
 * @param $data
 * @return bool
 * 检查信息是否存在，
 * 检查是否
 */
function check_info_exist($data){
    return !empty($data) && is_array($data);
}

/**
 * @param $name
 * @return bool
 * 检测是不是中文姓名
 */
function check_chinese_name($name){
    return preg_match('/^([\xe4-\xe9][\x80-\xbf]{2}){2,4}$/',$name);
}


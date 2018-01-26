<?php

//检查是不是数组
function check_arr($arr){
    if(!empty($arr) && is_array($arr)){
        return true;
    }
    return false;
}
//格式化输出，不退出
function echo_message($message){
    if(check_data($message)){
       echo implode($message.'<br>');
    }else{
        echo $message.'<br>';
    }
}

//格式化输出，退出
function exit_message($message){
    if(check_data($message)){
        exit(implode($message.'<br>'));
    }else{
        exit($message.'<br>');
    }
}
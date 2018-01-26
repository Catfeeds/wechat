<?php

//首页显示
if($op == 'display'){
    $token = JWT::encode(array(
        'uid' => '1',
        'ip' => CLIENT_IP,
        'time' => TIMESTAMP
    ), JWT_AUTH_KEY);
    print_r((array)check_login($token));
}

//登录接口
if($op == 'login'){

}




include $this->template('index');
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
include $this->template('profile');
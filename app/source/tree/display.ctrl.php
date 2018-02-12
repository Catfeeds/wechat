<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/6/1
 * Time: 18:34
 */
defined('IN_IA') or exit('Access Denied');
if($do == 'display'){
    message('功能正在开发中，敬请期待....','','error');
}
template('tree/display');
<?php
load()->model('store');
$do = !empty($_GPC['do'])?trim($_GPC['do']):'display';
template('talk/display');
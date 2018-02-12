<?php
defined('IN_IA') or exit('Access Denied');
isetcookie('__agent_session', false, -100);
header("location:{$_W['siteroot']}agent/index.php?i={$_W['uniacid']}&c=user&a=login");

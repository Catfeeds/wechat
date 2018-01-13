<?php
/**
 * 
 * 
 */
define('IN_SYS', true);
require '../framework/bootstrap.inc.php';
require IA_ROOT . '/agent/common/bootstrap.sys.inc.php';
load()->agent('common');
load()->agent('template');
load()->model('agent');
if (empty($_W['isfounder']) && !empty($_W['user']) && $_W['user']['status'] == 1) {
	message('您的账号正在审核或是已经被系统禁止，请联系网站管理员解决！');
}

/* 代理提示 */
$_W['is_big_city'] = false;
$_W['tips'] = "您好，{$_W['admin']}。您当前所代理的区域是：";
if(in_array($_W['city'],AgentModel::$bigCity)){
	$_W['is_big_city'] = true;
	$_W['tips'] .= $_W['city'].'<span style="color: chartreuse">'.$_W['district'].'</span>';
}else{
	$_W['tips'] .= '<span style="color: chartreuse">'.$_W['city'].'</span>';
}

$acl = array(
	'user' => array(
		'default' => 'display',
		'direct' => array(
			'login',
			'logout'
		)
	),
	'utility' => array(
		'direct' => array(
			'code'
		)
	),
	'account' => array(
		'direct' => array(
			'display'
		)
	),
	'shoper' => array(
		'direct' => array(
			'display'
		)
	)
);
if($_GPC['a'] != 'login' && $_GPC['c'] != 'utility'){
	checklogin(floor(trim($_GPC['i'])));
}
$controllers = array();
$handle = opendir(IA_ROOT . '/agent/source/');
if(!empty($handle)) {
	while($dir = readdir($handle)) {
		if($dir != '.' && $dir != '..') {
			$controllers[] = $dir;
		}
	}
}
if(!in_array($controller, $controllers)) {
	$controller = 'account';
}
$init = IA_ROOT . "/agent/source/{$controller}/__init.php";
if(is_file($init)) {
	require $init;
}

$actions = array();
$handle = opendir(IA_ROOT . '/agent/source/' . $controller);
if(!empty($handle)) {
	while($dir = readdir($handle)) {
		if($dir != '.' && $dir != '..' && strexists($dir, '.ctrl.php')) {
			$dir = str_replace('.ctrl.php', '', $dir);
			$actions[] = $dir;
		}
	}
}
if(empty($actions)) {
	header('location: ?refresh');
}
if(!in_array($action, $actions)) {
	$action = $acl[$controller]['default'];
}
if(!in_array($action, $actions)) {
	$action = $actions[0];
}

$_W['page'] = array();
$_W['page']['copyright'] = $_W['setting']['copyright'];

if(is_array($acl[$controller]['direct']) && in_array($action, $acl[$controller]['direct'])) {
		require _forward($controller, $action);
	exit;
}
require _forward($controller, $action);

define('ENDTIME', microtime());
if (empty($_W['config']['setting']['maxtimeurl'])) {
	$_W['config']['setting']['maxtimeurl'] = 10;
}
if ((ENDTIME - STARTTIME) > $_W['config']['setting']['maxtimeurl']) {
	$data = array(
		'type' => '1',
		'runtime' => ENDTIME - STARTTIME,
		'runurl' => $_W['sitescheme'].$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
		'createtime' => TIMESTAMP
	);
	pdo_insert('core_performance', $data);
}

function _forward($c, $a) {
	$file = IA_ROOT . '/agent/source/' . $c . '/' . $a . '.ctrl.php';
	return $file;
}

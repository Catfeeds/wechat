<?php
/**
 * 慕马商城模块微站定义
 *
 * @author 慕马科技
 * @url http://wx.51muma.com/
 */
defined('IN_IA') or exit('Access Denied');
define('ASSETS_URL',"{$_W['siteroot']}assets");
include_once 'model.php';
if(isset($_SESSION)){
	session_start();
}
class OtoModuleSite extends WeModuleSite {

	/**
	 * @param $func
	 * @param $args
	 * @return bool
	 * 统一控制器入口
	 */
	function __call($func,$args){
		$func_start = str_replace('do','',$func);
		if(preg_match("/^Mobile/",$func_start)){
			$this->_mobile($func);
		}
		if(preg_match("/^Web/",$func_start)){
			$this->_web($func);
		}
	}


	/**
	 * @param $function
	 * 引入web控制器
	 */
	private function _web($function){
		$web_controller_name = substr(strtolower($function),5);
		$file = str_replace('/',DIRECTORY_SEPARATOR,__DIR__."/web/{$web_controller_name}.php");
		if(!file_exists($file)){
			message("文件：{$file}不存在！",'','error');
		}
		global $_W,$_GPC;
		$op = trim($_GPC['op'])?trim($_GPC['op']):'display';
		include_once $file;
	}

	/**
	 * @param $function
	 * 引入手机端控制器
	 */
	private function _mobile($function){
		global $_GPC,$_W;
		if(!empty($_SESSION['__location'])){
			$_W['location'] = json_decode($_SESSION['__location'],true);
		}else{
			load()->classs('point');
			$_W['location']  = (new Point())->getBaiduLocationByIP(CLIENT_IP);
		}
		$mobile_controller_name = substr(strtolower($function),8);
		$file = str_replace('/',DIRECTORY_SEPARATOR,__DIR__."/mobile/{$mobile_controller_name}.php");
		if(!file_exists($file)){
			message("文件：{$file}不存在！",'','error');
		}
		global $_W,$_GPC;
		$op = trim($_GPC['op'])?trim($_GPC['op']):'display';
		if(in_array($_GPC['do'],array('user','cart','order','address','check','set','pay','chat'))){
			checkauth();
		}
		include_once $file;
	}

}


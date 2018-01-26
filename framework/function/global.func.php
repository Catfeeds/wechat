<?php
/**
 * 
 * 
 */
defined('IN_IA') or exit('Access Denied');


/**
 * @param $version1
 * @param $version2
 * @return mixed
 * 判断 $arg1 是否大于 $arg2，如果大于返回 1， 否则返回 -1。
 */
function ver_compare($version1, $version2) {
	$version1 = str_replace('.', '', $version1);
	$version2 = str_replace('.', '', $version2);
	$oldLength = istrlen($version1);
	$newLength = istrlen($version2);
	if(is_numeric($version1) && is_numeric($version2)) {
		if ($oldLength > $newLength) {
			$version2 .= str_repeat('0', $oldLength - $newLength);
		}
		if ($newLength > $oldLength) {
			$version1 .= str_repeat('0', $newLength - $oldLength);
		}
		$version1 = intval($version1);
		$version2 = intval($version2);
	}
	return version_compare($version1, $version2);
}


/**
 * @param $var
 * @return array|string
 * 将字符串或数组中的 \' \“ \ \ 转换为 ' ” \ 并返回。
 */
function istripslashes($var) {
	if (is_array($var)) {
		foreach ($var as $key => $value) {
			$var[stripslashes($key)] = istripslashes($value);
		}
	} else {
		$var = stripslashes($var);
	}
	return $var;
}


/**
 * @param $var
 * @return array|mixed
 * 将字符串或数组中的HTML代码进行转义并返回。
 */
function ihtmlspecialchars($var) {
	if (is_array($var)) {
		foreach ($var as $key => $value) {
			$var[htmlspecialchars($key)] = ihtmlspecialchars($value);
		}
	} else {
		$var = str_replace('&amp;', '&', htmlspecialchars($var, ENT_QUOTES));
	}
	return $var;
}


/**
 * @param $key
 * @param $value
 * @param int $expire
 * @param bool|false $httponly
 * @return bool
 * isetcookie() 会根据参数写入对应的cookie值(cookie的名称会进行加密)，并且返回一个是否写入成功的布尔值。
 */
function isetcookie($key, $value, $expire = 0, $httponly = false) {
	global $_W;
	$expire = $expire != 0 ? (TIMESTAMP + $expire) : 0;
	$secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
	return setcookie($_W['config']['cookie']['pre'] . $key, $value, $expire, $_W['config']['cookie']['path'], $_W['config']['cookie']['domain'], $secure, $httponly);
}


/**
 * @return mixed
 * 获取客户端IP地址并返回，如果获取失败返回 unknow。
 */
function getip() {
	static $ip = '';
	$ip = $_SERVER['REMOTE_ADDR'];
	if(isset($_SERVER['HTTP_CDN_SRC_IP'])) {
		$ip = $_SERVER['HTTP_CDN_SRC_IP'];
	} elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
		foreach ($matches[0] AS $xip) {
			if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
				$ip = $xip;
				break;
			}
		}
	}
	return $ip;
}


/**
 * @param string $specialadd
 * @return string
 * 根据配置文件中的信息生成加密Token字串并返回,如果参数 $specialadd 不为空，则将参数附加在配置信息后面。
 */
function token($specialadd = '') {
	global $_W;
	if(!defined('IN_MOBILE')) {
		return substr(md5($_W['config']['setting']['authkey'] . $specialadd), 8, 8);
	} else {
		if(!empty($_SESSION['token'])) {
			$count  = count($_SESSION['token']) - 5;
			asort($_SESSION['token']);
			foreach($_SESSION['token'] as $k => $v) {
				if(TIMESTAMP - $v > 300 || $count > 0) {
					unset($_SESSION['token'][$k]);
					$count--;
				}
			}
		}
		$key = substr(random(20), 0, 4);
		$_SESSION['token'] = TIMESTAMP;
		return $key;
	}
}


/**
 * @param $length
 * @param bool|FALSE $numeric
 * @return string
 * 生成指定长度的随机字符串并返回。
 */
function random($length, $numeric = FALSE) {
	$seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
	if ($numeric) {
		$hash = '';
	} else {
		$hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
		$length--;
	}
	$max = strlen($seed) - 1;
	for ($i = 0; $i < $length; $i++) {
		$hash .= $seed{mt_rand(0, $max)};
	}
	return $hash;
}


/**
 * @param string $var
 * @param bool|false $allowget
 * @return bool
 * 检查 POST 表单是否值得信任并返回一个布尔值, 如果参数 $allowget 为 true，则默认通过检查。
 */
function checksubmit($var = 'submit', $allowget = false) {
	global $_W, $_GPC;
	if (empty($_GPC[$var])) {
		return FALSE;
	}
	if(defined('IN_SYS')) {
		if ($allowget || (($_W['ispost'] && !empty($_W['token']) && $_W['token'] == $_GPC['token']) && (empty($_SERVER['HTTP_REFERER']) || preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])))) {
			return TRUE;
		}
	} else {
		if(empty($_W['isajax']) && empty($_SESSION['token'][$_GPC['token']])) {
			message('抱歉，表单已经失效请您重新进入提交数据', referer(), 'error');
		} else {
			unset($_SESSION['token'][$_GPC['token']]);
		}
		return TRUE;
	}
	return FALSE;
}

function checkcaptcha($code) {
	global $_W, $_GPC;
	session_start();
	$codehash = md5(strtolower($code) . $_W['config']['setting']['authkey']);
	if (!empty($_GPC['__code']) && $codehash == $_SESSION['__code']) {
		$return = true;
	} else {
		$return = false;
	}
	$_SESSION['__code'] = '';
	isetcookie('__code', '');
	return $return;
}

/**
 * @param $table
 * @return string
 * 返回包含前缀的数据表名称。
 */
function tablename($table) {
	if(empty($GLOBALS['_W']['config']['db']['master'])) {
		return "`{$GLOBALS['_W']['config']['db']['tablepre']}{$table}`";
	}
	return "`{$GLOBALS['_W']['config']['db']['master']['tablepre']}{$table}`";
}


/**
 * @param $keys
 * @param $src
 * @param bool|FALSE $default
 * @return array
 * 返回筛选并替换完成的数组。
 */
function array_elements($keys, $src, $default = FALSE) {
	$return = array();
	if(!is_array($keys)) {
		$keys = array($keys);
	}
	foreach($keys as $key) {
		if(isset($src)) {
			$return = $src;
		} else {
			$return = $default;
		}
	}
	return $return;
}


/**
 * @param $num
 * @param $downline
 * @param $upline
 * @param bool|true $returnNear
 * @return bool|int
 * 判断给定参数是否位于区间内，并且根据参数 $returnNear 决定返回判断结果还是返回转换后的值。
 */
function range_limit($num, $downline, $upline, $returnNear = true) {
	$num = intval($num);
	$downline = intval($downline);
	$upline = intval($upline);
	if($num < $downline){
		return empty($returnNear) ? false : $downline;
	} elseif ($num > $upline) {
		return empty($returnNear) ? false : $upline;
	} else {
		return empty($returnNear) ? true : $num;
	}
}


/**
 * @param $value
 * @param int $options
 * @return bool|string
 * 将参数进行JSON编码并且转义后返回。
 */
function ijson_encode($value, $options = 0) {
	if (empty($value)) {
		return false;
	}
	if (version_compare(PHP_VERSION, '5.4.0', '<') && $options == JSON_UNESCAPED_UNICODE) {
		$json_str = preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", json_encode($value));
	} else {
		$json_str = json_encode($value, $options);
	}
	return addcslashes($json_str, "\\\'\"");
}


/**
 * @param $value
 * @return string
 * 将参数序列化后返回。
 */
function iserializer($value) {
	return serialize($value);
}


/**
 * @param $value
 * @return mixed|string
 * 将参数进行反序列化后返回。
 */
function iunserializer($value) {
	if (empty($value)) {
		return '';
	}
	if (!is_serialized($value)) {
		return $value;
	}
	$result = unserialize($value);
	if ($result === false) {
		$temp = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $value);
		return unserialize($temp);
	}
	return $result;
}


/**
 * @param $str
 * @return bool
 * 判断参数是否为加密后的字符串，并返回一个布尔值的结果。
 */
function is_base64($str){
	if(!is_string($str)){
		return false;
	}
	return $str == base64_encode(base64_decode($str));
}


/**
 * @param $data
 * @param bool|true $strict
 * @return bool
 * 判断参数是否会序列化后的结果，并返回一个布尔值的结果。
 */
function is_serialized($data, $strict = true) {
	if (!is_string($data)) {
		return false;
	}
	$data = trim($data);
	if ('N;' == $data) {
		return true;
	}
	if (strlen($data) < 4) {
		return false;
	}
	if (':' !== $data[1]) {
		return false;
	}
	if ($strict) {
		$lastc = substr($data, -1);
		if (';' !== $lastc && '}' !== $lastc) {
			return false;
		}
	} else {
		$semicolon = strpos($data, ';');
		$brace = strpos($data, '}');
				if (false === $semicolon && false === $brace)
			return false;
				if (false !== $semicolon && $semicolon < 3)
			return false;
		if (false !== $brace && $brace < 4)
			return false;
	}
	$token = $data[0];
	switch ($token) {
		case 's' :
			if ($strict) {
				if ('"' !== substr($data, -2, 1)) {
					return false;
				}
			} elseif (false === strpos($data, '"')) {
				return false;
			}
				case 'a' :
		case 'O' :
			return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
		case 'b' :
		case 'i' :
		case 'd' :
			$end = $strict ? '$' : '';
			return (bool)preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
	}
	return false;
}


/**
 * @param $segment
 * @param array $params
 * @return string
 * 根据参数生成Web端的URL地址并返回。如果参数 $params 不为空，则将 $params 中的值设置为附加参数。
 */
function wurl($segment, $params = array()) {
	list($controller, $action, $do) = explode('/', $segment);
	$url = './index.php?';
	if (!empty($controller)) {
		$url .= "c={$controller}&";
	}
	if (!empty($action)) {
		$url .= "a={$action}&";
	}
	if (!empty($do)) {
		$url .= "do={$do}&";
	}
	if (!empty($params)) {
		$queryString = http_build_query($params, '', '&');
		$url .= $queryString;
	}
	return $url;
}


/**
 * @param $segment
 * @param array $params
 * @param bool|true $noredirect
 * @param bool|false $addhost
 * @return string
 * 根据参数生成Mobile端的URL地址并返回。如果参数 $params 不为空，则将 $params 中的值设置为附加参数，如果参数 $noredirect 为 false，则会在 URL 末尾追加微信的 URL 后缀。
 */
function murl($segment, $params = array(), $noredirect = true, $addhost = false) {
	global $_W;
	list($controller, $action, $do) = explode('/', $segment);
	if (!empty($addhost)) {
		$url = $_W['siteroot'] . 'app/';
	} else {
		$url = './';
	}
	$str = '';
	if(uni_is_multi_acid()) {
		$str = "&j={$_W['acid']}";
	}
	$url .= "index.php?i={$_W['uniacid']}{$str}&";
	if (!empty($controller)) {
		$url .= "c={$controller}&";
	}
	if (!empty($action)) {
		$url .= "a={$action}&";
	}
	if (!empty($do)) {
		$url .= "do={$do}&";
	}
	if (!empty($params)) {
		$queryString = http_build_query($params, '', '&');
		$url .= $queryString;
		if ($noredirect === false) {
						$url .= '&wxref=mp.weixin.qq.com#wechat_redirect';
		}
	}
	return $url;
}

/**
 * @param $total
 * @param $pageIndex
 * @param $pageSize
 * @return string
 * 手机标签分页
 */
function mobilePagination($total,$pageIndex,$pageSize){
	global $_W,$_GPC;
	$html = "";
	$totalPage = ceil($total / $pageSize);
	if ($totalPage <= 1) {
		return '';
	}
	$pageIndex = min($totalPage, $pageIndex);
	$pdata = array(
		"fristPage" => 1,
		"prePage" => $pageIndex - 1,
		"nextPage" => $pageIndex + 1,
		"lastPage" => $totalPage
	);
	//链接赋值
	$_GET['page'] = $pdata["fristPage"];
	$pLink["fristPage"] = $_W['script_name'] . '?' . http_build_query($_GET);
	$_GET['page'] = $pdata["prePage"];
	$pLink["prePage"] = $_W['script_name'] . '?' . http_build_query($_GET);
	$_GET['page'] = $pdata["nextPage"];
	$pLink["nextPage"] = $_W['script_name'] . '?' . http_build_query($_GET);
	$_GET['page'] = $pdata["lastPage"];
	$pLink["lastPage"] = $_W['script_name'] . '?' . http_build_query($_GET);

	$html .= '<div id="mPage" page="'.$pageIndex.'"><ul class="clr"><li onclick="window.location.href=\''.$pLink["fristPage"].'\'">首页</li>';
	if($pdata['prePage'] >= 1){
		$html .= '<li onclick="window.location.href=\''.$pLink["prePage"].'\'">&lt;</li>';
	}else{
		$html .= '<li>&lt;</li>';
	}
	$html .= '<li><span>';
	$html .= min(max(1,$_GPC['page']),$totalPage).'/'.$totalPage;
	$html .= '</span></li>';
	if($pdata['nextPage'] <= $totalPage){
		$html .= '<li onclick="window.location.href=\''.$pLink["nextPage"].'\'">&gt;</li>';
	}else{
		$html .= '<li>&gt;</li>';
	}
	$html .= '<li onclick="window.location.href=\''.$pLink["lastPage"].'\'">末页</li>';
	$html .= '</ul></div>';
	return $html;
}


/**
 * @param $total
 * @param $pageIndex
 * @param int $pageSize
 * @param string $url
 * @param array $context
 * @param string $callbackfunc
 * @return string
 * 根据参数计算并返回分页导航条的HTML代码。其中参数 $total 表示为总记录条数， $pageIndex 表示当前页码， $pageSize 表示每页显示条数， 可选参数 $url 表示要生成的 url 格式，如果为空系统将自动生成，可选参数 $context 可以设置分页导航条的一些信息。
 */
function pagination($total, $pageIndex, $pageSize = 15, $url = '', $context = array('before' => 5, 'after' => 4, 'ajaxcallback' => ''),$callbackfunc = '') {
	global $_W;
	$pdata = array(
		'tcount' => 0,
		'tpage' => 0,
		'cindex' => 0,
		'findex' => 0,
		'pindex' => 0,
		'nindex' => 0,
		'lindex' => 0,
		'options' => ''
	);
	if ($context['ajaxcallback']) {
		$context['isajax'] = true;
	}

	$pdata['tcount'] = $total;
	$pdata['tpage'] = (empty($pageSize) || $pageSize < 0) ? 1 : ceil($total / $pageSize);
	if ($pdata['tpage'] <= 1) {
		return '';
	}
	$cindex = $pageIndex;
	$cindex = min($cindex, $pdata['tpage']);
	$cindex = max($cindex, 1);
	$pdata['cindex'] = $cindex;
	$pdata['findex'] = 1;
	$pdata['pindex'] = $cindex > 1 ? $cindex - 1 : 1;
	$pdata['nindex'] = $cindex < $pdata['tpage'] ? $cindex + 1 : $pdata['tpage'];
	$pdata['lindex'] = $pdata['tpage'];

	if ($context['isajax']) {
		if (!$url) {
			$url = $_W['script_name'] . '?' . http_build_query($_GET);
		}
		$pdata['faa'] = 'href="javascript:;" page="' . $pdata['findex'] . '" '. ($callbackfunc ? 'onclick="'.$callbackfunc.'(\'' . $_W['script_name'] . $url . '\', \'' . $pdata['findex'] . '\', this);return false;"' : '');
		$pdata['paa'] = 'href="javascript:;" page="' . $pdata['pindex'] . '" '. ($callbackfunc ? 'onclick="'.$callbackfunc.'(\'' . $_W['script_name'] . $url . '\', \'' . $pdata['pindex'] . '\', this);return false;"' : '');
		$pdata['naa'] = 'href="javascript:;" page="' . $pdata['nindex'] . '" '. ($callbackfunc ? 'onclick="'.$callbackfunc.'(\'' . $_W['script_name'] . $url . '\', \'' . $pdata['nindex'] . '\', this);return false;"' : '');
		$pdata['laa'] = 'href="javascript:;" page="' . $pdata['lindex'] . '" '. ($callbackfunc ? 'onclick="'.$callbackfunc.'(\'' . $_W['script_name'] . $url . '\', \'' . $pdata['lindex'] . '\', this);return false;"' : '');
	} else {
		if ($url) {
			$pdata['faa'] = 'href="?' . str_replace('*', $pdata['findex'], $url) . '"';
			$pdata['paa'] = 'href="?' . str_replace('*', $pdata['pindex'], $url) . '"';
			$pdata['naa'] = 'href="?' . str_replace('*', $pdata['nindex'], $url) . '"';
			$pdata['laa'] = 'href="?' . str_replace('*', $pdata['lindex'], $url) . '"';
		} else {
			$_GET['page'] = $pdata['findex'];
			$pdata['faa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
			$_GET['page'] = $pdata['pindex'];
			$pdata['paa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
			$_GET['page'] = $pdata['nindex'];
			$pdata['naa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
			$_GET['page'] = $pdata['lindex'];
			$pdata['laa'] = 'href="' . $_W['script_name'] . '?' . http_build_query($_GET) . '"';
		}
	}

	$html = '<div><ul class="pagination pagination-centered">';
	if ($pdata['cindex'] > 1) {
		$html .= "<li><a {$pdata['faa']} class=\"pager-nav\">首页</a></li>";
		$html .= "<li><a {$pdata['paa']} class=\"pager-nav\">&laquo;上一页</a></li>";
	}
		if (!$context['before'] && $context['before'] != 0) {
		$context['before'] = 5;
	}
	if (!$context['after'] && $context['after'] != 0) {
		$context['after'] = 4;
	}

	if ($context['after'] != 0 && $context['before'] != 0) {
		$range = array();
		$range['start'] = max(1, $pdata['cindex'] - $context['before']);
		$range['end'] = min($pdata['tpage'], $pdata['cindex'] + $context['after']);
		if ($range['end'] - $range['start'] < $context['before'] + $context['after']) {
			$range['end'] = min($pdata['tpage'], $range['start'] + $context['before'] + $context['after']);
			$range['start'] = max(1, $range['end'] - $context['before'] - $context['after']);
		}
		for ($i = $range['start']; $i <= $range['end']; $i++) {
			if ($context['isajax']) {
				$aa = 'href="javascript:;" page="' . $i . '" '. ($callbackfunc ? 'onclick="'.$callbackfunc.'(\'' . $_W['script_name'] . $url . '\', \'' . $i . '\', this);return false;"' : '');
			} else {
				if ($url) {
					$aa = 'href="?' . str_replace('*', $i, $url) . '"';
				} else {
					$_GET['page'] = $i;
					$aa = 'href="?' . http_build_query($_GET) . '"';
				}
			}
			$html .= ($i == $pdata['cindex'] ? '<li class="active"><a href="javascript:;">' . $i . '</a></li>' : "<li><a {$aa}>" . $i . '</a></li>');
		}
	}

	if ($pdata['cindex'] < $pdata['tpage']) {
		$html .= "<li><a {$pdata['naa']} class=\"pager-nav\">下一页&raquo;</a></li>";
		$html .= "<li><a {$pdata['laa']} class=\"pager-nav\">尾页</a></li>";
	}
	$html .= '</ul></div>';
	return $html;
}


/**
 * @param $src
 * @param bool|false $local_path
 * @return string
 * 将参数转换为HTTP绝对路径并返回。
 */
function tomedia($src, $local_path = false){
	global $_W;
	if (empty($src)) {
		return '';
	}
	if (strexists($src, 'addons/')) {
		return $_W['siteroot'] . substr($src, strpos($src, 'addons/'));
	}
		if (strexists($src, $_W['siteroot']) && !strexists($src, '/addons/')) {
		$urls = parse_url($src);
		$src = $t = substr($urls['path'], strpos($urls['path'], 'images'));
	}
	$t = strtolower($src);
	if (strexists($t, 'https://mmbiz.qlogo.cn') || strexists($t, 'http://mmbiz.qpic.cn')) {
		return url('utility/wxcode/image', array('attach' => $src));
	}
	if (strexists($t, 'http://') || strexists($t, 'https://')) {
		return $src;
	}
	if ($local_path || empty($_W['setting']['remote']['type']) || file_exists(IA_ROOT . '/' . $_W['config']['upload']['attachdir'] . '/' . $src)) {
		$src = $_W['siteroot'] . $_W['config']['upload']['attachdir'] . '/' . $src;
	} else {
		$src = $_W['attachurl_remote'] . $src;
	}
	return $src;
}


/**
 * @param $errno
 * @param string $message
 * @return array
 * 通过参数构造并返回相应的错误数组，如果参数 $errno 为0，则表示没有任何错误。
 */
function error($errno, $message = '') {
	return array(
		'errno' => $errno,
		'message' => $message,
	);
}


/**
 * @param $data
 * @return bool
 * 检测给定的参数是否产生错误，并返回一个布尔值结果。
 */
function is_error($data) {
	if (empty($data) || !is_array($data) || !array_key_exists('errno', $data) || (array_key_exists('errno', $data) && $data['errno'] == 0)) {
		return false;
	} else {
		return true;
	}
}


/**
 * @param string $default
 * @return string
 * 获取到当前页面的引用页地址，并将其注入到 $_W['referer'] 全局变量中，然后返回。
 */
function referer($default = '') {
	global $_GPC, $_W;
	$_W['referer'] = !empty($_GPC['referer']) ? $_GPC['referer'] : $_SERVER['HTTP_REFERER'];;
	$_W['referer'] = substr($_W['referer'], -1) == '?' ? substr($_W['referer'], 0, -1) : $_W['referer'];

	if (strpos($_W['referer'], 'member.php?act=login')) {
		$_W['referer'] = $default;
	}
	$_W['referer'] = str_replace('&amp;', '&', $_W['referer']);
	$reurl = parse_url($_W['referer']);

	if (!empty($reurl['host']) && !in_array($reurl['host'], array($_SERVER['HTTP_HOST'], 'www.' . $_SERVER['HTTP_HOST'])) && !in_array($_SERVER['HTTP_HOST'], array($reurl['host'], 'www.' . $reurl['host']))) {
		$_W['referer'] = $_W['siteroot'];
	} elseif (empty($reurl['host'])) {
		$_W['referer'] = $_W['siteroot'] . './' . $_W['referer'];
	}
	return strip_tags($_W['referer']);
}


/**
 * @param $string
 * @param $find
 * @return bool
 * 判断字符串 $string 中是否包含 $find，如果包含返回 true ，否则返回 false。
 */
function strexists($string, $find) {
	return !(strpos($string, $find) === FALSE);
}


/**
 * @param $string
 * @param $length
 * @param bool|false $havedot
 * @param string $charset
 * @return mixed|string
 * 从字符串左侧开始进行截取，参数 $string 表示要进行截取的字符串，参数 $length 表示要截取的长度， 可选参数 $havedot 为 true, 超过指定长度的字符串将用 '…' 显示。
 */
function cutstr($string, $length, $havedot = false, $charset = '') {
	global $_W;
	if (empty($charset)) {
		$charset = $_W['charset'];
	}
	if (strtolower($charset) == 'gbk') {
		$charset = 'gbk';
	} else {
		$charset = 'utf8';
	}
	if (istrlen($string, $charset) <= $length) {
		return $string;
	}
	if (function_exists('mb_strcut')) {
		$string = mb_substr($string, 0, $length, $charset);
	} else {
		$pre = '{%';
		$end = '%}';
		$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end), $string);
		$strlen = strlen($string);
		$n = $tn = $noc = 0;
		if ($charset == 'utf8') {
			while ($n < $strlen) {
				$t = ord($string[$n]);
				if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$tn = 1;
					$n++;
					$noc++;
				} elseif (194 <= $t && $t <= 223) {
					$tn = 2;
					$n += 2;
					$noc++;
				} elseif (224 <= $t && $t <= 239) {
					$tn = 3;
					$n += 3;
					$noc++;
				} elseif (240 <= $t && $t <= 247) {
					$tn = 4;
					$n += 4;
					$noc++;
				} elseif (248 <= $t && $t <= 251) {
					$tn = 5;
					$n += 5;
					$noc++;
				} elseif ($t == 252 || $t == 253) {
					$tn = 6;
					$n += 6;
					$noc++;
				} else {
					$n++;
				}
				if ($noc >= $length) {
					break;
				}
			}
			if ($noc > $length) {
				$n -= $tn;
			}
			$strcut = substr($string, 0, $n);
		} else {
			while ($n < $strlen) {
				$t = ord($string[$n]);
				if ($t > 127) {
					$tn = 2;
					$n += 2;
					$noc++;
				} else {
					$tn = 1;
					$n++;
					$noc++;
				}
				if ($noc >= $length) {
					break;
				}
			}
			if ($noc > $length) {
				$n -= $tn;
			}
			$strcut = substr($string, 0, $n);
		}
		$string = str_replace(array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);
	}

	if ($havedot) {
		$string = $string . "...";
	}

	return $string;
}


/**
 * @param $string
 * @param string $charset
 * @return int
 * 获取参数字符串的长度并返回。
 */
function istrlen($string, $charset = '') {
	global $_W;
	if (empty($charset)) {
		$charset = $_W['charset'];
	}
	if (strtolower($charset) == 'gbk') {
		$charset = 'gbk';
	} else {
		$charset = 'utf8';
	}
	if (function_exists('mb_strlen')) {
		return mb_strlen($string, $charset);
	} else {
		$n = $noc = 0;
		$strlen = strlen($string);

		if ($charset == 'utf8') {

			while ($n < $strlen) {
				$t = ord($string[$n]);
				if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$n++;
					$noc++;
				} elseif (194 <= $t && $t <= 223) {
					$n += 2;
					$noc++;
				} elseif (224 <= $t && $t <= 239) {
					$n += 3;
					$noc++;
				} elseif (240 <= $t && $t <= 247) {
					$n += 4;
					$noc++;
				} elseif (248 <= $t && $t <= 251) {
					$n += 5;
					$noc++;
				} elseif ($t == 252 || $t == 253) {
					$n += 6;
					$noc++;
				} else {
					$n++;
				}
			}

		} else {

			while ($n < $strlen) {
				$t = ord($string[$n]);
				if ($t > 127) {
					$n += 2;
					$noc++;
				} else {
					$n++;
					$noc++;
				}
			}

		}

		return $noc;
	}
}

/**
 * @param string $message
 * @param string $size
 * @return mixed|string
 * 获取表情字符串HTML代码并返回，可选参数 $message 表示指定表情的字符，可选参数 $size 表示表情尺寸。
 */
function emotion($message = '', $size = '24px') {
	$emotions = array(
		"/::)","/::~","/::B","/::|","/:8-)","/::<","/::$","/::X","/::Z","/::'(",
		"/::-|","/::@","/::P","/::D","/::O","/::(","/::+","/:--b","/::Q","/::T",
		"/:,@P","/:,@-D","/::d","/:,@o","/::g","/:|-)","/::!","/::L","/::>","/::,@",
		"/:,@f","/::-S","/:?","/:,@x","/:,@@","/::8","/:,@!","/:!!!","/:xx","/:bye",
		"/:wipe","/:dig","/:handclap","/:&-(","/:B-)","/:<@","/:@>","/::-O","/:>-|",
		"/:P-(","/::'|","/:X-)","/::*","/:@x","/:8*","/:pd","/:<W>","/:beer","/:basketb",
		"/:oo","/:coffee","/:eat","/:pig","/:rose","/:fade","/:showlove","/:heart",
		"/:break","/:cake","/:li","/:bome","/:kn","/:footb","/:ladybug","/:shit","/:moon",
		"/:sun","/:gift","/:hug","/:strong","/:weak","/:share","/:v","/:@)","/:jj","/:@@",
		"/:bad","/:lvu","/:no","/:ok","/:love","/:<L>","/:jump","/:shake","/:<O>","/:circle",
		"/:kotow","/:turn","/:skip","/:oY","/:#-0","/:hiphot","/:kiss","/:<&","/:&>"
	);
	foreach ($emotions as $index => $emotion) {
		$message = str_replace($emotion, '<img style="width:'.$size.';vertical-align:middle;" src="http://res.mail.qq.com/zh_CN/images/mo/DEFAULT2/'.$index.'.gif" />', $message);
	}
	return $message;
}

/**
 * @param $str
 * @return string
 * unescape() 函数可对通过 escape() 编码的字符串进行解码
 */
function unescape($str) {
	$ret = '';
	$len = strlen($str);
	for ($i = 0; $i < $len; $i++) {
		if ($str[$i] == '%' && $str[$i + 1] == 'u') {
			$val = hexdec(substr($str, $i + 2, 4));
			if ($val < 127) {
				$ret .= chr($val);
			} else {
				if ($val < 2048) {
					$ret .= chr(192 | $val >> 6) . chr(128 | $val & 63);
				} else {
					$ret .= chr(224 | $val >> 12) . chr(128 | $val >> 6 & 63) . chr(128 | $val & 63);
				}
			}
			$i += 5;
		} else {
			if ($str[$i] == '%') {
				$ret .= urldecode(substr($str, $i, 3));
				$i += 2;
			} else {
				$ret .= $str[$i];
			}
		}
	}
	return $ret;
}


/**
 * @param $string
 * @param string $operation
 * @param string $key
 * @param int $expiry
 * @return string
 * 根据参数 $operation 决定对指定字符 $string 进行加密或者解密操作， 可选参数 $key 表示加密密钥或解密密钥，可选参数 $expiry 表示过期时间。
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	$key = md5($key != '' ? $key : $GLOBALS['_W']['config']['setting']['authkey']);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

	$cryptkey = $keya . md5($keya . $keyc);
	$key_length = strlen($cryptkey);

	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	$rndkey = array();
	for ($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	for ($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for ($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if ($operation == 'DECODE') {
		if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc . str_replace('=', '', base64_encode($result));
	}

}


/**
 * @param $size
 * @return string
 * 将文件大小进行友好格式化然后返回。
 */
function sizecount($size) {
	if($size >= 1073741824) {
		$size = round($size / 1073741824 * 100) / 100 . ' GB';
	} elseif($size >= 1048576) {
		$size = round($size / 1048576 * 100) / 100 . ' MB';
	} elseif($size >= 1024) {
		$size = round($size / 1024 * 100) / 100 . ' KB';
	} else {
		$size = $size . ' Bytes';
	}
	return $size;
}


/**
 * @param $arr
 * @param int $level
 * @return mixed|string
 * 将参数数组转换为XML结构并且返回，可选参数 $level 表示节点的层级，默认为1。
 */
function array2xml($arr, $level = 1) {
	$s = $level == 1 ? "<xml>" : '';
	foreach ($arr as $tagname => $value) {
		if (is_numeric($tagname)) {
			$tagname = $value['TagName'];
			unset($value['TagName']);
		}
		if (!is_array($value)) {
			$s .= "<{$tagname}>" . (!is_numeric($value) ? '<![CDATA[' : '') . $value . (!is_numeric($value) ? ']]>' : '') . "</{$tagname}>";
		} else {
			$s .= "<{$tagname}>" . array2xml($value, $level + 1) . "</{$tagname}>";
		}
	}
	$s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
	return $level == 1 ? $s . "</xml>" : $s;
}

function xml2array($xml) {
	if (empty($xml)) {
		return array();
	}
	$result = array();
	$xmlobj = isimplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
	if($xmlobj instanceof SimpleXMLElement) {
		$result = json_decode(json_encode($xmlobj), true);
		if (is_array($result)) {
			return $result;
		} else {
			return '';
		}
	} else {
		return $result;
	}
}

function scriptname($scriptName = '') {
	global $_W;
	$_W['script_name'] = basename($_SERVER['SCRIPT_FILENAME']);
	if(basename($_SERVER['SCRIPT_NAME']) === $_W['script_name']) {
		$_W['script_name'] = $_SERVER['SCRIPT_NAME'];
	} else {
		if(basename($_SERVER['PHP_SELF']) === $_W['script_name']) {
			$_W['script_name'] = $_SERVER['PHP_SELF'];
		} else {
			if(isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $_W['script_name']) {
				$_W['script_name'] = $_SERVER['ORIG_SCRIPT_NAME'];
			} else {
				if(($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
					$_W['script_name'] = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $_W['script_name'];
				} else {
					if(isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT']) === 0) {
						$_W['script_name'] = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']));
					} else {
						$_W['script_name'] = 'unknown';
					}
				}
			}
		}
	}
	return $_W['script_name'];
}


function utf8_bytes($cp) {
	if ($cp > 0x10000){
				return	chr(0xF0 | (($cp & 0x1C0000) >> 18)).
		chr(0x80 | (($cp & 0x3F000) >> 12)).
		chr(0x80 | (($cp & 0xFC0) >> 6)).
		chr(0x80 | ($cp & 0x3F));
	}else if ($cp > 0x800){
				return	chr(0xE0 | (($cp & 0xF000) >> 12)).
		chr(0x80 | (($cp & 0xFC0) >> 6)).
		chr(0x80 | ($cp & 0x3F));
	}else if ($cp > 0x80){
				return	chr(0xC0 | (($cp & 0x7C0) >> 6)).
		chr(0x80 | ($cp & 0x3F));
	}else{
				return chr($cp);
	}
}

function media2local($media_id, $all = false){
	global $_W;
	if (empty($media_id)) {
		return '';
	}
	$data = pdo_fetch('SELECT * FROM ' . tablename('wechat_attachment') . ' WHERE uniacid = :uniacid AND media_id = :id', array(':uniacid' => $_W['uniacid'], ':id' => $media_id));
	if (!empty($data)) {
		$data['attachment'] = tomedia($data['attachment'], true);
		if (!$all) {
			return $data['attachment'];
		}
		return $data;
	}
	return '';
}

function aes_decode($message, $encodingaeskey = '', $appid = '') {
	$key = base64_decode($encodingaeskey . '=');

	$ciphertext_dec = base64_decode($message);
	$module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
	$iv = substr($key, 0, 16);

	mcrypt_generic_init($module, $key, $iv);
	$decrypted = mdecrypt_generic($module, $ciphertext_dec);
	mcrypt_generic_deinit($module);
	mcrypt_module_close($module);
	$block_size = 32;

	$pad = ord(substr($decrypted, -1));
	if ($pad < 1 || $pad > 32) {
		$pad = 0;
	}
	$result = substr($decrypted, 0, (strlen($decrypted) - $pad));
	if (strlen($result) < 16) {
		return '';
	}
	$content = substr($result, 16, strlen($result));
	$len_list = unpack("N", substr($content, 0, 4));
	$contentlen = $len_list[1];
	$content = substr($content, 4, $contentlen);
	$from_appid = substr($content, $contentlen + 4);
	if (!empty($appid) && $appid != $from_appid) {
		return '';
	}
	return $content;
}

function aes_encode($message, $encodingaeskey = '', $appid = '') {
	$key = base64_decode($encodingaeskey . '=');
	$text = random(16) . pack("N", strlen($message)) . $message . $appid;

	$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	$module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
	$iv = substr($key, 0, 16);

	$block_size = 32;
	$text_length = strlen($text);
		$amount_to_pad = $block_size - ($text_length % $block_size);
	if ($amount_to_pad == 0) {
		$amount_to_pad = $block_size;
	}
		$pad_chr = chr($amount_to_pad);
	$tmp = '';
	for ($index = 0; $index < $amount_to_pad; $index++) {
		$tmp .= $pad_chr;
	}
	$text = $text . $tmp;
	mcrypt_generic_init($module, $key, $iv);
		$encrypted = mcrypt_generic($module, $text);
	mcrypt_generic_deinit($module);
	mcrypt_module_close($module);
		$encrypt_msg = base64_encode($encrypted);
	return $encrypt_msg;
}


function isimplexml_load_string($string, $class_name = 'SimpleXMLElement', $options = 0, $ns = '', $is_prefix = false) {
	libxml_disable_entity_loader(true);
	if (preg_match('/(\<\!DOCTYPE|\<\!ENTITY)/i', $string)) {
		return false;
	}
	return simplexml_load_string($string, $class_name, $options, $ns, $is_prefix);
}

function ihtml_entity_decode($str) {
	$str = str_replace('&nbsp;', '#nbsp;', $str);
	return str_replace('#nbsp;', '&nbsp;', html_entity_decode(urldecode($str)));
}

function iarray_change_key_case($array, $case = CASE_LOWER){
	if (!is_array($array) || empty($array)){
		return array();
	}
	$array = array_change_key_case($array, $case);
	foreach ($array as $key => $value){
		if (empty($value) && is_array($value)) {
			$array = '';
		}
		if (!empty($value) && is_array($value)) {
			$array = iarray_change_key_case($value, $case);
		}
	}
	return $array;
}

function strip_gpc($values, $type = 'g') {
	$filter = array(
		'g' => "'|(and|or)\\b.+?(>|<|=|in|like)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)",
		'p' => "\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)",
		'c' => "\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)",
	);
	if (!isset($values)) {
		return '';
	}
	if(is_array($values)) {
		foreach($values as $key => $val) {
			$values[addslashes($key)] = strip_gpc($val, $type);
		}
	} else {
		if (preg_match("/".$filter[$type]."/is", $values, $match) == 1) {
			$values = '';
		}
	}
	return $values;
}


/**
 * @param $content
 * @param string $order
 * @return string
 * 从HTML文本中提取所有图片
 * $order为数字时，代表第N张
 */
function getImagesFromHtml($content,$order = 'ALL'){
	$pattern="/<img.*?src=[\'|\"](.*?(?:[\.gif|\.jpg]))[\'|\"].*?[\/]?>/";
	preg_match_all($pattern,$content,$match);
	if(isset($match[1])&&!empty($match[1])){
		if($order==='ALL'){
			return $match[1];
		}
		if(is_numeric($order)&&isset($match[1][$order])){
			return $match[1][$order];
		}
	}
	return null;
}

/**
 * @param $list
 * @param int $parent_id
 * @param int $level
 * @param string $html
 * @return array
 * 获取无限极分类标题形式
 * $list 数据列可以不用以分类ID为key
 */
function getCategoryTree(&$list,$parent_id=0,$level =0,$html='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'){
	static $tree = array();
	foreach($list as $k=> $v){
		if($v['parent_id'] == $parent_id){
			if($level > 0){
				$v['title'] = str_repeat($html,$level).$v['title'];
			}
			$tree[] = $v;
			getCategoryTree($list,$v['id'],$level+1);
		}
	}
	return $tree;
}

/**
 * @param $list
 * @return array
 *	获取无限级分类数组形式
 * $list 数据列必须以分类ID为key
 */
function getCategoryTreeArray($list){
	$tree = array();
	if(!empty($list)){
		foreach ($list as $item) {
			if (!empty($list[$item['parent_id']])) {
				$list[$item['parent_id']]['subs'][] = &$list[$item['id']];
			} else {
				$tree[] = &$list[$item['id']];
			}
		}
	}
	return $tree;
}

/**
 * @param array $category
 * @return array
 * 返回父级自己tpl格式的分类数组
 * 可以用lis($parent,$son) = array($parent,$son);
 */
function categoryToParentSon($category = array()){
	$parent = array();//存放父级分类
	$son = array();//存放子分类
	if(!empty($category) && is_array($category)){
		//处理平台商品分类数组
		foreach($category as $k => $v){
			if($v['parent_id'] == 0){
				array_push( $parent,array(
					'name' => $v['title'],
					'id' => $v['id']
				));
			}else{
				if(!isset($son[$v['parent_id']])){
					$son[$v['parent_id']] = array();
				}
				array_push($son[$v['parent_id']],array(
					'name' => $v['title'],
					'id' => $v['id']
				));
			}
		}
	}
	return array($parent,$son);
}

/**
 * @param array $list
 * @return array
 * 获取分类二维数组
 */
function getTwoLevelCategory($list = array()){
	$category = array();
	if(!empty($list) && is_array($list)){
		foreach($list as $k1 => $v1){
			if($v1['parent_id'] == 0){
				$category[$v1['id']] = $v1;
				$category[$v1['id']]['sons'] = array();
			}
		}
		foreach($list as $k2 => $v2){
			if($v2['parent_id'] != 0){
				if(!isset($category[$v2['parent_id']]['sons'])){
					$category[$v2['parent_id']]['sons'] = array();
				}
				if(!isset($category[$v2['parent_id']]['sons'][$v2['id']])){
					$category[$v2['parent_id']]['sons'][$v2['id']] = array();
				}
				$category[$v2['parent_id']]['sons'][$v2['id']] = $v2;
			}
		}
	}
	return $category;
}

/**
 * @param $lat1
 * @param $lng1
 * @param $lat2
 * @param $lng2
 * @return float
 * 根据两个经纬度算距离
 */
function getDistanceByLocations($lat1, $lng1, $lat2, $lng2){
	$earthRadius = 6378137;//单位:m
	$lat1 = ($lat1 * M_PI)/180;
	$lng1 = ($lng1 * M_PI)/180;
	$lat2 = ($lat2 * M_PI)/180;
	$lng2 = ($lng2 * M_PI)/180;
	$calcLongitude = $lng2 - $lng1;
	$calcLatitude = $lat2 - $lat1;
	$stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
	$stepTwo = 2 * asin(min(1, sqrt($stepOne)));
	$calculatedDistance = $earthRadius * $stepTwo;
	return round($calculatedDistance);
}

/**
 * @param $lng
 * @param $lat
 * @param float $distance 单位：km
 * @return array
 * 根据传入的经纬度，和距离范围，返回所有在距离范围内的经纬度的取值范围
 */
function getLocationRange($lng, $lat,$distance = 0.5){
	$earthRadius = 6378.137;//单位km
	$d_lng =  2 * asin(sin($distance / (2 * $earthRadius)) / cos(deg2rad($lat)));
	$d_lng = rad2deg($d_lng);
	$d_lat = $distance/$earthRadius;
	$d_lat = rad2deg($d_lat);
	return array(
		'lat_start' => $lat - $d_lat,//纬度开始
		'lat_end' => $lat + $d_lat,//纬度结束
		'lng_start' => $lng-$d_lng,//纬度开始
		'lng_end' => $lng + $d_lng//纬度结束
	);
}

/**
 * @param $file
 * @return array
 *  上传图片
 * status = 1返回错误信息，0返回上传路径
 */
function apply_upload_image_file($file)
{
	global $_W;
	load()->func('file');
	$dir = "images/{$_W['uniacid']}/" . date('Y/m/');//上传路径
	if (!is_dir(ATTACHMENT_ROOT . $dir)) {
		mkdirs(ATTACHMENT_ROOT . $dir);
	}
	$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
	$ext = strtolower($ext);
	$filename = file_random_name($dir, $ext);
	$result = file_upload($file, 'image', $dir . $filename);
	if (is_error($result)) {
		return array(
			'status' => 1,
			'message' => $result['message'] . $dir
		);
	}
	$info = array(
		'name' => $_FILES['file']['name'],
		'ext' => $ext,
		'filename' => $result['path'],
		'attachment' => $result['path'],
		'url' => tomedia($result['path']),
		'is_image' => 1,
		'filesize' => filesize(ATTACHMENT_ROOT . '/' . $result['path']),
	);
	$size = getimagesize(ATTACHMENT_ROOT . '/' . $result['path']);
	$info['width'] = $size[0];
	$info['height'] = $size[1];
	pdo_insert('core_attachment', array(
		'uniacid' => $_W['uniacid'],
		'uid' => $_W['member']['uid'],
		'filename' => $_FILES['file']['name'],
		'attachment' => $result['path'],
		'type' => 1,
		'createtime' => TIMESTAMP,
	));
	return array(
		'status' => 0,
		'path' => $result['path']
	);
}

/**
 * @param $files
 * @return array
 *  上传多张图片
 * status = 1返回错误信息，0返回上传路径
 */
function apply_upload_multi_image_file($files)
{
	global $_W;
	load()->func('file');
	$dir = "images/{$_W['uniacid']}/" . date('Y/m/');//上传路径
	if (!is_dir(ATTACHMENT_ROOT . $dir)) {
		mkdirs(ATTACHMENT_ROOT . $dir);
	}
	$path = array();
	if(!empty($files['name']) && is_array($files['name'])){
		foreach($files['name'] as $k => $name){
			if(empty($name)){
				continue;
			}
			$file = array(
				'name' => $name,
				'type' => $files['type'][$k],
				'tmp_name' => $files['tmp_name'][$k],
				'error' => $files['error'][$k],
				'size' => $files['size'][$k]
			);
			$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
			$ext = strtolower($ext);
			$filename = file_random_name($dir, $ext);
			$result = file_upload($file, 'image', $dir . $filename);

			if (is_error($result)) {
				return array(
					'status' => 1,
					'message' => $result['message'] . $dir
				);
			}

			$info = array(
				'name' => $_FILES['file']['name'],
				'ext' => $ext,
				'filename' => $result['path'],
				'attachment' => $result['path'],
				'url' => tomedia($result['path']),
				'is_image' => 1,
				'filesize' => filesize(ATTACHMENT_ROOT . '/' . $result['path']),
			);
			$size = getimagesize(ATTACHMENT_ROOT . '/' . $result['path']);
			$info['width'] = $size[0];
			$info['height'] = $size[1];
			pdo_insert('core_attachment', array(
				'uniacid' => $_W['uniacid'],
				'uid' => $_W['member']['uid'],
				'filename' => $_FILES['file']['name'],
				'attachment' => $result['path'],
				'type' => 1,
				'createtime' => TIMESTAMP,
			));
			array_push($path,$result['path']);
		}
	}
	return array(
		'status' => 0,
		'path' => $path
	);
}

/**
 * @param $file
 * @param string $type
 * @return array
 * 上传单文件，并返回路径
 */
function apply_upload_file($file,$type = 'image')
{
	global $_W;
	load()->func('file');
	$dir = "{$type}s/{$_W['uniacid']}/" . date('Y/m/');//上传路径
	if (!is_dir(ATTACHMENT_ROOT . $dir)) {
		mkdirs(ATTACHMENT_ROOT . $dir);
	}
	if(!empty($file['name'])){
		$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
		$ext = strtolower($ext);
		$filename = file_random_name($dir, $ext);
		$result = file_upload($file, $type, $dir . $filename);
		if (is_error($result)) {
			return array(
				'status' => 1,
				'message' => $result['message'] . $dir
			);
		}
		$info = array(
			'name' => $_FILES['file']['name'],
			'ext' => $ext,
			'filename' => $result['path'],
			'attachment' => $result['path'],
			'url' => tomedia($result['path']),
			'is_image' => 0,
			'filesize' => filesize(ATTACHMENT_ROOT . '/' . $result['path']),
		);
		$size = getimagesize(ATTACHMENT_ROOT . '/' . $result['path']);
		$info['width'] = $size[0];
		$info['height'] = $size[1];
		pdo_insert('core_attachment', array(
			'uniacid' => $_W['uniacid'],
			'uid' => $_W['member']['uid'],
			'filename' => $_FILES['file']['name'],
			'attachment' => $result['path'],
			'type' => 3,
			'createtime' => TIMESTAMP,
		));
		return array(
			'status' => 0,
			'path' => $result['path']
		);
	}
	return array(
		'status' => 1,
		'message' => '请选择上传的文件'
	);
}

/**
 * @param array $goods_info
 * @param string $sku_key
 * @return float
 * 获取商品真是出售价格
 */
function getGoodsTruePrice($goods_info = array(),$sku_key = ''){
	$price = $goods_info['sale_price'];
	if($goods_info['is_open_spec'] == OPEN_STATUS && !empty($sku_key)){
		if(isset($goods_info['sku_list'][$sku_key]['sale_price'])){
			$price = floatval(trim($goods_info['sku_list'][$sku_key]['sale_price']));
		}
		if($goods_info['is_open_limit_time_buy'] == OPEN_STATUS){
			if($goods_info['limit_time_buy_start'] <= TIMESTAMP && $goods_info['limit_time_buy_end'] >= TIMESTAMP){
				$price = floatval(trim($goods_info['sku_list'][$sku_key]['limit_time_price']));
			}
		}
	}else{
		if($goods_info['is_open_limit_time_buy'] == OPEN_STATUS){
			if($goods_info['limit_time_buy_start'] <= TIMESTAMP && $goods_info['limit_time_buy_end'] >= TIMESTAMP){
				$price = $goods_info['limit_time_price'];
			}
		}
	}
	return $price;
}

/**
 * @param array $goods_info
 * @param string $sku_key
 * @return float
 * 获取真实的成本价
 */
function getGoodsTrueCostPrice($goods_info = array(),$sku_key = ''){
	$price = $goods_info['cost_price'];
	if($goods_info['is_open_spec'] == OPEN_STATUS && !empty($sku_key)) {
		if (isset($goods_info['sku_list'][$sku_key]['cost_price'])) {
			$price = floatval(trim($goods_info['sku_list'][$sku_key]['cost_price']));
		}
	}
	return $price;
}

/**
 * @param array $goods_info
 * @param string $sku_key
 * @return float
 * 获取真实的市场价
 */
function getGoodsTrueMarketPrice($goods_info = array(),$sku_key = ''){
	$price = $goods_info['cost_price'];
	if($goods_info['is_open_spec'] == OPEN_STATUS && !empty($sku_key)) {
		if (isset($goods_info['sku_list'][$sku_key]['market_price'])) {
			$price = floatval(trim($goods_info['sku_list'][$sku_key]['market_price']));
		}
	}
	return $price;
}


/**
 * @param array $goods_info
 * @param string $sku_key
 * @return float
 * 获取商品真是库存
 */
function getGoodsTrueTotal($goods_info = array(),$sku_key = ''){
	$total = $goods_info['total'];
	if($goods_info['is_open_spec'] == OPEN_STATUS && !empty($sku_key)){
		if(isset($goods_info['sku_list'][$sku_key]['total'])){
			$total = floor(trim($goods_info['sku_list'][$sku_key]['total']));
		}
	}
	return $total;
}

/**
 * @param array $goods_info
 * @param string $sku_key
 * @param int $buy_num
 * @return int
 * 获取邮费
 */
function getGoodsPostPrice($goods_info = array(),$sku_key = '',$buy_num = 0){
	global $_W;
	$fee = 0;
	if($buy_num == 0 || $goods_info['is_free_post'] == POST_FREE_YES){
		return $fee;
	}
	if($goods_info['is_free_post'] == POST_FREE_NO){
		if($goods_info['postage_type'] == POSTAGE_TYPE_TEMPLATE){//运费模板
			$postage_template_info = pdo_get('goods_postage_template',array(
				'uniacid' => $_W['uniacid'],
				'id' => $goods_info['postage_id']
			));
			if(!empty($postage_template_info) && is_array($postage_template_info)){
				if($postage_template_info['calc_type'] == CALC_BY_NUM){ //按个数计费
					//算一次首买个数
					$fee += $postage_template_info['first_num_fee'];
					//多的累加
					if($buy_num > $postage_template_info['first_num'] && $postage_template_info['sequel_num'] > 0){
						$fee += ceil(($buy_num-$postage_template_info['first_num'])/$postage_template_info['sequel_num'])*$postage_template_info['sequel_num_fee'];
					}
				}else{
					//按重量收费
					$unit_weight = $goods_info['weight'];
					if($goods_info['is_open_spec'] == OPEN_STATUS && !empty($sku_key)){
						if(isset($goods_info['sku_list'][$sku_key]['weight'])){
							$unit_weight = floor(trim($goods_info['sku_list'][$sku_key]['weight']));
						}
					}
					//算一次首重
					$fee += $postage_template_info['first_weight_fee'];
					if($buy_num*$unit_weight > $postage_template_info['first_weight'] && $postage_template_info['sequel_weight']>0){
						$fee += ceil(($buy_num*$unit_weight-$postage_template_info['first_weight'])/$postage_template_info['sequel_weight'])*$postage_template_info['sequel_weight_fee'];
					}
				}
			}
		}else{//固定金额
			$fee = $goods_info['postage_money'];
		}
	}
	return $fee;
}

/**
 * @param array $goods_info
 * @param string $old_sku_desc
 * @param string $sku_key
 * @return null|string
 * 获取规格改变信息
 */
function getGoodsSkuChange($goods_info = array(),$old_sku_desc = '',$sku_key = ''){
	if($goods_info['is_open_spec'] == OPEN_STATUS){
		if(!in_array($sku_key,array_keys($goods_info['sku_list']))){
			return '规格不存在';
		}else{
			$new_sku_desc = $goods_info['sku_list'][$sku_key]['filed_1'].'-'.$goods_info['sku_list'][$sku_key]['filed_2'];
			if($old_sku_desc != $new_sku_desc){
				return '规格已改变：'.$new_sku_desc;
			}
		}
	}
	return null;
}

/**
 * @param int $count
 * @param int $length
 * @return string
 * 根据插入订单ID，生成唯一标识订单号
 */
function generateOrderSnByBuyTodayTradeCount($length = ORDER_NO_LENGTH){
	$pay_log_count = pdo_fetchcolumn("SELECT COUNT(1) FROM ".tablename('pay_log')." WHERE createtime BETWEEN ".strtotime(date('Y-m-d').' 00:00:00')." AND ".strtotime(date('Y-m-d').' 23:59:59'));
	return date('Ymd').str_pad($pay_log_count+1,$length - 8,0,STR_PAD_LEFT);
}

/* 转码跳转链接 */
function getCodeJumpUrl($url = '',$type = ENCODE_STATUS){
	if($type == ENCODE_STATUS){
		return urlencode(base64_encode($url));
	}
	return base64_decode(urldecode($url));
}


/**
 * @param $color
 * @return array|bool
 * 颜色值转rgb
 */
function colorHex2Rgb( $color ) {
	if ( $color[0] == '#' ) {
		$color = substr( $color, 1 );
	}
	if ( strlen( $color ) == 6 ) {
		list( $r, $g, $b ) = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
	} elseif ( strlen( $color ) == 3 ) {
		list( $r, $g, $b ) = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
	} else {
		return false;
	}
	$r = hexdec($r);
	$g = hexdec($g);
	$b = hexdec($b);
	return array($r,$g,$b);
}

/**
 * @param $out_trade_no
 * @param $uniacid
 * @return string
 * 支付加密auth
 */
function payAuthEncode($out_trade_no,$uniacid){
	global $_W;
	$auth = authcode("{$out_trade_no}".SPLIT_AUTH_PARAM."{$uniacid}", "ENCODE", $_W['config']['setting']['authkey']);
	return urlencode($auth);
}

/**
 * @param $store_id
 * @param $uniacid
 * @return string
 *  * authcode加密结果每次都不一样,但解析的内容是一样的
 * 所以不能 用 auth != payOfflineAuthEncode去验证，而是去验证内容
 * 线下支付验证
 */
function payOfflineAuthEncode($store_id,$uniacid){
	global $_W;
	$auth = authcode("{$store_id}".SPLIT_AUTH_PARAM."{$uniacid}", "ENCODE", $_W['config']['setting']['authkey']);
	return urlencode($auth);
}

/**
 * @param $store_id
 * @param $uniacid
 * @param $auth
 * @return bool
 * 验证线下付款auth是否符合加密规则
 */
function payOfflineAuthCheck($store_id,$uniacid,$auth){
	if(!empty($store_id) && !empty($uniacid) && !empty($auth)){
		list($old_store_id,$old_uniacid) = explode(SPLIT_AUTH_PARAM,payAuthDecode($auth));
		if(!empty($old_store_id) && !empty($old_uniacid)){
			if($old_store_id == $store_id && $uniacid == $old_uniacid){
				return true;
			}
		}
	}
	return false;
}

/**
 * @param $store_id
 * @param $uniacid
 * @return string
 * 个人收款验证
 *  * authcode加密结果每次都不一样,但解析的内容是一样的
 */
function payPersonAuthEncode($uid,$uniacid){
	global $_W;
	$auth = authcode("{$uid}".SPLIT_AUTH_PARAM."{$uniacid}", "ENCODE", $_W['config']['setting']['authkey']);
	return urlencode($auth);
}

/**
 * @param $pay_uid
 * @param $cashier_uid
 * @param $uniacid
 * @param $auth
 * @return bool
 * 验证个人收款Auth
 */
function payPersonAuthCheck($pay_uid,$cashier_uid,$uniacid,$auth){
	if(!empty($pay_uid) && !empty($uniacid) && !empty($auth) && !empty($cashier_uid)){
		list($old_cashier_uid,$old_uniacid) = explode(SPLIT_AUTH_PARAM,payAuthDecode($auth));
		if(!empty($old_cashier_uid) && !empty($old_uniacid) && $pay_uid != $cashier_uid){
			if($old_cashier_uid == $cashier_uid && $uniacid == $old_uniacid){
				return true;
			}
		}
	}
	return false;
}


/**
 * @param $auth
 * @return string
 * 解密auth,
 * authcode加密结果每次都不一样,但解析的内容是一样的
 */
function payAuthDecode($auth){
	global $_W;
	return authcode($auth, "DECODE", $_W['config']['setting']['authkey']);
}

/**
 * @param $pay_info
 * @return string
 * 返回支付回调地址
 */
function getPayBackUrl($order_type){
	global $_W;
	$url = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&do=index&m=oto";
	switch($order_type){
		case ORDER_TYPE_OTO_GOODS:
			$url = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&do=index&m=oto";
			break;
		case ORDER_TYPE_OFFLINE:
			$url = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&op=order&do=pay&m=oto";
			break;
		case ORDER_TYPE_PERSON:
			$url = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=mc&a=cashier&do=pay_log";
			break;
		case ORDER_TYPE_OLD_FEE:
			$url = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=old&a=display&do=fee&";
			break;
		case ORDER_TYPE_DEVELOP_SHOP:
			$url = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=shop&a=display&do=display";
			break;
		case ORDER_TYPE_SJ_NEWS_AD:
			$url = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&do=m_ad&m=sj_news";
			break;
		case ORDER_TYPE_SJ_NEWS_RENEW:
			$url = "{$_W['siteroot']}app/index.php?i={$_W['uniacid']}&c=entry&do=m_ad&m=sj_news";
			break;
	}
	return $url;
}

/**
 * @param $keyword
 * @return string
 * 根据地名关键字获取导航
 */
function get_gps_keyword($keyword){
	return "https://map.baidu.com/mobile/webapp/search/search/qt=s&wd={$keyword}";
}

/***
 * @param string $key
 * @return array
 * 根据$_GPC['时间']获取结束开始时间戳
 */
function getStartTimeEndTimeByGPC($key = 'createtime'){
	global $_GPC,$_W;
	if($_W['uniacid'] == 7){
		$endtime = empty($_GPC[$key]['end']) ? strtotime(date('Y-m-d 23:59:59')) : strtotime($_GPC[$key]['end']) + 86399;
		$starttime = empty($_GPC[$key]['start']) ? $endtime - 24*3600+1 : strtotime($_GPC[$key]['start']);
	}else{
		$endtime = empty($_GPC[$key]['end']) ? strtotime(date('Y-m-d 23:59:59')) : strtotime($_GPC[$key]['end']) + 86399;
		$starttime = empty($_GPC[$key]['start']) ? $endtime - 30*24*3600 : strtotime($_GPC[$key]['start']);
	}
	return array($starttime,$endtime);
}

/**
 * @param string $key
 * @return mixed
 * 返回分页时的当前page
 */
function getApartPageNo($key = 'page'){
	global $_GPC;
	return max(1,floor(trim($_GPC[$key])));
}

/**
 * @param $uid
 * @param $parent_uid
 * @return bool
 * 修改关系树
 */
function updateRelation($uid,$parent_uid){
	global $_W;
	$relation = pdo_fetchcolumn("SELECT relation FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$uid}'");
	if(!empty($relation)){
		return false;
	}
	if($parent_uid != $uid && !empty($parent_uid)){ //不是本人
		$parent_relation = pdo_fetchcolumn("SELECT relation FROM ".tablename('mc_members')." WHERE uniacid='{$_W['uniacid']}' AND uid='{$parent_uid}'");
		$status = pdo_update('mc_members',array('relation'=>$parent_uid.SPLIT_RELATION.$parent_relation),array('uniacid'=>$_W['uniacid'],'uid'=>$uid));
		return !$status?false:true;
	}
	return false;
}


/**
 * @param int $parent_uid
 * @return null|string
 * 获取关注微信关注二维码地址
 */
function getWechatFocusImage($parent_uid = 0){
	global $_W;
	$account = WeAccount::create($_W['acid']);
	$scene_str = 'friend'.SPLIT_SCENE_STR.$_W['uniacid'].SPLIT_SCENE_STR.(!empty($parent_uid)?$parent_uid:$_W['member']['uid']);
	$result = pdo_get('qrcode',array(
		'uniacid' => $_W['uniacid'],
		'acid' => $_W['acid'],
		'scene_str' => $scene_str,
		'model' => QR_MODEL_LONG_TIME
	));
	if(empty($result) || !is_array($result)){
		$qrcid = pdo_fetchcolumn("SELECT qrcid FROM ".tablename('qrcode')." WHERE acid ='{$_W['acid']}' AND model = ".QR_MODEL_LONG_TIME." ORDER BY qrcid DESC LIMIT 1");
		$barcode['action_info']['scene']['scene_id'] = !empty($qrcid) ? ($qrcid + 1) : 100001;
		$barcode['action_info']['scene']['scene_str'] = $scene_str;
		$barcode['action_name'] = 'QR_LIMIT_STR_SCENE';
		$result = $account->barCodeCreateFixed($barcode);
		if(!is_error($result)) {
			$data = array(
				'uniacid' => $_W['uniacid'],
				'acid' => $_W['acid'],
				'qrcid' => $barcode['action_info']['scene']['scene_id'],
				'scene_str' => $barcode['action_info']['scene']['scene_str'],
				'name' => $barcode['action_name'],
				'model' => QR_MODEL_LONG_TIME,
				'ticket' => $result['ticket'],
				'url' => $result['url'],
				'createtime' => TIMESTAMP,
				'status' => '1',
				'type' => 'scene',
			);
			$insert = pdo_insert('qrcode',$data);
			if(!$insert){
				return null;
			}
		}else{
			return null;
		}
	}
	return "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($result['ticket']);
}

/**
 * @param $mobile
 * @param $code
 * @return bool
 * 检验短信验证码是否正确
 */
function checkMobileCode($mobile,$code){
	global $_W;
	if(!check_mobile($mobile)){
		return error(-1,'手机号格式错误');
	}
	if(empty($code)) {
		return error(-2,'未输入验证码');
	}
	$sql_code = pdo_fetch("SELECT * FROM ".tablename('core_sendsms_log')." WHERE uniacid='{$_W['uniacid']}' AND mobile='{$mobile}' AND type=".SMS_TYPE_CODE." ORDER BY createtime DESC LIMIT 0,1");
	if(!check_data($sql_code)){
		return error(-3,'验证码信息不存在');
	}
	if(TIMESTAMP - $sql_code['createtime'] > 600){
		return error(-4,'验证码已失效，请重新获取');
	}
	if($code != $sql_code['code']){
		return error(-5,'验证码输入不正确');
	}
	return true;
}

/**
 * @param $base64_content
 * @param string $ext
 * @return null|string
 * base64转图片
 */
function base64ToImage($base64_content,$ext = 'png'){
	global $_W;
	if(empty($base64_content)){
	    return null;
    }
	if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_content, $result)){
		$base64_content = str_replace($result[1], '', $base64_content);
		$ext = $result[2];
	}
	load()->func('file');
	$originname = uniqid();
	$folder = "images/{$_W['uniacid']}/".date('Y/m/');
	$filename = file_random_name(ATTACHMENT_ROOT . $folder, $ext);
	$pathname = $folder . $filename;
	$fullname = ATTACHMENT_ROOT . $pathname;
	if(!is_dir(ATTACHMENT_ROOT.$folder)){
		mkdirs(ATTACHMENT_ROOT.$folder);
	}
	if (file_put_contents($fullname, base64_decode($base64_content)) == false) {
		return null;
	}
	$info = array(
		'name' => $originname,
		'ext' => $ext,
		'filename' => $pathname,
		'attachment' => $pathname,
		'url' => tomedia($pathname),
		'is_image' => 1,
		'filesize' => filesize($fullname),
	);
	$size = getimagesize($fullname);
	$info['width'] = $size[0];
	$info['height'] = $size[1];
	pdo_insert('core_attachment', array(
		'uniacid' => $_W['uniacid'],
		'uid' => $_W['uid'],
		'filename' => $originname,
		'attachment' => $pathname,
		'type' => 1,
		'createtime' => TIMESTAMP,
	));
	return $pathname;
}


//友好的时间显示
function friend_date($time){
    if (!$time)
        return false;
    $fdate = '';
    $d = TIMESTAMP - intval($time);
    $ld = $time - mktime(0, 0, 0, 0, 0, date('Y')); //得出年
    $md = $time - mktime(0, 0, 0, date('m'), 0, date('Y')); //得出月
    $byd = $time - mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')); //前天
    $yd = $time - mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')); //昨天
    $dd = $time - mktime(0, 0, 0, date('m'), date('d'), date('Y')); //今天
    $td = $time - mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')); //明天
    $atd = $time - mktime(0, 0, 0, date('m'), date('d') + 2, date('Y')); //后天
    if ($d == 0) {
        $fdate = '刚刚';
    } else {
        switch ($d) {
            case $d < $atd:
                $fdate = date('Y年m月d日', $time);
                break;
            case $d < $td:
                $fdate = '后天' . date('H:i', $time);
                break;
            case $d < 0:
                $fdate = '明天' . date('H:i', $time);
                break;
            case $d < 60:
                $fdate = $d . '秒前';
                break;
            case $d < 3600:
                $fdate = floor($d / 60) . '分钟前';
                break;
            case $d < $dd:
                $fdate = floor($d / 3600) . '小时前';
                break;
            case $d < $yd:
                $fdate = '昨天' . date('H:i', $time);
                break;
            case $d < $byd:
                $fdate = '前天' . date('H:i', $time);
                break;
            case $d < $md:
                $fdate = date('m月d日 H:i', $time);
                break;
            case $d < $ld:
                $fdate = date('m月d日', $time);
                break;
            default:
                $fdate = date('Y年m月d日', $time);
                break;
        }
    }
    return $fdate;
}
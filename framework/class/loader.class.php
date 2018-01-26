<?php
/**
 * 
 * 
 */
defined('IN_IA') or exit('Access Denied');


function load() {
	static $loader;
	if(empty($loader)) {
		$loader = new Loader();
	}
	return $loader;
}


class Loader {
	
	private $cache = array();
	
	function func($name) {
		global $_W;
		if (isset($this->cache['func'][$name])) {
			return true;
		}
		$file = IA_ROOT . '/framework/function/' . $name . '.func.php';
		if (file_exists($file)) {
			include $file;
			$this->cache['func'][$name] = true;
			return true;
		} else {
			trigger_error('Invalid Helper Function /framework/function/' . $name . '.func.php', E_USER_ERROR);
			return false;
		}
	}
	
	function model($name) {
		global $_W;
		if (isset($this->cache['model'][$name])) {
			return true;
		}
		$file = IA_ROOT . '/framework/model/' . $name . '.mod.php';
		if (file_exists($file)) {
			include $file;
			$this->cache['model'][$name] = true;
			return true;
		} else {
			trigger_error('Invalid Model /framework/model/' . $name . '.mod.php', E_USER_ERROR);
			return false;
		}
	}
	
	function classs($name) {
		global $_W;
		if (isset($this->cache['class'][$name])) {
			return true;
		}
		$file = IA_ROOT . '/framework/class/' . $name . '.class.php';
		if (file_exists($file)) {
			include $file;
			$this->cache['class'][$name] = true;
			return true;
		} else {
			trigger_error('Invalid Class /framework/class/' . $name . '.class.php', E_USER_ERROR);
			return false;
		}
	}

	/* 引入web公共函数 */
	function web($name) {
		global $_W;
		if (isset($this->cache['web'][$name])) {
			return true;
		}
		$file = IA_ROOT . '/web/common/' . $name . '.func.php';
		if (file_exists($file)) {
			include $file;
			$this->cache['web'][$name] = true;
			return true;
		} else {
			trigger_error('Invalid Web Helper /web/common/' . $name . '.func.php', E_USER_ERROR);
			return false;
		}
	}

	/* 引入app公共函数 */
	function app($name) {
		global $_W;
		if (isset($this->cache['app'][$name])) {
			return true;
		}
		$file = IA_ROOT . '/app/common/' . $name . '.func.php';
		if (file_exists($file)) {
			include $file;
			$this->cache['app'][$name] = true;
			return true;
		} else {
			trigger_error('Invalid App Function /app/common/' . $name . '.func.php', E_USER_ERROR);
			return false;
		}
	}

	/* 引入store公共函数 */
	function store($name) {
		global $_W;
		if (isset($this->cache['store'][$name])) {
			return true;
		}
		$file = IA_ROOT . '/store/common/' . $name . '.func.php';
		if (file_exists($file)) {
			include $file;
			$this->cache['store'][$name] = true;
			return true;
		} else {
			trigger_error('Invalid Web Helper /store/common/' . $name . '.func.php', E_USER_ERROR);
			return false;
		}
	}

	/* 引入agent公共函数 */
	function agent($name) {
		global $_W;
		if (isset($this->cache['agent'][$name])) {
			return true;
		}
		$file = IA_ROOT . '/agent/common/' . $name . '.func.php';
		if (file_exists($file)) {
			include $file;
			$this->cache['agent'][$name] = true;
			return true;
		} else {
			trigger_error('Invalid Web Helper /agent/common/' . $name . '.func.php', E_USER_ERROR);
			return false;
		}
	}

	/* 引入account公共函数 */
	function account($name) {
		global $_W;
		if (isset($this->cache['account'][$name])) {
			return true;
		}
		$file = IA_ROOT . '/account/common/' . $name . '.func.php';
		if (file_exists($file)) {
			include $file;
			$this->cache['account'][$name] = true;
			return true;
		} else {
			trigger_error('Invalid Web Helper /account/common/' . $name . '.func.php', E_USER_ERROR);
			return false;
		}
	}

	/* 引入ad公共函数 */
	function ad($name) {
		global $_W;
		if (isset($this->cache['ad'][$name])) {
			return true;
		}
		$file = IA_ROOT . '/ad/common/' . $name . '.func.php';
		if (file_exists($file)) {
			include $file;
			$this->cache['ad'][$name] = true;
			return true;
		} else {
			trigger_error('Invalid Web Helper /ad/common/' . $name . '.func.php', E_USER_ERROR);
			return false;
		}
	}

	/* 引入servicer公共函数 */
	function servicer($name) {
		global $_W;
		if (isset($this->cache['servicer'][$name])) {
			return true;
		}
		$file = IA_ROOT . '/servicer/common/' . $name . '.func.php';
		if (file_exists($file)) {
			include $file;
			$this->cache['servicer'][$name] = true;
			return true;
		} else {
			trigger_error('Invalid Web Helper /servicer/common/' . $name . '.func.php', E_USER_ERROR);
			return false;
		}
	}

    /* 引入vapp公共函数 */
    function vapp($name) {
        global $_W;
        if (isset($this->cache['vapp'][$name])) {
            return true;
        }
        $file = IA_ROOT . '/vapp/common/' . $name . '.func.php';
        if (file_exists($file)) {
            include $file;
            $this->cache['vapp'][$name] = true;
            return true;
        } else {
            trigger_error('Invalid Web Helper /vapp/common/' . $name . '.func.php', E_USER_ERROR);
            return false;
        }
    }
	
}

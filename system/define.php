<?php
/*
 * +------------------------------------------------------------------------+
 * | MaskPHP - A PHP Framework For Beginners                                |
 * | @package       : MaskPHP                                               |
 * | @authors       : MaskPHP                                               |
 * | @copyright     : Copyright (c) 2015, MaskPHP                           |
 * | @since         : Version 1.0.0                                         |
 * | @website       : http://maskphp.com                                    |
 * | @e-mail        : support@maskphp.com                                   |
 * | @require       : PHP version >= 5.3.0                                  |
 * +------------------------------------------------------------------------+
 */

/**
 * application time start
 */
define('APP_TIME_START', microtime(true));

/**
 * path seperator
 * ex: windows -> \ | linux -> /
 */
define('DS', DIRECTORY_SEPARATOR);

/**
 * php extension
 */
define('EXT', '.php');

/**
 * application path
 * ex: /public_html/,...
 */
define('APP_PATH', substr(__DIR__, 0, strrpos(__DIR__, DS) + 1));

/**
 * system path
 * ex: /public_html/system/
 */
define('SYSTEM_PATH', APP_PATH . 'system' . DS);

/**
 * library path
 * ex: /public_html/library/
 */
define('LIBRARY_PATH', APP_PATH . 'library' . DS);

/**
 * module path
 * ex: /public_html/module/
 */
define('MODULE_PATH', APP_PATH . 'module' . DS);

/**
 * media path
 * ex: /public_html/media/
 */
define('MEDIA_PATH', APP_PATH . 'media' . DS);

/**
 * theme path
 * ex: /public_html/theme/
 */
define('THEME_PATH', APP_PATH . 'theme' . DS);

/**
 * cache path
 * ex: /public_html/theme/
 */
define('CACHE_PATH', APP_PATH . 'cache' . DS);

/**
 * domain
 * ex: localhost, maskphp.com, demo.maskphp.com,...
 */
define('DOMAIN', isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != $_SERVER['SERVER_NAME'] ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);

/**
 * protocol
 * ex: http, https,...
 */
define('PROTOCOL', isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1) 
	|| isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ? 'https' : 'http');

/**
 * Http referer
 * ex: http://sukienhay.com/
 */
define('HTTP_REFERER', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');

/**
 * port
 * ex: 80, 8080,...
 */
define('PORT', $_SERVER['SERVER_PORT']);
 
/**
 * site path
 * ex: http://localhost/maskphp/ -> /maskphp/
 */
define('SITE_PATH', preg_replace('/index.php$/i', '', $_SERVER['PHP_SELF']));

/**
 * site root
 * ex: http://maskgroup.com, http://localhost/maskphp/,...
 */
define('SITE_ROOT', PROTOCOL . '://' . DOMAIN . (PORT === '80' ? '' : ':' . PORT) . SITE_PATH);

/**
 * server IP & client IP
 */
define('SERVER_IP', isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1');
define('CLIENT_IP', isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] 
	: (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']));

/**
 * CLIENT LANGUAGE
 */
define('CLIENT_LANG', strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)));

/**
 * check is windows server
 */
define('IS_WINDOWS', strncasecmp(PHP_OS, 'WIN', 3) == 0 ? true : false);

/**
 * is local mode
 */
define('IS_LOCAL', CLIENT_IP === SERVER_IP);

/**
 * request method
 */
define('IS_POST', $_SERVER['REQUEST_METHOD'] === 'POST');
define('IS_GET', $_SERVER['REQUEST_METHOD'] === 'GET');

/**
 * is ajax method
 */
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') == 0);
define('IS_AJAX_POST', IS_AJAX && IS_POST);
define('IS_AJAX_GET', IS_AJAX && IS_GET);

/**
 * empty value
 */
define('EMPTY_VALUE', '__M::EMPTY::M__' . APP_TIME_START);

/**
 * check empty value
 * @param  $var
 */
function is_empty($var){
	if($var === EMPTY_VALUE){
		return true;
	}

	return false;
}

/**
 * translate key
 * @param  string $key
 * @param  string | array $args
 */
function __($key, $args = array()){
	$_args = array();
	\M::get('event')->trigger('system.language', null, $_args);

	$args = array_merge((array)$_args, (array)$args);

	if(isset($args[$key])){
		return vsprintf($args[$key], (array)$args);
	}

	return $key;

}

/**
 * flash data
 * @param  string $key
 * @param  $value
 */
function flash_data($key, $value = EMPTY_VALUE, $replace = false){
	$msg = null;
	$msg = \M::get('session')->get($key);

	if(!is_empty($value)){
		if( $replace != true ){
			$msg = (array)$msg;
			$msg[] = $value;
			$value = $msg;
		}
		\M::get('session')->set($key, $value);
	}else{
		\M::get('session')->delete($key);
	}

	return $msg;
}

/**
 * get class name
 * @param  string $class
 */
function get_class_name($class){
	$class = preg_replace("/[^a-zA-Z0-9_\.]/", '\\', $class);
	$class = preg_replace("/[\.\\\]+/", '\\', $class);
	return $class = '\\' . trim(strtolower($class), '\\');
}

/**
 * get array key
 * @param  string $key
 */
function get_array_key($key){
	$key = preg_replace("/[^a-zA-Z0-9\.]/", '.', $key);
	$key = preg_replace("/[\.]+/", '.', $key);
	return $key = trim(strtolower($key), '.');
}

/**
 * trim & convert string to lower case
 * @param  string &$str
 */
function trim_lower(&$str){
	return $str = strtolower(trim(preg_replace('#\s+#', ' ', $str)));
}

/**
 * replace multi & trim slash
 * @param  string &$str
 * @param  string $slash
 */
function trim_slash(&$str, $slash = '/'){
	return $str = trim(preg_replace("/[\/\\\]+/", $slash, $str), $slash);
}

/**
 * get string last
 * @param  string $str
 * @param  string $symbol
 */
function get_string_last($str, $symbol = '/'){
	return substr(strrchr($str, $symbol), 1);
}

/**
 * json parse (decode)
 * @param  string  $str
 * @param  boolean $assoc
 */
function json_parse($str, $assoc = true){
	try{
		return json_decode($str, $assoc);
	}catch(\Exception $e){
		return null;
	}
}

/**
 * filter input
 * set type, trim, escape string,...
 * _GET, _POST, _COOKIE, _FILES, _SERVER, _REQUEST, _SESSION, GLOBALS
 * @param  string $key
 * @param  string $type
 * @param  string $method
 */
function input($key, $type = 'str', $method = null){
	$ret = $key;

	if(!$method){
		$var = $GLOBALS;
	}else{
		$_key = '_' . strtoupper($method);
		if(!isset($GLOBALS[$_key])){
			$GLOBALS[$_key] = array();
		}
		$var = $GLOBALS[$_key];
	}

	$ret = isset($var[$key]) ? $var[$key] : null;

	$extra = '';
	if(is_array($type) && count($type) == 2){
		if(isset($type[1])){
			$extra = $type[1];
		}
		if(isset($type[0])){
			$type = $type[0];
		}
	}

	switch ($type){
		case 'res':
			$ret = settype($ret, 'resource');
			break;

		case 'obj' :
			$ret = (object)$ret;
			break;

		case 'array' :
			if(is_object($ret)){
				$ret = array($ret);
			}else{
				$ret = (array)$ret;
			}
			break;

		case 'double' :
			$ret = settype($ret, 'double');
			break;

		case 'float' :
			$ret = (float)$ret;
			break;

		case 'int' :
			$ret = (int)$ret;
			break;

		case 'bool' :
			$ret = (boolean)$ret;
			break;

		case 'str' :
			return trim((string)$ret);
			break;

		case 'date':
			if($date = date_create_from_format($extra, trim($ret))){
				$ret = date_format($date, $extra);
			}elseif($date = date_create_from_format(str_replace('y', 'Y', $extra), $ret)){
				$ret = date_format($date, $extra);
			}elseif($date = date_create_from_format(str_replace('Y', 'y', $extra), $ret)){
				$ret = date_format($date, $extra);
			}
			$ret = null;
			break;

		case 'email':
			$ret = filter_var(trim($ret), FILTER_VALIDATE_EMAIL);
			break;

		default :
			$ret = null;
			break;
	}

	\M::get('event')->change('system.define.input', $ret, $type, $key, $method);
	return $ret;
}

/**
 * find key in array
 * @param  string $key
 * @param  array $arr
 */
function array_key_exist($key, $arr){
	foreach($arr as $k => $v){
		if(strcasecmp($key, $k) == 0){
			return true;
		}
	}

	return false;
}

/**
 * find string value in array
 * @param  string $key
 * @param  string|array $arr
 */
function array_value_exist($key, $arr){
	if(is_string($arr)){
		$arr = preg_replace('/\s+/', '', $arr);
		$arr = explode(',', $arr);
	}

	foreach($arr as $v){
		if(is_string($v) && strcasecmp($key, $v) == 0){
			return true;
		}
	}

	return false;
}

/**
 * get file in folder
 * @param  string $dir
 */
function get_file($dir){
	return get_sub($dir, false);
}

/**
 * get sub folder
 * @param  string $dir
 */
function get_folder($dir){
	return get_sub($dir, true);
}

/**
 * get sub folder|file
 * @param  string $dir
 * @param  boolean $child: TRUE => folder; FALSE => file; OTHER => folder & file
 */
function get_sub($dir, $child = null){
	static $ret = array();

	if(is_bool($child)){
		$key = $child ? 'folder' : 'file';
	}else{
		$key = 'all';
	}
	$key = trim_lower($dir) . $key;

	if(!isset($ret[$key])){
		$ret[$key] = array();

		if(get_readable($dir)){
			$dir .= '*';

			// return all folder & file
			if($child === '*'){
				$res = glob($dir);
			}
			// return folder
			elseif($child){
				$res = glob($dir, GLOB_ONLYDIR);
			}
			// return file
			else{
				$res = glob($dir . '.*');
			}

			foreach((array)$res as $v){
				$ret[$key][] = get_string_last($v, DS);
			}
		}
	}

	return $ret[$key];
}

/**
 * get resource: file | folder
 * @param  string &$res
 */
function get_resource(&$res){
	return $res = get_readable($res);
}

/**
 * get readable file|path
 * @param  string $path
 * @param  string $root_path
 */
function get_readable($path, $root_path = APP_PATH){
	if(!trim_slash($path, DS)){
		return null;
	}

	// is windows -> just check readable
	if(IS_WINDOWS){
		if(is_readable($root_path . $path)){
			$path = $root_path . $path;
		}elseif(!is_readable($path)){
			return null;
		}

		// is file
		if(is_file($path)){
			return $path;
		}
		// is dir
		return rtrim($path, DS) . DS;
	}

	// remove root path
	$length = strlen(trim_slash($root_path, DS));
	$first 	= substr($path, 0, $length);

	// get path
	if(strcasecmp($first, $root_path) == 0){
		$path = trim(substr($path, $length), DS);
	}

	// set $root_path
	$root_path = DS . $root_path . DS;

	// get last path
	$last_path = get_string_last($path, DS);

	// check all directories
	if($last_path){
		$paths = explode(DS, trim(substr($path, 0, strlen($path) - strlen($last_path)), DS));
		do{
			$dir = array_shift($paths);
			$check = false;
			foreach(glob($root_path . '*', GLOB_ONLYDIR) as $v){
				if(strcasecmp($root_path . $dir, $v) == 0){
					$root_path = $v . DS;
					$check = true;
					break;
				}
			}

			if(!$check){
				return null;
			}

		}while(count($paths) > 0);
	}else{
		$last_path = $path;
	}

	foreach(glob($root_path . '*') as $v){
		if(strcasecmp($root_path . $last_path, $v) == 0){
			if(is_file($v)){
				return $v;
			}else{
				return $v . DS;
			}
		}
	}

	return null;
}

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

abstract class M{
	/**
	 * set & get global config
	 * @param  string $key
	 * @param  $val
	 * @param  boolean $overwrite
	 */
	public static function config($key = EMPTY_VALUE, $val = EMPTY_VALUE, $overwrite = true){
		static
			$data = array(),
			$allow_overwrite = array();

		// return all configs
		if(self::is_empty($key)){
			return $data;
		}

		// convert key to lower case
		self::trimmer($key, false);

		// return config by key
		if(self::is_empty($val)){
			if(isset($data[$key])){
				return $data[$key];
			}

			return $data;
		}

		// set config value
		if(!isset($data[$key]) || !isset($allow_overwrite[$key])){
			$data[$key] = $val;
		}

		// don't allow overwrite
		if(!$overwrite){
			$allow_overwrite[$key] = true;
		}

		return $data[$key];
	}

	/**
	 * register class
	 * @param string|array $class
	 * @param string $file
	 * @param $args
	 * @param boolean $overwrite
	 */
	/*
	public static function register($class = null, $file = null, $args = null, $overwrite = true){
		static $reg = array('data' => array(), 'overwrite' => array());

		// return all class register
		if(!trim_lower($class)){
			return $reg['data'];
		}

		// get class & alias
		if(is_array($class)){
			$key 	= key($class);
			$alias	= $class[$key];
			$class 	= get_class_name($key);
		}else{
			$alias = get_class_name($class);
		}
		trim_slash($alias, '\\');

		// return | check class register
		if(!trim($file)){
			if(isset($reg['data'][$alias])){
				return $reg['data'][$alias];
			}

			return null;
		}

		// assign class
		if(!isset($reg['data'][$alias]) || !isset($reg['overwrite'][$alias])){
			$reg['data'][$alias] = array('class' => $class, 'file' => $file, 'args' => $args);
		}

		// don't allow overwrite
		if(!$overwrite){
			$reg['overwrite'][$alias] = true;
		}

		return $reg['data'][$alias];
	}
	*/

	/**
	 * get & autoload library
	 * @param  string $lib
	 * @param  array $args
	 */
	public static function get($lib, $args = null){
		// check system lib
		if(strpos($lib, '.') === false){
			$lib = 'system.' . $lib;
		}

		// return controller
		if(trim_lower($lib) == 'system.controller'){
			return self::get_controller();
		}

		// load lib
		return self::load($lib, APP_PATH . str_replace('.', DS, $lib) . EXT, $args);
	}

	public static function get_args($lib){
		$args = func_get_args();
		array_shift($args);

		return self::get($lib, $args);
	}

	/**
	 * load library
	 * @param  string $name
	 * @param  string $file
	 * @param  $args
	 * @param  string $class
	 */
	public static function load($name, $file = null, $args = null, $class = null){
		// store lib
		static $instance = array();

		// get args
		if(is_object($args)){
			$args = array($args);
		}else{
			$args = (array)$args;
		}

		// get class name
		trim_lower($name);
		if(!trim_lower($class)){
			$class = $name;
		}
		$class = '\\' . trim(str_replace('.', '\\', $class), '\\');

		// return lib if exist
		if(isset($instance[$name])){
			if(is_callable($args)){
				call_user_func($args, $instance[$name]);
			}

			return $instance[$name];
		}

		// check file & class
		if(!self::import($file, false) || !class_exists($class)){
			return null;
		}

		// create new object
		if(method_exists($class,  '__construct')){
			$ref = new ReflectionClass($class);
			$instance[$name] = $ref->newInstanceArgs($args);
		}else{
			$instance[$name] = new $class;
		}

		// auto call construct method
		if(($main = get_string_last($class, '\\')) && method_exists($class, $main)){
			call_user_func_array(array($class, $main), $args);
		}

		return $instance[$name];
	}

	/**
	 * import file
	 * @param  string | array $file
	 * @param  boolean $require
	 * @param  $args
	 */
	public static function import($files, $require = true, &$args = null){
		$ret = true;

		// check & include file
		foreach((array)$files as $file){
			if(!($f = get_readable($file))){
				if($require){
					self::exception('\M::import(...) : Failed opening required %s', $file);
				}else{
					$ret = false;
					continue;
				}
			}

			if(!in_array($f, get_included_files())){
				require_once $f;
			}
		}

		return $ret;
	}

	/**
	 * redirect url
	 * @param  string $url
	 * @param  boolean $absolute
	 * @param  integer $code
	 * @param  boolean $replace
	 */
	public static function redirect($url = '', $absolute = false, $code = 301, $replace = false){
		if(!$url){
			$url = SITE_ROOT;
			$absolute = true;
		}

		if(!$absolute){
			$url = SITE_ROOT . $url;
		}

		header('Location: ' . $url, $replace, $code);
		die;
	}

	/**
	 * get controller instance
	 * @param  object $obj
	 */
	public static function get_controller(&$obj = null){
		static $instance = null;

		if(!$instance){
			$instance = $obj;
		}

		return $instance;
	}

	/**
	 * load module (must abstract class)
	 * @param  string $module
	 */
	public static function load_module($module){
		\M::import(array(MODULE_PATH . $module . DS . 'config' . EXT, MODULE_PATH . $module . DS . $module . EXT), false);
		$cls = $module . '\Controller\\Controller';

		if(!class_exists($cls)){
			return null;
		}

		$new_class = '__M_Load_Module_' . $module . '__';
		$content = '<?php class ' . $new_class . ' extends ' . $cls . '{function index(){}}';
		$content .= '$' . $new_class . ' = new ' . $new_class . ';?>';
		ob_start();
			$tmp = tempnam('/tmp', $new_class);
			$handle = fopen($tmp, 'w');
			fwrite($handle, $content);
			fclose($handle);
			include $tmp;
			unlink($tmp);
		ob_get_clean();
	}

	/**
	 * handler exception
	 * @param  string $str
	 * @param  array $args
	 */
	public static function exception($str, $args = null){
		throw new \Exception(vsprintf($str, (array)$args));
	}

	/**
	 * check empty value
	 * @param  $var
	 */
	public static function is_empty($var){
		if($var === EMPTY_VALUE){
			return true;
		}

		return false;
	}

	/**
	 * trim & convert string to lower|upper case
	 * @param  string &$str
	 * @param  boolean $case
	 */
	public static function trimmer($str, $case = EMPTY_VALUE){
		$str = trim(preg_replace('#\s+#', ' ', $str));
		if(self::is_empty($case)){
			return $str;
		}

		if(!$case){
			$str = strtolower($str);
		}else{
			$str = strtoupper($str);
		}

		return $str;
	}

	/**
	 * replace multi & trim slash
	 * @param  string &$str
	 * @param  string $slash
	 */
	public static function trim_slash($str, $slash = '/'){
		return $str = trim(preg_replace("/[\/\\\]+/", $slash, $str), $slash);
	}

	/**
	 * get last string
	 * @param  string $str
	 * @param  string $symbol
	 */
	public static function last_string($str, $symbol = '/'){
		return substr(strrchr($str, $symbol), 1);
	}

	/**
	 * json parse (decode)
	 * @param  string  $str
	 * @param  boolean $assoc
	 */
	public static function json_parse($str, $assoc = true){
		try{
			return json_decode($str, $assoc);
		}catch(\Exception $e){
			return array();
		}
	}

	/**
	 * get readable file|path
	 * @param  string $path
	 */
	public static function is_readable($path, $root_path = EMPTY_VALUE){
		if(!self::is_empty($root_path)){
			$root_path = APP_PATH;
		}

		if(!($path = (IS_WINDOWS ? '' : DS) . self::trim_slash($path, DS))){
			return null;
		}

		if(is_readable($path)){
			return $path;
		}

		$paths = explode(DS, trim($path, DS));
		$len = count($paths);

		// get last folder | file
		$last = $paths[$len-1];
		array_pop($paths);

		// check parent directory exist
		$temp = $root_path;
		do{
			if(!$first = array_shift($paths)){
				$first = $last;
			}

			$check = false;
			foreach(glob($temp . '*') as $v){
				if(strcasecmp($v, $temp . $first) == 0){
					$temp = $v . DS;
					$check = true;
					break;
				}
			}

			if(!$check){
				return null;
			}
		}while(count($paths) > 0);

		// check folder | file exist
		$check = false;
		foreach(glob($temp . '*') as $v){
			if(strcasecmp($v, $temp . $last) == 0){
				$temp = $v;
				$check = true;
				break;
			}
		}

		if(!$check || $temp == $root_path){
			return null;
		}

		return $temp . (is_file($temp) ? '' : DS);
	}
}
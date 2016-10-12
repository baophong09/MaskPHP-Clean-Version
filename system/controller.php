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

namespace System;

abstract class Controller{
	/**
	 * All controllers must contain an index method
	 */
	abstract public function index();

	/**
	 * get library
	 * @param  string $lib
	 * @param  $args
	 */
	public function get($lib, $args = null){
		return \M::get($lib, $args);
	}

	/**
	 * get library inside module
	 * @param  string $lib
	 * @param  $args
	 */
	public function load($lib, $args = null){
		$r = \M::get('router');
		return \M::load(
			($r->get('extend_module') ? $r->get('extend_module') : $r->get('module')) . '.library.' . trim_lower($lib),
			$r->get('process_path') . 'library' . DS . $lib . EXT,
			$args,
			$lib
		);
	}

	/**
	 * redirect url
	 * @param  string $url
	 * @param  boolean $absolute
	 * @param  number $code
	 * @param  boolean $replace
	 */
	public function redirect($url = null, $absolute = false, $code = 301, $replace = false){
		\M::redirect($url, $absolute, $code, $replace);
	}

	/**
	 * expand method
	 * @param  string $name
	 * @param  array $args
	 */
	function __call($name, $args){
		$lib = null;
		if(substr($name, 0, 4) == 'get_'){
			$instance = substr($name, 4);
			switch($instance){
				case 'model':
					$lib = \M::get('model');
					$model = isset($args[0]) ? $args[0] : \M::get('router')->get('module');
					$module = isset($args[1]) ? $args[1] : \M::get('router')->get('module');
					$extend_module = isset($args[2]) ? $args[2] : null;
					return $lib->get($model, $module, $extend_module);
				break;
			}
			
		}

		if($lib){
			return $lib;
		}

		return \M::get('event')->expand('system.controller.expand.' . $name, $args, $this);
	}
}
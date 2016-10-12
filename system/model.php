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

class Model{
	public $db = null;

	function __construct(){
		\M::get('event')->change('system.model.on_get_model', $this->db);
	}

	/**
	 * load model
	 * @param  string $model
	 * @param  string $module
	 * @param  string $extend_module
	 */
	public function get($model = null, $module = null, $extend_module = null){
		// get router
		$router = \M::get('router');
		
		$group_controller = $router->get('group_controller');
		$controller = $router->get('controller');

		// get process path & lib
		if(!trim_lower($module)){
			$module = $router->get('module');
		}
		$lib = $module;
		$process_path = APP_PATH . 'module' . DS . $module . DS;

		// get extend
		if(!trim_lower($extend_module)){
			$extend_module = $router->get('extend_module');
		}
		if($extend_module){
			$lib .= '\extend\\' . $extend_module;
			$process_path .= 'extend' . DS . $extend_module . DS;
		}

		// get controller
		if(!trim_lower($model)){
			$model = $group_controller . '/' . $controller;
		}

		// load model
		$lib .= '\Model\\' . trim_slash($model, '\\');
		$ret = \M::load($lib, $process_path . 'model' . DS . trim_slash($model, DS) . EXT);

		\M::get('event')->change('system.model.on_load', $ret, $model);
		return $ret;
	}

	/**
	 * expand method
	 * @param  string $name
	 * @param  array $args
	 */
	function __call($name, $args){
		if(substr($name, 1, 4) == 'get_'){
			$model = isset($args[0]) ? $args[0] : null;
			$module = isset($args[1]) ? $args[1] : null;
			$extend_module = isset($args[2]) ? $args[2] : null;
			return $this->get($model, $module, $extend_module);
		}

		return \M::get('event')->expand(strtolower('system.model.expand.' . $name), $args, $this);
	}
}
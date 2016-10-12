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

interface Template{
	/**
	 * __construct
	 * @param  array $args
	 */
	public function __construct($args);

	/**
	 * load content
	 * @param  string $view
	 * @param  array $args
	 */
	public function display($view, &$args);
}

class View{
	public
		$extension	= '.php',		// extension
		$template 	= null;			// template name

	protected
		$engine 	= null,			// template engine
		$data 		= array();		// assign data

	/**
	 * load & display view
	 * @param  string $view
	 * @param  string $module
	 * @param  string $extend_module
	 */
	public function display($view = null, $module = null, $extend_module = null){
		// get router
		$router = \M::get('router');
		$process_path = $router->process_path;
		if(trim_lower($module)){
			$process_path = MODULE_PATH . $module . DS;
		}

		if(trim_lower($extend_module)){
			$process_path = MODULE_PATH . $module . DS . 'extend' . DS . $extend_module . DS;
		}

		// auto get view
		if(!trim_lower($view) || !get_readable($view)){
			if(!$view){
				$view = $router->get('group_controller') . DS . $router->get('controller') . DS . $router->get('action');
			}

			$view = $process_path . 'view' . DS . $view;
		}

		// change $view
		\M::get('event')->change('system.view.on_get_view', $view, $this);
		if(!($f = get_readable(preg_replace('/' . str_replace('.', '\.', $this->extension). '$/', '', $view) . $this->extension))){
			\M::exception('System\View->display(...) : Failed opening required %s', $view);
		}
		

		// check use template engine
		if(trim_lower($this->template)){
			$args = array(
				'theme_path'	=> THEME_PATH,
				'view_path'		=> $process_path . 'view' . DS,
				'widget_path'	=> array(APP_PATH . 'widget' . DS, $process_path . 'widget' . DS),
				'cache_path'	=> CACHE_PATH . 'view' . DS,
				'extension'		=> $this->extension
			);

			// change $args
			\M::get('event')->change('system.view.on_get_config', $args, $this->template);

			// load template engine
			$lib = 'system.view.' . $this->template;
			if(!\M::load($lib)){
				$this->engine = \M::get($lib, array($args));
			}

			foreach($args as $k => $v){
				if(!$this->engine->{$k}){
					$this->engine->{$k} = $v;
				}
			}

			\M::get('event')->change('system.view.on_get_template', $this->engine, $this->template);
			$this->engine->display($f, $this->data);
		}
		// not use template
		else{
			\M::import($f, true, $this->data);
		}

		return $this;
	}

	/**
	 * assign variable to view
	 * @param  string | array $name
	 * @param  $value
	 */
	public function assign($name, $value = null){
		if(is_array($name)){
			$this->data = array_merge($this->data, $name);
		}else{
			$this->data[$name] = $value;
		}

		return $this;
	}

	/**
	 * get template engine
	 */
	public function &get_template(){
		if(!$this->engine){
			$this->engine = \M::get('system.view.' . $this->template);
		}
		return $this->engine;
	}

	/**
	 * get property
	 * @param  string $key
	 */
	public function get($key = null){
		if(property_exists($this, $key)){
			return $this->{$key};
		}else{
			return null;
		}
	}

	/**
	 * expand method
	 * @param  string $name
	 * @param  array $args
	 */
	function __call($name, $args){
		return \M::get('event')->expand('system.view.expand.' . $name, $args, $this);
	}
}
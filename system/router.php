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

class Router{
	public
		$default_module		= null,			// default module
		$default_url		= null,			// default url
		$url_extension		= null,			// url extension
		$error_url			= null,			// error url

		$url_request		= null,			// url request
		$query_string		= null,			// query string
		$request_uri		= null,			// request uri
		$uri_segment		= array(),		// uri segment

		$module_path 		= null,			// module path
		$extend_path 		= null,			// extend path
		$process_path 		= null;			// process path

	protected
		$module 			= null,			// module name
		$extend_module 		= null,			// extend name
		$group_controller 	= null,			// group controller
		$controller 		= 'index',		// controller
		$action 			= 'index';		// action

	/**
	 * handle request parsing... then response result to client
	 */
	public function response(){
		// register event on_shutdown
		register_shutdown_function(function(){
			// on_shutdown
			\M::get('event')->trigger('system.on_shutdown');

			// debug
			\M::get('debug')->exception_fatal();
		});

		ob_start();
			// load default config & controller core
			require_once APP_PATH . 'config.php';
			require_once SYSTEM_PATH . 'controller.php';

			// on load
			\M::get('event')->trigger('system.on_load', DOMAIN);

			// parser url
			$this->parser_url();
			// load module
			$this->load_module(input('module', 'str', 'get'));
			// load extend module
			$this->load_extend(input('extend_module', 'str', 'get'));
			// load group controller & controller
			list($lib, $instance) = $this->load_controller(input('group_controller', 'str', 'get'), input('controller', 'str', 'get'));
			// load action
			$this->load_action($lib, $instance);
		$html = ob_get_clean();

		// on response
		ob_start();
		\M::get('event')->change('system.on_response', $html);

		// display html & end all script
		die($html);
	}

	/**
	 * parser request url
	 */
	private function parser_url(){
		// trigger on_get_domain
		\M::get('event')->trigger('system.on_get_domain', DOMAIN);

		// get url request
		$this->url_request = substr($_SERVER['REQUEST_URI'], strlen(SITE_PATH));
		if(!$this->url_request){
			$this->url_request = $this->default_url;
		}
		// on_get_url_request
		\M::get('event')->change('system.on_get_url_request', $this->url_request, $this);
		trim_slash($this->url_request);

		// get query string
		$this->query_string = ltrim(strstr($this->url_request, '?'), '?');
		// get request uri
		$this->request_uri = trim(substr($this->url_request, 0, strlen($this->url_request) - strlen($this->query_string)), '?');

		// on_get_query_string
		\M::get('event')->change('system.on_get_query_string', $this->query_string, $this);
		// assign to $_GET
		parse_str((string)$this->query_string, $_GET);

		// on_get_request_uri
		\M::get('event')->change('system.on_get_request_uri', $this->request_uri, $this);
		trim_slash($this->request_uri);

		// get uri segment
		// remove extension & explode uri segment
		$extension = '';
		foreach((array)$this->url_extension as $v){
			$extension .= preg_quote($v) . '|';
		}
		if($extension){
			$extension = '(' . rtrim($extension, '|') . ')';
			$request_uri = preg_replace('/' . $extension . '$/i', '', $this->request_uri);
		}else{
			$request_uri = $this->request_uri;
		}

		$this->uri_segment = $this->request_uri ? explode('/', $request_uri) : array();
		// on_get_uri_segment
		\M::get('event')->change('system.on_get_uri_segment', $this->uri_segment, $this);
	}

	/**
	 * build url
	 * @param  string|array $args
	 * @param  boolean $query
	 * @param  string $extension
	 */
	public function build_url($args, $query = false, $extension = '.html'){
		$list = array('module', 'extend_module', 'group_controller', 'controller', 'task');

		// extract string to array
		if(is_string($args)){
			parse_str($args, $args);
		}

		// set variable
		foreach($list as $v){
			$$v = '';
			if(isset($args[$v])){
				$$v = $args[$v] . '/';
				unset($args[$v]);
			}
		}

		// get url
		$url = $module . $extend_module . $group_controller . $controller . $task;

		// get query
		$q = '';
		if($query){
			foreach($args as $k => $v){
				$q .= $k . '=' . $v . '&';
			}
			$q = trim($q, '&');

			if($url){
				$url = trim($url, '/') . $extension;
			}

			$url .= '?' . $q;
		}
		// get uri
		else{
			foreach($args as $v){
				$q .= $v . '/';
			}
			$url .= trim($q, '/') . $extension;
		}

		return $url;
	}

	/**
	 * load module
	 * @param  string $module
	 */
	private function load_module($module){
		// get module
		if($module){
			$this->module = $module;
		}elseif(isset($this->uri_segment[0]) && array_value_exist($this->uri_segment[0], get_folder(MODULE_PATH))){
			$this->module = array_shift($this->uri_segment);
		}else{
			$this->module = $this->default_module;
		}

		// module path
		$this->module_path = MODULE_PATH . $this->module . DS;

		// on_get_module
		\M::get('event')->change('system.on_get_module', $this->module, $this);
		$this->process_path = $this->module_path;

		// load configs
		\M::import($this->module_path . 'config' . EXT, false);
		// load bootstrap controller
		if(\M::import($this->module_path . $this->module . EXT, false)){
			$cls = $this->module . '\Controller\Controller';
			if(!class_exists($cls)){
				\M::exception('System\Router->load_module(...): Class %s does not exist!', $cls);
			}
		}
	}

	/**
	 * load extend module
	 * @param  string extend_module
	 */
	private function load_extend($extend_module){
		if($extend_module){
			$this->extend_module = $extend_module;
		}elseif(isset($this->uri_segment[0]) && $this->uri_segment[0]){
			$this->extend_module = array_shift($this->uri_segment);
		}

		// check allow extend module
		$allow_extend_module = array();
		// on_allow_extend_module
		\M::get('event')->change('system.on_allow_extend_module', $allow_extend_module, $this->module);

		$allow_extend = false;
		if(array_value_exist($this->extend_module, (array)$allow_extend_module)){
			$allow_extend = true;
		}

		// extend module
		$this->extend_path = MODULE_PATH . $this->extend_module . DS . 'extend' . DS . $this->module;
		// on_get_extend_path
		\M::get('event')->change('system.on_get_extend_path', $this->extend_path, $this);

		if($allow_extend && get_resource($this->extend_path)){
			// load configs, init
			\M::import(array($this->extend_path . 'config' . EXT, $this->extend_path . $this->module . EXT), false);

			// on_get_extend_module
			\M::get('event')->trigger('system.on_get_extend_module', $this);
			$this->process_path = $this->extend_path;
		}else{
			if(!$extend_module && $this->extend_module){
				array_unshift($this->uri_segment, $this->extend_module);
			}
			$this->extend_module = $this->extend_path = null;
		}
	}

	/**
	 * load controller
	 * @param  string $group_controller
	 * @param  string $controller
	 */
	private function load_controller($group_controller, $controller){
		// get group controller
		if($group_controller){
			$this->group_controller = $group_controller;
		}elseif(isset($this->uri_segment[0]) && $this->uri_segment[0]){
			$this->group_controller = array_shift($this->uri_segment);
		}
		$controller_path = $this->process_path . 'controller' . DS;

		if($path = get_readable($controller_path . $this->group_controller)){
			// on_get_group_controller
			\M::get('event')->change('system.on_get_group_controller', $this->group_controller, $this);
			$controller_path = $path;
		}else{
			if(!$group_controller && $this->group_controller){
				array_unshift($this->uri_segment, $this->group_controller);
			}

			$this->group_controller = null;
		}

		// get controller
		if($controller){
			$this->controller = $controller;
		}elseif(isset($this->uri_segment[0]) && $this->uri_segment[0]){
			$this->controller = array_shift($this->uri_segment);
		}

		if(!get_readable($controller_path . $this->controller . EXT)){
			if(!$controller && $this->controller){
				array_unshift($this->uri_segment, $this->controller);
				$this->controller = 'index';
			}
		}

		// check controller file
		if(!\M::import($controller_path . $this->controller . EXT, false)){
			\M::redirect($this->error_url);
		}

		// get controller & set instance
		if($this->extend_module){
			$lib = $this->extend_module . '\Extend\\' . $this->module;
		}else{
			$lib = $this->module;
		}
		$lib .= '\Controller\\' . $this->group_controller . $this->controller;

		if(!class_exists($lib)){
			\M::redirect($this->error_url);
		}

		// set controller instance
		$instance = new $lib;
		\M::get_controller($instance);

		// trigger on_get_controller
		\M::get('event')->change('system.on_get_controller', $instance, $this->group_controller, $this->controller);

		return array($lib, $instance);
	}

	/**
	 * load application
	 * @param  string $lib
	 * @param  object $instance
	 */
	private function load_action($lib, $instance){
		// get action
		if(!empty($_GET['task'])){
			$this->action = $_GET['task'];
		}else if(!empty($this->uri_segment[0])){
			$this->action = array_shift($this->uri_segment);
		}

		// check method exist | is public
		if(!method_exists($lib, $this->action) || !(new \ReflectionMethod($lib, $this->action))->isPublic()){
			if(empty($_GET['task']) && $this->action){
				array_unshift($this->uri_segment, $this->action);
			}
			$this->action = 'index';
		}

		// excute
		\M::get('event')->change('system.on_get_action', $this->action, $this);
		call_user_func_array(array($instance, $this->action), $this->uri_segment);
	}

	/**
	 * get property
	 * @param  string $property
	 */
	public function get($property){
		if(property_exists($this, $property)){
			return $this->{$property};
		}

		return null;
	}

	/**
	 * expand method
	 * @param  string $name
	 * @param  array $args
	 */
	function __call($name, $args){
		return \M::get('event')->expand('system.router.expand.' . $name, $args, $this);
	}
}
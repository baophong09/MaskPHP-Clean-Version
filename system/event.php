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

class Event{
	protected
		$hook	= array(),		// stack event
		$event	= array();		// trigger event

	/**
	 * hook variable
	 * @param  string  $event
	 * @param  $args
	 * @param  integer  $priority
	 */
	public function hook_var($event, $args, $priority = 0){
		$this->hook($event, $args, true, $priority);
		return $this;
	}

	/**
	 * hook file
	 * @param  string  $event
	 * @param  $args
	 * @param  integer $priority
	 */
	public function hook_file($event, $args, $priority = 0){
		$this->hook($event, $args, false, $priority);
		return $this;
	}

	/**
	 * hook module
	 * @param  string  $event
	 * @param  $args
	 * @param  integer $priority
	 */
	public function hook_module($event, $args, $priority = 0){
		$args = (array)$args;
		foreach($args as $k => $v){
			$args[$k] = MODULE_PATH . $v . DS . 'hook' . DS . $event . EXT;
		}

		$this->hook($event, $args, false, $priority);

		return $this;
	}

	/**
	 * hook
	 * @param  string  $event
	 * @param  $args
	 * @param  boolean  $is_var
	 * @param  integer  $priority
	 */
	public function hook($event, $args = null, $is_var = false, $priority = 0){
		// store event
		if(!isset($this->hook[trim_lower($event)])){
			$this->hook[$event] = array();
		}

		// event priority
		if(!isset($this->hook[$event][$priority])){
			$this->hook[$event][$priority] = array();
		}

		// anonymous functions
		if(is_callable($args)){
			$this->hook[$event][$priority][] = array('fn' => $args);
		}
		// change value direct
		elseif($is_var){
			$this->hook[$event][$priority][] = array('var' => $args);
		}
		// file
		else{
			$this->hook[$event][$priority][] = array('file' => $args);
		}

		return $this;
	}

	/**
	 * trigger
	 * @param  string $event
	 * @param  array $args
	 * @param  $var
	 */
	public function &trigger($event, $args = null, &$var = null, $expand = false){
		// get arguments
		if(is_object($args)){
			$args = array($args);
		}else{
			$args = (array)$args;
		}

		// push reference
		if(!is_null($var)){
			array_unshift($args, null);
			$args[0] =& $var;
		}

		// set reference
		if(!isset($this->event[trim_lower($event)])){
			$this->event[$event] =& $var;
		}

		// check hooks
		if(!isset($this->hook[$event])){
			return $var;
		}

		// order by ASC
		ksort($this->hook[$event]);

		// excute
		foreach($this->hook[$event] as $events){
			foreach($events as $v){
				// hook function
				if(($key = key($v)) === 'fn'){
					if($expand){
						$var = call_user_func_array($v[$key], $args);
					}else{
						call_user_func_array($v[$key], $args);
					}
				}
				// hook file
				elseif($key === 'file'){
					foreach($v[$key] as $f){
						\M::import($f, true, $args);
					}
				}
				// hook variable
				else{
					$var = $v[$key];
				}
			}
		}

		return $var;
	}

	/**
	 * change value
	 * @param  string $event
	 * @param  $reference
	 */
	public function &change($event, &$reference){
		// get arguments
		$args = array_slice(func_get_args(), 2);
		self::trigger($event, $args, $reference);
		return $reference;
	}

	/**
	 * expand object method
	 * @param  string  $event
	 * @param  array  $args
	 * @param  object  $obj
	 * @param  boolean  $overwrite
	 */
	public function expand($event, $args, &$obj, $overwrite = false){
		if(isset($this->hook[trim_lower($event)])){
			// don't allow multi hook
			if(!$overwrite){
				$hook = array_shift($this->hook[$event]);
				$this->hook[$event] = array(array($hook[0]));
			}

			return $this->trigger($event, $args, $obj, true);
		}

		return $obj;
	}

	/**
	 * get properties
	 * @param  string  $key
	 */
	public function get($key){
		if(property_exists($this, $key)){
			return $this->{$key};
		}

		return null;
	}
}
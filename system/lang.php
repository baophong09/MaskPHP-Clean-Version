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

abstract class Language{
	/**
	 * __construct
	 * @param  array $args
	 */
	abstract public function __construct($args);

	/**
	 * load language
	 */
	abstract public function load($file);

	/**
	 * translate
	 * @param  string $key
	 * @param  array $args
	 */
	abstract public function translate($key, $args);
}

class Lang{
	public
		$engine		= null,
		$current 	= 'en',
		$list 		= array('en', 'vi'),
		$extension 	= '.txt';

	protected
		$data 		= array();

	/**
	 * translate key
	 * @param  string $key
	 * @param  string | array $args
	 */
	public function translate($key, $args = null){
		if(isset($this->data[$key])){
			if($args){
				return vsprintf($this->data[$key], (array)$args);
			}else{
				return $this->data[$key];
			}
		}

		return $key;
	}

	public function load($files){
		foreach($files as $f){
			
		}
	}

	/**
	 * expand method
	 * @param  string $name
	 * @param  array $args
	 */
	function __call($name, $args){
		return \M::get('event')->expand(strtolower('system.lang.expand.' . $name), $args, $this);
	}
}
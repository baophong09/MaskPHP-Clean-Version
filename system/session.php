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

class Session{
	const HANDLER 	= '__system.session.handler__';

	public $timeout = 30; // default 30'

	function __construct(){
		// set ini
		$timeout = (int)$this->timeout*60;
		$this->ini(array(
			'gc_maxlifetime'	=> $timeout,
			'cookie_lifetime'	=> 0
		));

		// set session handler
		if(!isset($_SESSION[self::HANDLER])){
			$_SESSION[self::HANDLER] = array();
		}

		// auto destroy session
		\M::get('event')->hook('system.on_load', function(){
			// auto destroy session
			$this->auto_destroy();
		});
	}

	/**
	 * session ini
	 * @param  string | array $args  [description]
	 * @param  string | int $value
	 */
	public function ini($args, $value = null){
		// destroy session
		if(session_id()){
			session_destroy();
		}

		foreach((array)$args as $k => $v){
			ini_set('session.' . trim_lower($k), $v);
		}

		// start session
		session_set_cookie_params(0);
		session_start();
		return $this;
	}

	/**
	 * set session
	 * @param  string $name
	 * @param  $value
	 * @param  integer $timeout (minute)
	 */
	public function set($name, $value = null, $timeout = null){
		// get session name
		trim_lower($name);

		// set timeout
		if(is_null($timeout)){
			if(isset($_SESSION[self::HANDLER][$name])){
				$timeout = $_SESSION[self::HANDLER][$name];
			}else{
				$timeout = $this->timeout;
			}
		}

		// set session handler
		$_SESSION[self::HANDLER][$name] = time() + $timeout*60;

		// set session data
		if(is_object($value)){
			$_SESSION[$name] = serialize($value);
		}else{
			$_SESSION[$name] = $value;
		}

		return $this;
	}

	/**
	 * get session
	 * @param  string $name
	 */
	public function get($name){
		if(isset($_SESSION[trim_lower($name)])){
			if(is_string($_SESSION[$name])){
				if( $ret = @unserialize($_SESSION[$name]) ){
					return $ret;
				}
				
				return $_SESSION[$name];
				/*try{
					logc($_SESSION[$name]);
					return @json_decode($_SESSION[$name]);
				}catch(\Exception $e){
					return $_SESSION[$name];
				}*/
			}else{
				return $_SESSION[$name];
			}
		}else{
			return null;
		}
	}

	/**
	 * delete session
	 * @param  string $name
	 */
	public function delete($name){
		if(isset($_SESSION[trim_lower($name)])){
			// unset session
			unset($_SESSION[$name]);
			// remove handler
			unset($_SESSION[self::HANDLER][$name]);
		}

		return $this;
	}

	/**
	 * set cookie
	 * @param  string  $name
	 * @param  string  $data
	 * @param  int  $timeout
	 * @param  string  $path
	 * @param  string  $domain
	 */
	public function set_cookie($name, $data, $timeout = null, $path = '/', $domain = DOMAIN){
		if(is_null($timeout)){
			$timeout = $this->timeout;
		}

		if(is_array($data) || is_object($data)){
			$data = json_encode($data);
		}elseif(is_bool($data)){
			$data = $data ? 'TRUE' : 'FALSE';
		}

		setcookie($name, $data, time() + $timeout*60, $path, $domain, 0);

		return $this;
	}

	/**
	 * get cookie
	 * @param  string  $name
	 * @param  boolean $json_decode
	 */
	public function get_cookie($name, $json_decode = EMPTY_VALUE){
		if(!empty($_COOKIE[$name])){
			if(!is_empty($json_decode)){
				return json_parse($_COOKIE[$name], (boolean)$json_decode);
			}else{
				return $_COOKIE[$name];
			}
		}
		return null;
	}

	/**
	 * delete cookie
	 * @param  strubg $name
	 */
	public function delete_cookie($name, $path = '/', $domain = DOMAIN){
		// remove server cookie
		if(!empty($_COOKIE[trim_lower($name)])){
			unset($_COOKIE[$name]);
		}

		if(isset($_SERVER['HTTP_COOKIE'])){
			$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
			foreach($cookies as $cookie){
				$c = explode('=', $cookie);
				if(trim_lower($c[0]) == $name){
					$this->set_cookie($name, '', time() - 1000, $path, $domain);
					break;
				}
			}
		}

		return $this;
	}

	/**
	 * auto destroy session
	 */
	public function auto_destroy(){
		if(isset($_SESSION[self::HANDLER])){
			foreach($_SESSION[self::HANDLER] as $k => $v){
				if(time() > $v){
					unset($_SESSION[$k]);
					unset($_SESSION[self::HANDLER][$k]);
				}
			}
		}
	}

	/**
	 * expand method
	 * @param  string $name
	 * @param  array $args
	 */
	function __call($name, $args){
		return \M::get('event')->expand('system.session.expand.' . $name, $args, $this);
	}
}
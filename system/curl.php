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

class Curl{
	public
		$options	= array(),
		$cookies	= array();

	private $defaults = array(
		CURLOPT_CUSTOMREQUEST	=> 'GET',
		CURLOPT_POST 					=> false,			// set to GET
		//CURLOPT_POSTFIELDS			=> array(),			// set post fields
		//CURLOPT_COOKIEFILE 			=> "cookie.txt",	// set cookie file
		//CURLOPT_COOKIEJAR 			=> "cookie.txt",	// set cookie jar
		//CURLOPT_RETURNTRANSFER 		=> true,			// return web page
		//CURLOPT_HEADER 				=> false,			// don't return headers
		//CURLOPT_FOLLOWLOCATION 		=> true,			// follow redirects
		//CURLOPT_ENCODING 				=> '',				// handle all encodings
		CURLOPT_CONNECTTIMEOUT 			=> 10,				// timeout on connect
		CURLOPT_TIMEOUT 				=> 10,				// timeout on response
		//CURLOPT_MAXREDIRS 			=> 3,				// stop after 3 redirects
		//CURLOPT_HTTPHEADER			=> array(			// http header
			//"Accept-Encoding: gzip,deflate",
			//"Accept-Charset: utf-8;q=0.7,*;q=0.7",
			//"Connection: close"
		//),
		//set user agent
		CURLOPT_USERAGENT				=> 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.90 Safari/537.36'
	);

	/**
	 * get content from url by GET
	 * @param  string $url
	 * @param  array  $params
	 */
	public function get($url, $params = array()){
		$this->set_params($params);
		$this->options[CURLOPT_CUSTOMREQUEST] 	= 'GET';
		$this->options[CURLOPT_POST]			= false;
		return $this->get_contents($url);
	}

	/**
	 * get content from url by POST
	 * @param  string $url
	 * @param  array  $params
	 */
	public function post($url, $params = array()){
		$this->set_params($params);
		$this->options[CURLOPT_CUSTOMREQUEST] 	= 'POST';
		$this->options[CURLOPT_POST]			= true;
		return $this->get_contents($url);
	}

	/**
	 * get content from url
	 * @param  string $url
	 */
	public function get_contents($url){
		// get options
		$options = $this->defaults + $this->cookies + $this->options;
		// get content
		ob_start();
			$ch      = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt_array($ch, $options);
			$content = curl_exec($ch);
			$err     = curl_errno($ch);
			$errmsg  = curl_error($ch);
			$ret  	 = curl_getinfo($ch);
			curl_close($ch);
		$content = ob_get_clean();

		// return
		$ret['err']		= $err;
		$ret['errmsg']	= $errmsg;
		$ret['content'] = $content;

		// reset
		$this->clean();

		return $ret;
	}

	/**
	 * set params
	 * @param  array $args
	 */
	public function set_params($args){
		if(!isset($this->options[CURLOPT_POSTFIELDS])){
			$this->options[CURLOPT_POSTFIELDS] = array();
		}

		if(is_array($args)){
			$this->options[CURLOPT_POSTFIELDS] = $this->options[CURLOPT_POSTFIELDS] + $args;
		}else{
			$this->options[CURLOPT_POSTFIELDS] = $args;
		}
		return $this;
	}

	/**
	 * set userAgent
	 * @param  array $args
	 */
	public function set_user_agent($args){
		$this->options[CURLOPT_USERAGENT] = $args;
		return $this;
	}

	/**
	 * set header
	 * @param  array $args
	 */
	public function set_header($args){
		$this->options[CURLOPT_HTTPHEADER] = $args;
		return $this;
	}

	/**
	 * set options
	 * @param  array $args
	 */
	public function set_options($args){
		$this->options = $this->options + $args;
		return $this;
	}

	/**
	 * set cookie
	 * @param  array $args
	 */
	public function set_cookie($args){
		$this->cookies = $args;
		return $this;
	}

	/**
	 * clean 
	 */
	public function clean(){
		$this->options		= array();
		$this->cookies 		= array();
		return $this;
	}

	/**
	 * expand method
	 * @param  string $name
	 * @param  array $args
	 */
	function __call($name, $args){
		return \M::get('event')->expand('system.curl.expand.' . $name, $args, $this);
	}
}
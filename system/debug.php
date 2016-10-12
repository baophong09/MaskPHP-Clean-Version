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

class Debug{
	public
		$token 				= array(),		// list token
		$list 				= array(),		// list debug
		$display 			= array(		// display debug
			'memory_usage'	=> true,
			'excution_time'	=> true,
			'include_file'	=> true
		);

	protected
		$allow_display 		= false;		// allow display debug

	public function __construct(){
		// error handler
		set_error_handler(array($this, 'error_handler'));
		// exception handler
		set_exception_handler(array($this, 'exception_handler'));
	}

	/**
	 * Checks for a fatal error, work around for set_error_handler not working on fatal errors.
	 */
	public function exception_fatal(){
		$error = error_get_last();
		$this->error_handler($error['type'], $error['message'], $error['file'], $error['line']);
	}

	/**
	 * Error handler, passes flow over the exception logger with new ErrorException.
	 */
	public function error_handler($num, $str, $file, $line, $context = NULL){
		$this->exception_handler(new \ErrorException($str, 0, $num, $file, $line));
	}

	/**
	 * Uncaught exception handler.
	 */
	public function exception_handler(\Exception $e){
		$this->display_debug($e);
	}

	/**
	 * display error
	 */
	private function display_debug($e){
		static $file = '', $line = '';
		if(!$file){
			$trace = $e->getTrace();
			if(isset($trace[0]) && isset($trace[0]['file']) && strcasecmp($trace[0]['file'], SYSTEM_PATH . 'core'. EXT) == 0 && $trace[0]['function'] == 'exception'){
				$file = $trace[1]['file'];
				$line = $trace[1]['line'];
			}
		}

		// check token
		foreach($_GET as $kg => $vg){
			foreach((array)$this->token as $kt => $vt){
				if(strcasecmp($kg, (string)$kt) == 0){
					if($vt === true){
						$vt = 'true';
					}elseif($vt === false){
						$vt = 'false';
					}

					if(strcasecmp($vg, $vt) == 0){
						$this->allow_display = true;
						break;
					}
				}
			}
		}

		// check local
		if($e->getLine() > 0 && IS_LOCAL){
			$this->allow_display = true;
		}

		// don't display debug
		if(!$this->allow_display){
			return null;
		}

		// system error
		if($err_line = $e->getLine()){
			$err_file 	= $e->getFile();
			$this->list['error_file'] = array('label' => 'Error file', 'msg' => ($file ? $file : $err_file) . ' (' . ($line ? $line : $err_line) . ')');

			$err_msg 	= $e->getMessage();
			$this->list['error_msg'] = array('label' => 'Error', 'msg' => str_replace("\n", '<br>', $err_msg));

			$err = array('message' => $err_msg, 'file' => $err_file, 'line' => $err_line);
			// do something more...
			\M::get('event')->trigger('system.on_error', $err);
		}

		// memory usage & excution time
		$this->list['memory_usage'] = array('label' => 'Memory usage', 'msg' => $this->get_size(memory_get_peak_usage()) . ' / ' . $this->get_size(memory_get_peak_usage(true)));
		$this->list['excution_time'] = array('label' => 'Execution time', 'msg' => $this->get_time(APP_TIME_START, microtime(true)));

		// do something more...
		\M::get('event')->change('system.debug.on_get_data', $this->list, $this);

		$events = \M::get('event')->get('event');
		$events['system.on_display_debug'] = null;
		ksort($events);
		$this->list['hook_event'] = array('label' => 'Hook - Event', 'msg' => count($events) . '<i><br>- ' . implode('<br>- ', array_keys($events)) . '</i>');

		// include file
		$files = get_included_files();
		$this->list['include_file'] = array('label' => 'Included files', 'msg' => count($files));
		$this->list['include_file']['msg'] .= '<br>- ' . implode('<br>- ', $files);

		// auto display error
		$this->display['error_file'] = true;
		$this->display['error_msg'] = true;

		// debug display content
		$debug_content = '';
		foreach($this->list as $k => $v){
			if(array_key_exist($k, $this->display) && $this->display[$k] === true){
				$debug_content .= '<tr><td class="label">+ ' . $v['label'] . '</td><td class="msg"><b>:</b> ' . $v['msg'] . '</td></tr>';
			}
		}

		// display message
		$content =  
			'<html lang="en">
			<head><meta charset="UTF-8"><title>MaskPHP - A PHP Framework For Beginners</title></head>
			<body>
			<style>
			*{box-sizing:border-box;}
			body,html{margin:0;padding:0;height:100%;width:100%;}
			#system_debug_wrapper{
			    width:100%;
			    min-height:100%;
			    position:absolute;
			    background-color:#a3a3a3;
			    background-image:-webkit-linear-gradient(-45deg,rgba(255,255,255,.3) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.3) 50%,rgba(255,255,255,.3) 75%,transparent 75%,transparent);
			    background-image:-moz-linear-gradient(-45deg, rgba(255,255,255,.3) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.3) 50%,rgba(255,255,255,.3) 75%,transparent 75%,transparent);
			    background-image:-ms-linear-gradient(-45deg,rgba(255,255,255,.3) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.3) 50%,rgba(255,255,255,.3) 75%,transparent 75%,transparent);
			    background-image:-o-linear-gradient(-45deg,rgba(255,255,255,.3) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.3) 50%,rgba(255,255,255,.3) 75%,transparent 75%,transparent);
			    background-size:100px 100px;
			    top:0;
			    left:0;
			    padding:1% 0;
			    z-index:2147483647;
			    font-family:verdana,Tahoma,Helvetica;
			    font-size:14px;
			    color:#3e3e3e;
			}
			#system_debug{width:98%;background:#fff;border:1px solid #d3d3d3;box-shadow:1px 1px 1px 1px #888;position:relative;left:1%;}
			#system_debug h1{text-align:center;margin:0;padding:15px 0 15px 0;font-size:16px;background:#f0f0f0}
			#system_debug table{font-size:12px;width:100%;table-layout:fixed}
			#system_debug table tr td{padding:10px; border-top:1px solid #d3d3d3;line-height:150%;vertical-align:top;}
			#system_debug table tr td.label{font-weight:bold;padding-left:10px;width:20%;}
			#system_debug table tr td.msg{padding-left:10px;word-wrap:break-word;}
			#system_debug table pre{margin:0;padding:0;}

			</style>'.
			'<div id="system_debug_wrapper"><div id="system_debug">' . '<h1>MASKPHP DEBUGGING</h1>' . '<table border="0" cellspacing="0" cellpadding="0">' . $debug_content . '</table>' . '</div></div></body></html>';

		// do something more...
		\M::get('event')->change('system.debug.on_display', $content);

		// clear buffer
		while(ob_get_level()){
			ob_end_clean();
		}

		// display debug & end all script
		die($content);
	}

	/**
	 * Dumps information about variables
	 */
	public function log(){
		if(!$args = func_get_args()){
			\M::exception('System\Debug->log(...) : Missing arguments');
		}

		// get trace
		$trace = debug_backtrace();
		// not direct
		if(isset($trace[1]) && isset($trace[1]['function']) && preg_match('/^call_user/', $trace[1]['function'])){
			$trace = $trace[2];
		}
		// direct
		else{
			$trace = $trace[0];
		}

		// open file
		$raw = '';
		$line = $trace['line'];
		$i = 0;
		$handler = fopen($trace['file'], "r");
			while(($l = fgets($handler)) !== false){
				$i++;
				// remove comment
				$l = preg_replace("/(\/\/|\#)(.*)/", '', trim($l));
				if($i > $line){
					break;
				}
				
				$raw .= "\n" . $l;
			}
		fclose($handler);

		// replace double quotes
		$raw = str_replace('"', "'", $raw);
		// add new line
		$raw = preg_replace("/\'\s*;/", "';\n", $raw) . ';';
		// remove comment
		$raw = preg_replace('/\/\*(.*?)\*\//s', '', $raw);

		// push log to stack
		$temp = array();
		$raw = preg_replace_callback('/' . preg_quote($trace['function']) . '\s*\((.*?)\)\s*;/', function($m) use(&$temp){
			$hash = '__DEBUG_' . crc32($m[0]) . '__';
			$temp[$hash] = $m[1];
			return $hash;
		}, $raw);

		// remove quote
		$raw = str_replace("\'", '', $raw . ';');
		$raw = preg_replace('/\/\*(.*?);/', '', $raw);
		// remove string
		$raw = preg_replace("/\'(.*?)\'\s*;/ms", '', $raw);

		// get variables
		$vars = '';
		foreach($temp as $k => $v){
			if(strpos($raw, $k) !== false){
				$vars = $v;
				break;
			}
		}
		$vars = preg_replace('/\s*,\s*/', ',', trim($vars));
		$vars = explode(',', trim($vars, ','));

		$content = $trace['file'] . ' (' . $trace['line'] . ')' . '<br><pre>';
		foreach($args as $k => $v){
			// variable name
			$content .= '- ' . htmlentities($vars[$k]) . ': ';

			// is string
			if(is_string($v)){
				$content .= 'string(' . strlen($v) . ') "' . htmlentities($v) . '"';
			}
			// is numeric
			elseif(is_numeric($v)){
				$content .= 'number(' . $v . ')';
			}
			// is boolean
			elseif(is_bool($v)){
				$content .= 'boolean(' . ($v ? 'TRUE' : 'FALSE') . ')';
			}
			// is null
			elseif(is_null($v)){
				$content .= 'NULL';
			}
			// is array
			elseif(is_array($v)){
				$count = count($v);
				$content .= 'array(' . $count . ')<br>' . print_r($v, true);
			}
			// is function
			elseif(is_callable($v)){
				$ref = (new \ReflectionFunction($v));
				if(preg_match('/^\$/', $vars[$k])){
					$use = '';
					foreach($ref->getStaticVariables() as $k => $v){
						$use .= '$' . $k . ', ';
					}
					$use = trim($use, ', ');

					$params = '';
					foreach($ref->getParameters() as $param){
						$v = $param->getDefaultValue();
						$params .= $param->getName() . ' = ' . (is_string($v) ? '"' . $v . '"' : $v) . ', ';
					}
					$content .= $ref->getName() . '<br>' . 'function(' . trim($params, ', ') . ')' . ($use ? ' use (' . $use . ')' : '') . '{...}';
				}else{
					$content = substr($content, 0, strlen($content) - strlen($vars[$k]) - 2);
					$content .= $ref->getName() . '<br>' . preg_replace('/\)\{(.*?)\}$/s','){...}', $vars[$k]);
				}
			}
			// is object
			elseif(is_object($v)){
				$cls = get_class($v);
				$content .= $cls . '<br>' . trim(substr(print_r($v, true), strlen($cls)));
			}
			// is resource
			elseif(is_resource($v)){
				$content .= 'resource(' . get_string_last(print_r($handler, true), '#') . ')';
			}
			// unknow
			else{
				$content .=  print_r($v, true);
			}

			$content .= '<br>';
		}
		$content .= '</pre>';

		// hook display debug
		$this->list['debug_log'] = array('label' => 'System log', 'msg' => $content);
		$this->display['debug_log'] = true;
		$this->allow_display = true;
		die;
	}

	/**
	 * get time
	 * @param  float $start
	 * @param  float $end
	 */
	public function get_time($start, $end){
		return ($total = (float)($end - $start)) >= 1 ? $total . ' s' : $total*1000 . ' ms';
	}

	/**
	 * get size
	 * @param  float $size
	 */
	public function get_size($size){
		$unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
		return round(($size)/pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
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
		return \M::get('event')->expand('system.debug.expand.' . $name, $args, $this);
	}
}
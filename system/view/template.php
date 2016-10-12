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

namespace System\View;

class Template implements \System\Template{
	public
		$extension 		= '.php',		// file extension
		$cache_path 	= null,			// cache path
		$theme_path 	= null,			// theme path
		$view_path 		= null,			// view path
		$widget_path 	= array();		// widget path

	protected
		$current_path 	= null,			// current_path
		$block 			= null,			// block engine
		$data 			= array(),		// template variable
		$extend_deep 	= 0,			// extend deep
		$extend_raw 	= null;			// extend raw

	function __construct($args = null){
		// set properties
		foreach((array)$args as $k => $v){
			$this->{$k} = $v;
		}

		// load block engine
		$this->block = new Block;
	}

	/*
	 * expand method | load block
	 * @param  string $name
	 * @param  array $args
	 */
	function __call($name, $args){
		// for block
		if(preg_match('/^block_(.*?)$/i', $name, $m)){
			return $this->block->get($m[1]);
		}
		// for expand
		else{
			return \M::get('event')->expand('system.view.template.expand.' . $name, $args, $this);
		}
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
	 * get assign variable
	 * @param  string $name
	 * @param  $default
	 */
	public function get_var($name, $default = null){
		if(isset($this->data[$name])){
			return $this->data[$name];
		}

		return $default;
	}

	/**
	 * Display the content
	 * @param  string $view
	 * @param  array $args
	 */
	public function display($view, &$args){
		// create cache path
		if($this->cache_path && !get_readable($this->cache_path)){
			try{
				mkdir($this->cache_path, 0755, true);
			}catch(\Exception $e){
				\M::exception('System\View\Template : Failed to create cache folders...');
			}
		}

		// assign data
		$this->data =& $args;

		// load default extend
		$extend = array();
		\M::get('event')->change('system.view.template.on_set_extend', $extend);
		
		if($extend){
			foreach((array)$extend as $v){
				$this->extend($v);
			}
		}

		// set current path
		$this->current_path = $this->view_path;

		// load view
		ob_start();
			$data =& $this->data;
			include $view;
		$content = ob_get_clean();

		if(!$this->extend_raw){
			$this->load_extend($content);
		}else{
			$index = ($this->extend_deep-1);
			// remove trigger
			$content = preg_replace('/<\?php\s*echo\s*\\\\M\:\:get\(\'event\'\)\->trigger\(\'system\.view\.template\.block\.\w+' . $index . '\'\);\?>/ims', '', $content);
			\M::get('event')->hook_var('system.view.template.block.content' . $index, $content);
			$this->load_extend($this->extend_raw);
		}
	}

	/**
	 * extend view from template
	 * @param  string $file
	 */
	public function extend($file){
		if(($f = get_readable($this->get_extension($file))) || ($f = get_readable($file, $this->theme_path)) || ($f = get_readable($file, $this->view_path))){
			if(($path = substr($f, 0, strlen($this->view_path))) && $path == $this->view_path){
				$this->current_path = $this->view_path;
			}else{
				$this->current_path = substr($f, 0, strlen($f) - strlen(get_string_last($f, DS)));
			}
		}else{
			\M::exception('System\View\Template->extend(...) : Failed opening required %s', $file);
		}

		$this->extend_deep++;

		// load extend file
		ob_start();
		$data =& $this->data;
		require $f;
		$content = ob_get_clean();

		if($this->extend_deep <= 1){
			$this->extend_raw = $content;
		}else{
			\M::get('event')->hook_var('system.view.template.block.content' . ($this->extend_deep-2), $content);
			$this->extend_raw = $this->load_extend($this->extend_raw, false);
		}

		return $this;
	}

	/**
	 * load extend
	 * @param  string $content
	 * @param  boolean $render
	 */
	public function load_extend($content, $render = true){
		ob_start();
			$tmp = tempnam($this->cache_path, 'tpl');
			$handle = fopen($tmp, 'w');
			fwrite($handle, $content);
			fclose($handle);
			include $tmp;
			unlink($tmp);
		$content = ob_get_clean();

		if($render){
			echo $content;
		}else{
			return $content;
		}
	}

	/**
	 * add extend
	 * @param  string | array $args
	 */
	public function add_extend($args){
		\M::get('system.event')->hook('system.view.template.on_set_extend', function(&$extend) use($args){
			$extend = array_merge((array)$extend, (array)$args);
		});
	}

	/**
	 * remove extend
	 */
	public function remove_extend(){
		\M::get('event')->hook('system.view.template.on_set_extend', null, true);
	}

	/**
	 * get template extension
	 * @param  string &$file
	 */
	public function get_extension(&$file){
		// check extension
		$ext = get_string_last($file, '.');
		if(strcasecmp('.' . $ext, $this->extension) != 0){
			$file .= $this->extension;
		}

		return $file;
	}

	/**
	 * load other file
	 * @param  string | array $files
	 * @param  array $args
	 * @param  boolean $render
	 */
	public function load($files, $args = null, $render = true){
		$content = '';
		ob_start();
			$data =& $this->data;
			foreach((array)$files as $file){
				if(($f = get_readable($this->get_extension($file))) || ($f = get_readable($file, $this->current_path))){
					include $f;
				}else{
					\M::exception('System\View\Template->load(...) : Failed opening required %s', $file);
				}
			}
		$content .= ob_get_clean();

		if(!$render){
			return $content;
		}else{
			echo $content;
			return $this;
		}
	}

	/**
	 * widget
	 * @param  string|array $name
	 * @param  array $args
	 * @param  boolean $render
	 */
	public function widget($widgets, $args = null, $render = true){
		\M::get('event')->change('system.view.template.on_get_widget', $widgets, $args);

		$current_path = $this->current_path;
		$widgets = (array)$widgets;

		foreach($widgets as $k => $v){
			$this->get_extension($v);
			if(!($f = get_readable($v))){
				foreach($this->widget_path as $w){
					if($f = get_readable($v, $w)){
						break;
					}
				}
			}

			if(!$f){
				\M::exception('System\View\Template->widget(...) : Failed opening required %s', $v);
			}

			// auto load widget
			if(is_dir($f)){
				$f .= 'index' . $this->extension;
			}

			// set current path
			$this->current_path = substr($f, 0, strlen($f) - strlen(get_string_last($f, DS)));

			$widgets[$k] = $f;
		}

		$ret = $this->load($widgets, $args, false);
		$this->current_path = $current_path;

		if($render){
			echo $ret;
			return $this;
		}else{
			return $ret;
		}
	}

	/**
	 * css resource
	 * @param  string $file
	 * @param  array $attrs
	 * @param  boolean $ret
	 */
	public function css($file, $attrs = null, $ret = false){
		return $this->resource('css', $file, $attrs, $ret);
	}
	
	/**
	 * js resource
	 * @param  string $files
	 * @param  array $attr
	 * @param  boolean $ret
	 */
	public function js($file, $attrs = null, $ret = false){
		return $this->resource('js', $file, $attrs, $ret);
	}

	/**
	 * image resource
	 * @param  string $file
	 * @param  array $attrs
	 * @param  boolean $ret
	 */
	public function img($file, $attrs = null, $ret = false){
		return $this->resource('img', $file, $attrs, $ret);
	}

	/**
	 * resource
	 * @param  string $type
	 * @param  array $attrs
	 * @param  boolean $ret
	 */
	public function resource($type, $file, $attrs = null, $ret = false){
		if(!is_array($attrs)){
			//$ret = (bool)$attrs;
			$attrs = array();
		}

		// on_get_resource
		\M::get('event')->change('system.view.template.on_get_resource', $file, $type, $attrs);

		$attr = '';
		foreach($attrs as $k => $v){
			if(array_value_exist($k, array('href', 'src', 'rel'))){
				continue;
			}
			$attr .= ' ' . $k . '="' . $v . '"';
		}

		switch($type){
			case 'js': $res = '<script' . ' src="' . $file . '"' . $attr . '></script>'; break;
			case 'css': $res = '<link' . ' href="' . $file . '" rel="stylesheet"' . $attr . '/>'; break;
			case 'img': $res = '<img' . ' src="' . $file . '"' . $attr . ' />'; break;
			default: $res = ''; break;
		}

		if($ret){
			return $res;
		}else{
			echo $res;
		}

		return $this;
	}
}

class Block{
	public
		// block items
		$items = array();

	private
		// current block
		$current = null;

	/**
	 * get block
	 * @param  string $name
	 */
	public function get($name){
		$tpl =& \M::get('view')->get_template();
		$extend_deep = $tpl->get('extend_deep');

		// trigger block
		if($extend_deep > 0){
			$name .= $extend_deep - 1;
			echo "<?php echo \M::get('event')->trigger('system.view.template.block." . $name . "');?>";
		}

		// check block
		if(!array_key_exists($name, $this->items)){
			$this->items[$name] = '';
		}

		// set current block
		$this->current = $name;

		return $this;
	}

	/**
	 * set content
	 * @param  string $content
	 */
	public function html($content){
		$this->items[$this->current] = $content;
		\M::get('event')->hook_var('system.view.template.block.'. $this->current, $this->items[$this->current]);
		return $this;
	}

	/**
	 * append content
	 * @param  string $content
	 */
	public function append($content){
		if( is_string($content) ){
			$this->items[$this->current] .= $content;
			\M::get('event')->hook_var('system.view.template.block.'. $this->current, $this->items[$this->current]);
		}
		return $this;
	}

	/**
	 * prepend content
	 * @param  string $content
	 */
	public function prepend($content){
		$this->items[$this->current] = $content . $this->items[$this->current];
		\M::get('event')->hook_var('system.view.template.block.'. $this->current, $this->items[$this->current]);
		return $this;
	}

	/**
	 * load from another file
	 * @param  string | array $files
	 * @param  boolean $render
	 */
	public function load($files){
		$tpl =& \M::get('view')->get_template();
		foreach((array)$files as $f){
			$this->items[$this->current] .= $tpl->load($f, null, false);
		}
		\M::get('event')->hook_var('system.view.template.block.'. $this->current, $this->items[$this->current]);
		return $this;
	}

	/*
	 * expand method
	 * @param  string $name
	 * @param  array $args
	 */
	function __call($name, $args){
		return \M::get('event')->expand(strtolower('system.view.template.block.expand.' . $name), $args, $this);
	}
}
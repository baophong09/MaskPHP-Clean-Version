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

class DB{
	/**
	 * thirdparty driver
	 * @param  string $name
	 */
	public function driver($name){
		return \M::get('system.database.' . $name, array($this->{$name}));
	}

	/**
	 * expand method
	 * @param  string $name
	 * @param  array $args
	 */
	function __call($name, $args){
		return \M::get('event')->expand('system.db.expand.' . $name, $args, $this);
	}
}
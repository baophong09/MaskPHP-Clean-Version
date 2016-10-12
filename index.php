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

/**
 * turn off errors
 */
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL | E_STRICT);

/**
 * load core
 */
require_once getcwd() . '/system/define.php';
require_once APP_PATH . 'system/core.php';

/**
 * run application
 */
\M::get('router')->response();
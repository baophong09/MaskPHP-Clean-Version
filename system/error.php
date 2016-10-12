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

// error handler
set_error_handler('error_handler');
// exception handler
set_exception_handler('exception_handler');

// Checks for a fatal error, work around for set_error_handler not working on fatal errors.
$error = error_get_last();
error_handler($error['type'], $error['message'], $error['file'], $error['line']);

// Error handler, passes flow over the exception logger with new ErrorException.

function error_handler($num, $str, $file, $line, $context = NULL){
	exception_handler(new \ErrorException($str, 0, $num, $file, $line));
}

/**
 * Uncaught exception handler.
 */
function exception_handler(\Exception $e){
	// trigger error here
	echo 'trigger error';
}
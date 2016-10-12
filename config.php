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

// datetime
date_default_timezone_set('Asia/Bangkok');

/********************************************************************************
 * SESSION                                                                      *
 ********************************************************************************/
\M::get('session')->timeout = 30;
/********************************************************************************
 * CRYPTION KEY                                                                 *
 ********************************************************************************/
\M::get('crypt', md5("simpleCryptHere - Please replace me"));

/********************************************************************************
 * DEBUG                                                                        *
 ********************************************************************************/
\M::get('debug')->token['debug_token']       = true;
\M::get('debug')->display['memory_usage']    = true;
\M::get('debug')->display['excution_time']   = true;
\M::get('debug')->display['include_file']    = true;
\M::get('debug')->display['sql_query']       = true;

/********************************************************************************
 * DEBUG                                                                        *
 ********************************************************************************/
\M::import(APP_PATH . 'library/pagination.php');

/********************************************************************************
 * ROUTER                                                                       *
 ********************************************************************************/
\M::get('router')->default_module    = 'helloworld';
\M::get('router')->default_url       = 'index/index';
\M::get('router')->error_url         = 'error/404';
\M::get('router')->url_extension     = array('.html', '.htm');

/********************************************************************************
 * VIEW                                                                         *
 ********************************************************************************/
\M::get('view')->extension 	= '.php';
\M::get('view')->template 	= 'template';

/********************************************************************************
 * DATABASE                                                                     *
 ********************************************************************************/

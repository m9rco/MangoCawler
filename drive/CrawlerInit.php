<?php
/*
+--------------------------------------------------------------------------
| 申明驱动程序
+--------------------------------------------------------------------------
| @author: pushaowei @date: 2017/4/29 
+--------------------------------------------------------------------------
*/

// 产生时钟云，解决php7下面ctrl+c无法停止bug
declare(ticks = 1);

// 大文件处理
libxml_use_internal_errors(true);

// 永不超时
ini_set('max_execution_time', 0);
set_time_limit(0);

// 多来点内存
intval(ini_get("memory_limit")) < 1024 && ini_set('memory_limit', '1024M');

/*---------------------------数据库配置----------------------------------*/

define('M_DB_HOST'	 	, 	'127.0.0.1');
define('M_DB_NAME'	 	, 	'banana');
define('M_DB_USER'	 	, 	'root');
define('M_DB_PWD' 	 	, 	'ilovechina');
define('M_DB_TABLE' 	, 	'damai_venue');

/*---------------------------Swoole 配置----------------------------------*/

define('M_SWOOLE_HOST'	, 	'127.0.0.1');
define('M_SWOOLE_PORT'	, 	'9632');
define('M_SWOOLE_TIME'	, 	'10');


/*---------------------------注册自动加载----------------------------------*/

spl_autoload_register(
	function( $class ){
	$filename = dirname(__FILE__).'/'.str_replace('\\','/',$class).'.php';
	file_exists($filename) ? require_once $filename : die('文件'.$filename.'不存在'.PHP_EOL);
});


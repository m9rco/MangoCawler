<?php
/*
+--------------------------------------------------------------------------
| 申明驱动程序
+--------------------------------------------------------------------------
| @author: pushaowei @date: 2017/4/29 
+--------------------------------------------------------------------------
*/

require_once dirname(__FILE__).'/../vendor/autoload.php';

/**
 * [$filename 需要爬的列表]
 * @var [type]
 */
define('M_CRAWLER_URL', 'https://venue.damai.cn/search.aspx?cityID=0&k=0&keyword=&pageIndex=\d');



spl_autoload_register(function($class){
	$filename = dirname(__FILE__).'/'.str_replace('\\','/',$class).'.php';
	file_exists($filename) ? require_once $filename : die('文件'.$filename.'不存在'.PHP_EOL);
});


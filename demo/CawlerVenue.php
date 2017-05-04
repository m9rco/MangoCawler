<?php
// 注册自动加载
require dirname(__DIR__).'/MangoCawler.php';

/*
+--------------------------------------------------------------------------
| MangoCawler 场馆列表爬取
+--------------------------------------------------------------------------
| @author: pushaowei @date: 2017/4/29 
+--------------------------------------------------------------------------
*/
$mangoCawler = new MangoCawler([	
	'init' => [
			'process_num' => '5',
			'crawler_url' => 'https://venue.damai.cn/search.aspx?cityID=0&k=0&keyword=&pageIndex=\d',
			'page_num'	  => '181',
	],	
	'dom'  => [
			['list_href' =>	'//div[@class="v_mod"]//ul//li//span//a/@href'],
			['list_text' =>	'//div[@class="v_mod"]//ul//li//span//h3//a/text()'],
	],
]);

$mangoCawler->start();
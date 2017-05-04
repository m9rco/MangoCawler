<?php
// 注册自动加载
require_once dirname(__FILE__).'/drive/CrawlerInit.php';

// swoole
use worker\Client;
use worker\Server;

/*
+--------------------------------------------------------------------------
| MangoCawler 基于Swoole 实现的多进程爬虫方案
+--------------------------------------------------------------------------
| @author: pushaowei @date: 2017/4/29
+--------------------------------------------------------------------------
*/
 class MangoCawler implements StartInterface
 {
	/**
	 * [$_url UrlPatch]
	 * @var [type]
	 */
	private $_url;

	/**
	 * [$_process 进程集合]
	 * @var [type]
	 */
	private $_process;
	/**
	 * [$_worker 工作进程]
	 * @var [type]
	 */
	private $_worker;

	/**
	 * [$_pages 页码]
	 * @var [type]
	 */
	private $_pages;

	/**
	 * [$_page_num 页数]
	 * @var [type]
	 */
	private $_page_num;

	/**
	 * [$_dom Dom集合]
	 * @var [type]
	 */
	private $_dom;

	/**
	 * [$_dom_obj dom对象]
	 * @var [type]
	 */
	private $_dom_obj;

	/**
	 * [__construct 初始化]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-05-04T10:05:57+0800
	 */
	public function __construct( array $config ){

		//--------------------------------------------------------------------------------
        // 运行前验证
        //--------------------------------------------------------------------------------

        // 检查PHP版本
        if (version_compare(PHP_VERSION, '5.3.0', 'lt')) {
            exit('PHP 5.3+ is required, currently installed version is: ' . phpversion());
        }

        // 检查CURL扩展
        if(!function_exists('curl_init')){
            exit("The curl extension was not found");
        }

		if(!extension_loaded('swoole')){
			exit("Swoole not Install");
		}
		
		//--------------------------------------------------------------------------------
        // 验证配置并加入配置
        //--------------------------------------------------------------------------------

		try{
			if( $this->checkConfig( $config ) ) {
				$this->_worker 	  = $config['init']['process_num'] ;				
				$this->_page_num  = $config['init']['page_num']	   ;
				$this->_url    	  = $config['init']['crawler_url'] ;
				$this->_dom       = $config['dom']				   ;
			}
		}catch(Exception $e){
			exit( $e->getMessage() );
		}
	}

	/**
	 * [start 开始爬取]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-05-04T11:06:50+0800
	 * @return                              [type] [description]
	 */
	public function start(){
				
		//--------------------------------------------------------------------------------
        // 初始化 Dom || 及Xpath 类
        //--------------------------------------------------------------------------------
        
		$this->_dom_obj = new \DOMDocument;
		// We don't want to bother with white spaces
		$this->_dom_obj->preserveWhiteSpace = false;

		//--------------------------------------------------------------------------------
        // 开启各自进程准备干活
        //--------------------------------------------------------------------------------
     	$this->initProcess();   
	}


	/**
	 * [initProcess 初始化多进程]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-05-02T13:20:45+0800
	 * @return                              [type] [description]
	 */
	public function initProcess(){

		// Start processing
		for ($j = 0; $j  < $this->_page_num; $j ++) {
			$this->_pages[] = str_replace(['\d','\d+'], $j, $this->_url);
		}

		// pages distribution
	    $count = count ( $this->_pages );
	    $round = round ( $count / $this->_worker);
	    for ( $i = 0; $i <  $this->_worker; $i ++ ) {
	        $return_arr[$i] = array_slice ( $this->_pages , $i * $round, $round );
	    }

	    $this->_pages = $return_arr;
		for ($i = 0; $i  < $this->_worker; $i ++) {
       		$process = new \swoole_process([$this,'initWorker'],false);
			$pid	 = $process->start();
			$process->write( $i );
        	$this->_process[$pid] = $process;
        }
        foreach( $this->_process as $_process ){
        	echo "进程已完成工作：\t".$_process->read().PHP_EOL;
        }
	}

	/**
	 * [initWorker 各进程完成各自请求]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-05-02T13:21:09+0800
	 * @return                              [type] [description]
	 */
	public function initWorker( \swoole_process $worker){

	    $i     =  $worker->read();
	    $array =  isset( $this->_pages[$i] ) ? $this->_pages[$i] : [];

	    if( !empty( $array) ){
	    	$temp = $this->initCurl( $array );
	    	foreach( $temp as $value ){
	    		$this->getContent( $value );
	    	}
			$worker->write( $worker->pid.'-- It‘s ok');
		}
	}

	/**
	 * [initCurl CURL]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-05-02T15:48:39+0800
	 * @param                               [type] $url [description]
	 * @return                              [type]      [description]
	 */
	private function initCurl( $nodes ){

        $mh = curl_multi_init();
        $curl_array = array();
        foreach($nodes as $i => $url){
            $curl_array[$i] = curl_init($url);
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($mh, $curl_array[$i]);
        }
        $running = NULL;
        do {
            // usleep(10000);
            curl_multi_exec($mh,$running);
        } while($running > 0);

        $res = array();
        foreach($nodes as $i => $url)
        {
            $res[] = curl_multi_getcontent($curl_array[$i]);
        }

        foreach($nodes as $i => $url){
            curl_multi_remove_handle($mh, $curl_array[$i]);
        }
        curl_multi_close($mh);
        return $res;
	}

	/**
	 * [getContent 获取页面重要信息]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-04-29T22:08:44+0800
	 * @return                              [type] [description]
	 */
	private function getContent(  $content ){
		$this->_dom_obj->loadHTML($content);
		$xpath 	  = new \DOMXPath( $this->_dom_obj );
		// Compare the duplicate data
		$container = $container_href = $container_title = $container_dom = $_data = [];

		// We starts from the root element
		$href  = $xpath->query('//div[@class="v_mod"]//ul//li//span//a/@href');
		$title = $xpath->query('//div[@class="v_mod"]//ul//li//span//p[2]');

		// 自动化的时候在去弄吧
		// foreach( $this->_dom as $dom_val ){
		// 	$dom_key = key($dom_val);
		// 	$container_dom[$dom_key] = $xpath->query($dom_val[$dom_key]);	
		// }

		if( !empty( $href->length ) && !empty( $title->length )){
		  	// href
		  	foreach( $href as $element ){
		  		if( isset( $container[$element->nodeValue] )) continue;
		  		$container[$element->nodeValue] = true;
		  		$container_href[]['url'] = $element->nodeValue;
		  	}

		  	// title
		  	// foreach ($title as $element_title) {
		  	// 	$value = $offen = $matches = '';
		  	// 	// @preg_match('#\[.*\]#', $element_title->nodeValue, $matches);
	  		// 	$offen = str_replace(['[',']'], '', $matches)[0];
    		// 	$value = explode('-',$offen) ;
	  		// 	$container_title[] = [
	  		// 			'province' => $value[0],
	  		// 			'city' 	   => $value[1],
	  		// 		];
		  	// }
		  
		  	// venue
		  	foreach( $title as $element_title ){
		  		$container_title[]['type'] = str_replace(['场馆类型','：',''], '', $element_title->nodeValue);
		  	}

		  	$this->InstertDatabase( array_replace_recursive($container_href,$container_title) );
		}
	}
	/**
	 * [InstertDatabase 插入数据库]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-04-29T23:30:16+0800
	 * @param                               [type] $data [description]
	 */
	private function InstertDatabase( array $data ){
		new Client( $data );
	}

	/**
	 * [checkConfig 验证配置文件]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-05-04T10:50:14+0800
	 * @param                               array  $config [description]
	 * @return                              [type]         [description]
	 */
	private function checkConfig( array $config ){
		// 暂时假装写一个吧
		if( !isset( $config['init'] ) || !isset( $config['dom'] )) 
			throw new Exception("Please pass in 'init' and 'dom' ");

		// 一堆验证
		return true;
	}
}
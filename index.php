<?php
// 注册自动加载
require_once dirname(__FILE__).'/drive/CrawlerInit.php';

// swoole
use worker\Client;
use worker\Server;
// use GuzzleHttp\Client as GuzzleClient;
// use GuzzleHttp\Cookie\CookieJar;
// use GuzzleHttp\Pool;
// use GuzzleHttp\Exception\RequestException;
// use GuzzleHttp\Psr7\Request;

/*
+--------------------------------------------------------------------------
| MangoCawler 基于Swoole 实现的多进程爬虫方案
+--------------------------------------------------------------------------
| @author: pushaowei @date: 2017/4/29 
+--------------------------------------------------------------------------
*/
 class MangoCawler implements StartInterface{

	/**
	 * [$_Guzzle 客户端实例]
	 * @var [type]
	 */
	private $_Guzzle;

	/**
	 * [$_Url UrlPatch]
	 * @var [type]
	 */
	private $_Url;

	/**
	 * [$_Client 连接池]
	 * @var [type]
	 */
	private $_Client;

	/**
	 * [$_Worker 工作进程]
	 * @var [type]
	 */
	private $_Worker;

	/**
	 * [$_Pages 页码]
	 * @var [type]
	 */
	private $_Pages = [];

	const M_CAWLER_PAGE  = 200;
	const M_CAWLER_WORKE = 20;



	public function __construct()
	{
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
        // 各容器就位
        //--------------------------------------------------------------------------------
        
        // 产生时钟云，解决php7下面ctrl+c无法停止bug
        declare(ticks = 1);

        // 大文件处理
		libxml_use_internal_errors(true);

		// 多分配点内存
		ini_set("memory_limit", "101224M");

        // 开始
		// $this->_Guzzle   = new GuzzleClient();

		// echo $this->displayUi();

		$this->getConfig();

		// 初始化同步客户端
		// $this->initClient();
		
		// 初始化多进程
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
		for ($j = 0; $j  < self::M_CAWLER_PAGE; $j ++) { 
			// $this->_Pages[] = 'http://test.inner.mosh.cn:30330/?s='.$j;  
			$this->_Pages[] = str_replace(['\d','\d+'], $j, M_CRAWLER_URL);
		}
		$this->_Pages = array_chunk( $this->_Pages,self::M_CAWLER_WORKE);

		for ($i = 0; $i  < self::M_CAWLER_WORKE; $i ++) { 
       		$process = new swoole_process([$this,'initWorker'],false);
			$pid = $process->start();
			$process->write( $i );
        	$this->_Worker[$pid] = $process;
        }
        foreach( $this->_Worker as $_worker ){
        	echo "进程已完成：\t".$_worker->read().PHP_EOL;
        }
	}

	/**
	 * [initWorker 各进程完成各自请求]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-05-02T13:21:09+0800
	 * @return                              [type] [description]
	 */
	public function initWorker( swoole_process $worker){
	    $i  = $worker->read();
	    $array =  isset( $this->_Pages[$i] ) ? $this->_Pages[$i] : [];
	    $multi_ch = curl_multi_init(); 
	    if( !empty( $array) ){
	    	$temp = $this->initCurl($array);
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
	public function initCurl( $nodes ){
		// $ch = curl_init();
		// curl_setopt_array($ch, [
		// 	CURLOPT_URL 		   => $url,
		// 	CURLOPT_RETURNTRANSFER => true,
		// 	CURLOPT_CONNECTTIMEOUT => 10
		// ]);
		// $dxycontent = curl_exec($ch); 
		// curl_close($ch);
		// echo $dxycontent;
		// $this->getContent( $dxycontent );	
        $mh = curl_multi_init(); 
        $curl_array = array(); 	
        foreach($nodes as $i => $url){ 
            $curl_array[$i] = curl_init($url); 
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true); 
            curl_multi_add_handle($mh, $curl_array[$i]); 
        } 
        $running = NULL; 
        do { 
            usleep(10000); 
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
     * [initClient 初始化客户端]
     * @author 		Shaowei Pu <pushaowei@sporte.cn>
     * @CreateTime	2017-04-29T21:17:43+0800
     * @return                              [type] [description]
     */
	public function initClient(){

		$requests = function ($total) {
		    for ($i = 1; $i <= $total; $i++) {
		        yield new Request('GET', str_replace(['\d','\d+'], $i, M_CRAWLER_URL));
		    }
		};

		$pool = new Pool($this->_Guzzle , $requests(120), [
		    'concurrency' => 0,
		   		 'fulfilled' => function ($response, $index) {
					// 接力给解析页面的类
		   		 	if( $response ){
						self::getContent($response->getBody()->getContents());
		   		 	} 
		  	  },
		   		 'rejected' => function ($response, $index) {
		   		 	var_dump($response);
		    },
		]);

		// Initiate the transfers and create a promise
		$promise = $pool->promise();

		// Force the pool of requests to complete.
		$promise->wait();
	}

	/**
	 * [getConfig 获取各项配置]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-04-29T21:32:25+0800
	 * @return                              [type] [description]
	 */
	public function getConfig(){
		$this->_Url    = M_CRAWLER_URL;
	}

	/**
	 * [getContent 获取页面重要信息]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-04-29T22:08:44+0800
	 * @return                              [type] [description]
	 */
	public function getContent( $content )
	{
		$doc = new DOMDocument;

		// We don't want to bother with white spaces
		$doc->preserveWhiteSpace = false;

		$doc->loadHTML($content);
		$xpath = new DOMXPath($doc);


		// We starts from the root element
		$href  = $xpath->query('//div[@class="v_mod"]//ul//li//span//a/@href');
		$title = $xpath->query('//div[@class="v_mod"]//ul//li//span//h3//a/text()');

		// Compare the duplicate data
		$container = $container_href = $container_title = $_data = [];

		if( !empty( $href->length ) && !empty( $title->length )){
		  	// href
		  	foreach( $href as $element ){
		  		if( isset( $container[$element->nodeValue] )) continue;
		  		$container[$element->nodeValue] = true;
		  		$container_href[]['url'] = $element->nodeValue;
		  	}

		  	// title
		  	foreach ($title as $element_title) {
		  		$value = $offen = $matches = '';
		  		@preg_match('#\[.*\]#', $element_title->nodeValue, $matches);
	  			$offen = str_replace(['[',']'], '', $matches)[0];
            	$value = explode('-',$offen) ; 
	  			$container_title[] = [
	  					'province' => $value[0],
	  					'city' 	   => $value[1],
	  				];
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
	public function InstertDatabase( array $data ){
		new Client($data);
	}

	/**
	 * [displayUi 视图输出]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2017-04-29T21:17:28+0800
	 * @return                              [type] [description]
	 */
    public function displayUi()
    {
        $display_str = "---------------------------\033[47;30m MangoCawler Status \033[0m--------------------------\n";
        $display_str .= "\033[47;30mfind pages\033[0m". str_pad('', 16-strlen('find pages')). 
            "\033[47;30mqueue\033[0m". str_pad('', 14-strlen('queue')). 
            "\033[47;30mcollected\033[0m". str_pad('', 15-strlen('collected')). 
            "\033[47;30mfields\033[0m". str_pad('', 15-strlen('fields')). 
            "\033[47;30mdepth\033[0m". str_pad('', 12-strlen('depth')). 
            "\n";

        $collect   = 'pages';
        $collected = 'queue';
        $queue     = 'collected';
        $fields    = 'fields';
        $depth     = 'depth';

        $display_str .= str_pad($collect, 16);
        $display_str .= str_pad($queue, 14);
        $display_str .= str_pad($collected, 15);
        $display_str .= str_pad($fields, 15);
        $display_str .= str_pad($depth, 12);
        $display_str .= "\n";
        return $display_str;
    }
}

new MangoCawler(); 
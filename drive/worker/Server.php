<?php
namespace worker;
require_once dirname(__DIR__).'/CrawlerInit.php';

use db\PdoHelper;

class Server{
	private $serv;
	private $pdo;
	public function __construct(){
		$this->serv = new \swoole_server(M_SWOOLE_HOST,M_SWOOLE_PORT);
		$this->serv ->set([
			'open_eof_split'	       => true,
			'worker_num'               => 20,
			'max_request'              => 5000,
			'dispatch_mode'            => 3,
			'open_length_check'        => 1,
			'package_length_type'      => 'N',
			'package_body_offset'      => 16,
			'package_length_offset'    => 0,
			'heartbeat_check_interval' => 30,
			'heartbeat_idle_time'      => 60,
			'daemonize'                => false,    //守护进程改成true
			'package_eof'              => "\r\n",
			'task_worker_num'		   => '20',
			]);
		$this->serv->on('WorkerStart', array($this, 'onWorkerStart'));
		$this->serv->on('Connect', array($this, 'onConnect'));
		$this->serv->on('Receive', array($this, 'onReceive'));
		$this->serv->on('Close', array($this, 'onClose'));
		// bind callback
		$this->serv->on('Task', array($this, 'onTask'));
		$this->serv->on('Finish', array($this, 'onFinish'));
		$this->serv->start();
	}

	/**
	 * [onConnect 创建连接]
	 * @param  [type] $serv    [description]
	 * @param  [type] $fd      [description]
	 * @param  [type] $from_id [description]
	 * @return [type]          [description]
	 */
	public function onConnect($serv, $fd ,$from_id ){
		echo "Client {$fd} connect \n";
	}

	/**
	 * [onWorkerStart 创建pdo连接,woker进程创建之初被调用]
	 * @param  [type] $serv      [description]
	 * @param  [type] $worker_id [description]
	 * @return [type]            [description]
	 */
	public function onWorkerStart($serv, $worker_id){
		// require_once dirname(__FILE__).'/../db/PdoHelper.php';
		$this->pdo = PdoHelper::getInstance();
	}

	/**
	 * [onReceive 这里收到客户端的请求]
	 * @param  swoole_server $serv    [description]
	 * @param  [type]        $fd      [description]
	 * @param  [type]        $from_id [description]
	 * @param  [type]        $data    [description]
	 * @return [type]                 [description]
	 */
	public function  onReceive ( $serv ,$fd , $from_id ,$data ){
		$task= [
			'data' 	 =>	json_decode(explode("\r\n", $data)[0],true),
			'fd' 	 => $fd //描述符
		];
		$serv->task(json_encode($task));
	}

	/**
	 * [onTask 处理sql,接收客户端的$data]
	 * @param  [type] $serv    [description]
	 * @param  [type] $task_id [description]
	 * @param  [type] $from_id [description]
	 * @param  [type] $data    [description]
	 * @return [type]          [description]
	 */
	public function onTask($serv,$task_id,$from_id,$data){
		try{
			$data = json_decode($data,true);
			 $insert_id = $this->pdo->table(M_DB_TABLE)
			 						->insertMore( $data['data']);
			 echo $insert_id.'insert succed'.PHP_EOL;
			// $serv->send($data['fd'],'insert succed'); //将返回结果给客户端
			return true;
		}catch(\PDOException $e){
			var_dump($e->getMessage());
			return false;
		}
	}
	/**
	 * [onFinish description]
	 * @param  [type] $serv    [description]
	 * @param  [type] $task_id [description]
	 * @param  [type] $data    [description]
	 * @return [type]          [description]
	 */
	public function onFinish($serv,$task_id,$data){
		// $serv->send( $data['fd'],'$data');
		// var_dump($task_id);
	}
	/**
	 * @param $serv
	 * @param $fd
	 * @param $from_id
	 */
	public function onClose( $serv, $fd, $from_id ) {
		echo "Client {$fd} close connection \n";
	}
}
new Server();
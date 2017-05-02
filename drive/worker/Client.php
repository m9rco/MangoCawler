<?php
namespace worker;
class Client
{
  private $client;
  private $time;
  
  public function __construct($data) {

    $this->client = new \swoole_client(SWOOLE_TCP);
    $this->client->set([
        'open_length_check'     => true,
        'package_max_length'    => 2097152,
        'package_length_type'   => 'N',
        'package_body_offset'   => 16,
        'package_length_offset' => 0,
        'open_eof_check'        => true,
        'package_eof'           => "\r\n",
        'buffer_output_size'    => 32 * 1024 *1024,
         'pipe_buffer_size' => 32 * 1024 *1024, //必须为数字

    ]);
    // $this->client->on('Connect', array($this, 'onConnect'));
    // $this->client->on('Receive', array($this, 'onReceive'));
    // $this->client->on('Close', arra/y($this, 'onClose'));
    // $this->client->on('Error', array($this, 'onError'));
    $this->connect();
    $this->sendData( json_encode( $data )."\r\n" );
    // file_put_contents('/home/pushaowei/debug', var_export([__FILE__,str_length()]));
  }
  
  public function recv(){
    return $this->client->recv();
  }

  public function connect() {
    $fp = $this->client->connect("127.0.0.1", 9632 , 10);
    if( !$fp ) {
      echo "Error: {$fp->errMsg}[{$fp->errCode}]\n";
      return;
    }
  }
  public function onReceive( $cli, $data ) {

  }
  public function onConnect( $cli) {
    $cli->send("Get");
    // $this->time = time();
  }
  public function onClose( $cli) {
      echo "Client close connection\n";
  }
  public function onError() {
  }
  public function sendData($data) {
    $this->client->send( $data );
    // $this->connect();
  }
  public function isConnected() {
    return $this->client->isConnected();
  }
}

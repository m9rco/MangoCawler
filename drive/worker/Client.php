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
        'pipe_buffer_size'      => 32 * 1024 *1024, 

    ]);
    $this->connect();
    $this->sendData( json_encode( $data )."\r\n" );
  }
  
  public function recv(){
    return $this->client->recv();
  }

  public function connect() {
    $fp = $this->client->connect( M_SWOOLE_HOST , M_SWOOLE_PORT , M_SWOOLE_TIME);
    if( !$fp ) {
      echo "Error: {$fp->errMsg}[{$fp->errCode}]\n";
      return;
    }
  }
  
  public function onReceive( $cli, $data ) {

  }
  public function onConnect( $cli) {
    $cli->send("Get");
  }
  public function onClose( $cli) {
      echo "Client close connection\n";
  }
  public function onError() {
  }
  public function sendData($data) {
    $this->client->send( $data );
  }
  public function isConnected() {
    return $this->client->isConnected();
  }
}

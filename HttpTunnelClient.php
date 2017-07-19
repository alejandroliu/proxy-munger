<?php
class HttpTunnelClient extends HttpSocket {
  protected $client;
  protected $descr;
  public function __construct($conn,$http_host,$proxy_addr,$proxy_port,$target,$target_port,$verb,$ssl=FALSE) {
    //echo __FILE__.','.__LINE__.' ('.__CLASS__.'::'.__METHOD__.')'.PHP_EOL;//DEBUG
    $this->client = $conn;
    try {
      $proxy = NetIO::new_client($proxy_addr,$proxy_port,$ssl);
    } catch (Exception $e) {
      Logger::error('Error connecting to web proxy '.$proxy_addr.','.$proxy_port.' ('.$e->getMessage().')');
      return;
    }
    //echo __FILE__.','.__LINE__.' ('.__CLASS__.'::'.__METHOD__.')'.PHP_EOL;//DEBUG
    parent::__construct($proxy);
    $http_command = strtr($verb,['%h'=>$target,'%p'=>$target_port]);
    //echo($http_command.PHP_EOL);//DEBUG
    $this->send_message($http_command.' HTTP/1.1', [
				'Host' => $http_host,
				'Connection' => 'keep-alive',
				'Content-Type' => 'application/octet-stream',
				'Cache-Control' => 'no-cache',
			]);
    //echo __FILE__.','.__LINE__.' ('.__CLASS__.'::'.__METHOD__.')'.PHP_EOL;//DEBUG
    $this->descr = [
      '[http_host]' => $http_host,
      '[proxy_addr]' => $proxy_addr,
      '[proxy_port]' => $proxy_port,
      '[target]' => $target,
      '[port]' => $target_port,
      '[cmd]' => $verb,
      '[ssl]' => $ssl,
    ];
    //echo __FILE__.','.__LINE__.' ('.__CLASS__.'::'.__METHOD__.')'.PHP_EOL;//DEBUG
  }
  public function handle_request($main,$conn,$hdr,$data) {
    // Evaluate request...
    //echo __FILE__.','.__LINE__.' ('.__CLASS__.'::'.__METHOD__.')'.PHP_EOL;//DEBUG
    $tok = preg_split('/\s+/',$hdr,3);
    if (count($tok) >= 2) {
      if ($tok[1]{0} == '2') { // Succesful HTTP request...
	Logger::info(strtr('Using [http_host]([proxy_addr],[proxy_port]) proxy to [target],[port]',$this->descr));
	//echo($data);//DEBUG
	NetIO::write($this->client,$data);
	new SocketPump($this->client,$conn);
	new SocketPump($conn,$this->client);
	return;
      }
    }
    //echo($hdr.PHP_EOL);//DEBUG
    Logger::error(strtr('Failed proxy using [http_host]([proxy_addr],[proxy_port]) for [target],[port]',$this->descr));
    NetIO::close($this->client); // Error, abort client connection...
    NetIO::close($conn);
  }
}

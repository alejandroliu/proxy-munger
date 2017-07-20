<?php
/**
 * HTTP tunnel client
 * @package proxy_munger
 */
 
/**
 * Class to make use of a HTTP tunnel
 */
class HttpTunnelClient extends HttpSocket {
  /** Stores the incoming socket */
  protected $client;
  /** array containing metadata about this connection, used for error reporting */
  protected $descr;
  /**
   * Constructor
   * @param rsrc	$conn - incomming socket
   * @param str	$http_host - host name to use in the HTTP header
   * @param str	$proxy_addr - address to connect to
   * @param int	$proxy_port - port to connect to
   * @param str	$target - Target system we would like to connect to
   * @param int	$target_port - Target port we would like to connect to
   * @param str	$verb - HTTP verb to use, defaults to 'CONNECT %h:%p'
   * @param bool	$ssl - Defaults to FALSE, if TRUE, will use https
   */
  public function __construct($conn,$http_host,$proxy_addr,$proxy_port,$target,$target_port,$verb='CONNECT %h:%p',$ssl=FALSE) {
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
  /**
   * Callback to handle response
   * 
   * This is called after the proxy server has send a complete
   * HTTP response.  It will set-up the socket pumps to copy the data over.
   *
   * @param MainLoop	$main - Main Loop object
   * @param rsrc	$conn - Resource socket
   * @param str	$hdr - Header text
   * @param str	$data - Body text
   * @uses SocketPump
   */
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

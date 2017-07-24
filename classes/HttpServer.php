<?php
/**
 * HTTP server
 * @package proxy_munger
 */
 
/**
 * Class that implements Http Server
 */
class HttpServer extends HttpSocket {
  /** Routes configuration */
  public $routes;
  /** Default error message */
  static public $errmsg = <<<EOS
<!DOCTYPE html>
<html>
 <meta charset=utf-8>
 <title>%t</title>
 <h1>%t</h1><pre>%m</pre>
</html>
EOS;
  /** 
   * Constructor
   * 
   * Create an Http Server
   * 
   * @param rsrc	$conn - Incoming socket
   * @param array	$routes - Configured routes
   */
  public function __construct($conn,$routes) {
    if ($routes === NULL) Logger::fatal('Missing route configuration');
    $this->routes = $routes;
    parent::__construct($conn);
  }
  /**
   * Route matching function
   * 
   * @param str	$re - Route to test
   * @param str	$address - HTTP command to test
   */
  static public function check_route($re,$address) {
    if (!$re) return TRUE;
    if (substr($re,0,1) == '/' && substr($re,-1,1) == '/')
	return preg_match($re,$address);
    return fnmatch($re,$address);
  }
  /**
   * Handle an incoming request
   * 
   * @param MainLoop	$main - Main Loop object
   * @param rsrc	$conn - Incoming connection
   * @param str	$hdr - header text
   * @param str	$data - request body text
   */
  public function handle_request($main,$conn,$hdr,$data) {
    // Evaluate request...
    foreach ($this->routes as $re => $cb) {
      if (self::check_route($re,$hdr)) {
	$cb($this,$re,$hdr,$data);
	return;
      }
    }
    Logger::fatal('Misconfigured routes');
  }
  /**
   * Utility function to send HTTP reply
   * 
   * @param str	$code - HTTP return code
   * @param array	$headers - HTTP headers
   * @param str	$body - Body of message
   * 
   */
  public function send_reply($code,$headers,$body = '') {
    $this->send_message('HTTP/1.1 '.$code,$headers,$body);
  }
  /**
   * Utility function to send an HTML formated reply
   * 
   * @param str	$html - HTML text to send
   * @param str	$code - Return code (defaults to `200 OK`)
   */
  public function send_html($html, $code = '200 OK') {
    //echo __FILE__.','.__LINE__.PHP_EOL;//DEBUG
    $this->send_reply($code,[
		'Content-Type' => 'text/html;charset=utf-8',
		'Server' => 'PHP '.phpversion(),
	], $html);
  }
  /** End transaction */
  public function end() {
    NetIO::close($this->get_socket());
  }
  /**
   * Utility function to send an HTML formatted error message
   * 
   * @param str	$msg - Plain text message
   * @param str	$code - Error code (defaults to `500 ERROR`)
   * @param str	$title - Message title
   */
  public function send_error($msg,$code = '500 ERROR',$title='ERROR') {
    //echo __FILE__.','.__LINE__.PHP_EOL;//DEBUG
    $this->send_html(strtr(self::$errmsg,[
		'%m'=>htmlentities($msg),
		'%t'=>htmlentities($title),
		]), $code);
    Logger::warning($title.' '.$msg.' ('.NetIO::get_peername($this->get_socket()).')');
    $this->end();
  }
}  

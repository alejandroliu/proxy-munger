<?php
class HttpServer extends HttpSocket {
  public $routes;
  static public $errmsg = <<<EOS
<!DOCTYPE html>
<html>
 <meta charset=utf-8>
 <title>%t</title>
 <h1>%t</h1><pre>%m</pre>
</html>
EOS;
  public function __construct($conn,$routes) {
    if ($routes === NULL) Logger::fatal('Missing route configuration');
    $this->routes = $routes;
    parent::__construct($conn);
  }
  static public function check_route($re,$address) {
    if (!$re) return TRUE;
    if (substr($re,0,1) == '/' && substr($re,-1,1) == '/')
	return preg_match($re,$address);
    return fnmatch($re,$address);
  }
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
  public function send_reply($code,$headers,$body = '') {
    $this->send_message('HTTP/1.1 '.$code,$headers,$body);
  }
  public function send_html($html, $code = '200 OK') {
    //echo __FILE__.','.__LINE__.PHP_EOL;//DEBUG
    $this->send_reply($code,[
		'Content-Type' => 'text/html;charset=utf-8',
		'Server' => 'PHP '.phpversion(),
	], $html);
  }
  public function end() {
    NetIO::close($this->get_socket());
  }
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

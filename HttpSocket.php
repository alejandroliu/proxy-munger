<?php
abstract class HttpSocket extends BaseSocket {
  protected $request;
  
  abstract public function handle_request($main,$conn,$hdr,$data);
  public function __construct($conn) {
    $this->request = '';
    parent::__construct($conn);
    MainLoop::inst()->register_socket($conn,[$this,'recv']);
  }
  public function recv($main,$conn) {
    $off = strlen($this->request);
    if ($off > 4) $off - 4;
    $in = NetIO::read($conn);
    if ($in === FALSE) {
      Logger::error('HttpSocket, error reading socket from '.NetIO::get_peername($conn));
      $main->unregister_socket($conn);
      NetIO::close($conn);
      return;
    }
    $this->request .= $in;
    if (($pos = strpos($this->request,"\r\n\r\n",$off)) === FALSE) return;
    
    $main->unregister_socket($conn);

    $hdr = substr($this->request,0,$pos);
    $data = substr($this->request,$pos+4);
    
    $this->handle_request($main,$conn,$hdr,$data);
  }
  static public function parse_header($hdr) {
    // Parse header...
    $h = [];
    foreach (explode("\r\n",$hdr) as $i) {
      if (count($h) == 0) {
	$h[''] = preg_split('/\s+/',$i);
      } else {
	$i = preg_split('/:\s+/',$i,2);
	if (count($i) == 0) continue;
	if (count($i) == 1) {
	  if (!isset($last)) {
	    $h[$last] .= $i[0];
	  } else {
	    $h[''][] = trim($i[0]);
	  }
	  continue;
	}
	$h[$last = $i[0]] = $i[1];
      }
    }
    if (count($h['']) != 3) return FALSE;
    return $h;
  }
  public function send_message($code,$headers,$body = '') {
    $resp = self::prepare_message($code,$headers,$body);
    $sock = $this->get_socket();
    NetIO::write($sock,$resp);
  }
  static public function prepare_message($code,$headers,$body = '') {
    $resp = $code."\r\n";
    if (strlen($body) > 1 && !isset($headers['Content-Length']))
      $headers['Content-Length'] = strlen($body);
    foreach ($headers as $k => $v) {
      if ($k == '') continue;
      $resp .= $k.': '.$v."\r\n";
    }
    $resp .= "\r\n".$body;
    return $resp;
  }
}

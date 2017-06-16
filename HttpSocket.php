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
    $in = NetIO::read($conn,1024);
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
  public function send_message($code,$headers,$body = '') {
    $sock = $this->get_socket();
    $resp = $code."\r\n";
    if (strlen($body) > 1 && !isset($headers['Content-Length']))
      $headers['Content-Length'] = strlen($body);
    foreach ($headers as $k => $v) {
      $resp .= $k.': '.$v."\r\n";
    }
    $resp .= "\r\n".$body;
    NetIO::write($sock,$resp);
  }
}

<?php
class SocketPump extends BaseSocket {
  protected $wr_socket;
  public function __construct($in,$out) {
    $this->wr_socket = $out;
    parent::__construct($in);
    MainLoop::inst()->register_socket($in,[$this,'pump']);
  }
  public function pump($main,$in) {
    if (($data = NetIO::read($in,1024)) !== FALSE && strlen($data) > 0) {
      NetIO::write($this->wr_socket,$data);
      return;
    }
    $main->unregister_socket($in);
    $main->shutdown_socket($this->wr_socket,NetIO::SHUT_WR);
    $main->shutdown_socket($in,NetiO::SHUT_RD);
  }
}

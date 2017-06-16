<?php
class ServerSocket extends BaseSocket {
  protected $factory;
  public function __construct($port,$factory,$addr = '0.0.0.0',$ssl=FALSE) {
    if (!is_callable($factory)) {
      throw new Exception('No callback specified');
      return FALSE;
    }
    $this->factory = $factory;
    $sock = NetIO::new_server($port,$addr);
    parent::__construct($sock);
    MainLoop::inst()->register_socket($sock,[$this,'accept_client']);
    Logger::info('Listening on '.$addr.','.$port);
  }
  public function accept_client($main,$sock) {
    $conn = NetIO::accept($sock);
    Logger::debug('New connection from: '.NetIO::get_peername($conn));
    $cb = $this->factory;
    $cb($main,$conn);
  }
}

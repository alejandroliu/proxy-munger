<?php
class ServerSocket extends BaseSocket {
  protected $factory;
  protected $acl;
  
  public function __construct($port,$factory,$addr = '0.0.0.0',$ssl=NULL) {
    if (!is_callable($factory)) {
      throw new Exception('No callback specified');
      return FALSE;
    }
    $this->acl = NULL;
    $this->factory = $factory;
    $sock = NetIO::new_server($port,$addr,$ssl);
    parent::__construct($sock);
    MainLoop::inst()->register_socket($sock,[$this,'accept_client']);
    Logger::info('Listening on '.$addr.','.$port);
  }
  public function register_acl($acl = NULL) {
    $this->acl = $acl;
  }
  public function accept_client($main,$sock) {
    $conn = NetIO::accept($sock);
    $peer = NetIO::get_peername($conn);
    if ($this->acl) {
      if (!$this->acl->evaluate($peer)) {
	Logger::warning('Rejected connection from '.$peer);
	return;
      }
    }
    Logger::debug('New connection from: '.$peer);
    $cb = $this->factory;
    $cb($main,$conn);
  }
}

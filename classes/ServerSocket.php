<?php
/**
 * Server socket
 * @package proxy_munger
 */
 
/**
 * Class that implements Server Socket (listener)
 */
class ServerSocket extends BaseSocket {
  /** callback */
  protected $factory;
  /** Access Control List (Optional) */
  protected $acl;
  
  /**
   * Constructor
   * 
   * @param int	$port - Port to listen to
   * @param callable	$factory - Callback to invoke when accepting new connections
   * @param str	$addr - Bind IP address.  Defaults to '0.0.0.0'.
   * @param mixed	$ssl - NULL, Path to a unencrypted certificate or an array with SSL options.
   */
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
  /**
   * Register ACL to this server
   * 
   * @param Acl $acl
   */
  public function register_acl($acl = NULL) {
    $this->acl = $acl;
  }
  /**
   * Handle incoming connections
   * 
   * @param MainLoop	$main - Main loop object
   * @param rsrc	$sock - Incoming connection
   */
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

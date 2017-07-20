<?php
/**
 * Main Loop
 * @package proxy_munger
 */
 
/**
 * Single threaded main loop
 */
class MainLoop {
  /** Sockets to multiplex */
  protected $fds;
  /** Used to generate id's */
  protected $cid;
  /** Sockets that are half closed */
  protected $shut;
  /** Loop is running */
  public $running;
  /** Singleton object */
  protected static $instance = NULL;

  /** Constructor */
  protected function __construct() {
    $this->fds = [];
    $this->shut = [];
    $this->cid = 1;
    $this->running = FALSE;
  }
  
  /**
   * Find the MainLoop instance
   * @return MainLoop
   */
  static public function inst() {
    if (self::$instance == NULL) {
      self::$instance = new MainLoop();
    }
    return self::$instance;
  }
  /**
   * Register a socket callback
   * 
   * @param rsrc	$sock - Socket to multiplex
   * @param callable	$callback - Callback for when the socket is readable
   * @return int|FALSE	Returns FALSE on error, otherwise the $id for this socket.
   */
  
  public function register_socket($sock,$callback) {
    if (!is_resource($sock)) {
      throw new Exception('Socket not specified');
      return FALSE;
    }
    if (!is_callable($callback)) {
      throw new Exception('No callback specified');
      return FALSE;
    }
    $id = $this->lookup_socket($sock);
    if ($id === FALSE) $id = $this->cid++;
    $this->fds[$id] = [ $sock,$callback ];
    return $id;
  }
  /**
   * Lookup socket
   * @param rsrc	$sock - socket to lookup its ID
   * @return int|FALSE	Returns FALSE if not found, otherwise the $id for the socket
   */
  public function lookup_socket($sock) {
    foreach ($this->fds as $id=>$sc) {
      if ($sc[0] == $sock) return $id;
    }
    return FALSE;
  }
  /**
   * Un-register a socket callback
   * @param rsrc	$sock - Socket to de-register
   */
  public function unregister_socket($sock) {
    $id = $this->lookup_socket($sock);
    if ($id === FALSE) return;
    unset($this->fds[$id]);
  }

  /**
   * Utility function to see the shutdown status of a socket
   * @param rsrc	$sock - Socket to lookup
   * @return int|FALSE	Returns FALSE if not found, othewise the $id.
   */
  protected function get_shut($sock) {
    foreach ($this->shut as $i => $j) {
      if ($j['sock'] == $sock) return $i;
    }
    return FALSE;
  }
  /**
   * Half shuts down a socket
   * 
   * If boths halves are shutdown, it will fully shutdown the socket
   * 
   * @param rsrc	$sock - Socket to close
   * @param enum	$how - Should be one of NetIO::SHUT_RDWR, NetIO::SHUT_RD, NetIO::SHUT_WR
   */  
  public function shutdown_socket($sock,$how) {
    //socket_shutdown($sock,$how);
    NetIO::shutdown($sock,$how);
    $id = $this->get_shut($sock);
    if ($how == NetIO::SHUT_RDWR) {
      NetIO::close($sock);
      if ($id !== FALSE) unset($this->shut[$id]);
      return;
    }
    if ($id === FALSE) {
      $this->shut[$this->cid++] = ['sock'=>$sock,$how=>TRUE];
      return;
    }
    $this->shut[$id][$how] = TRUE;
    if (isset($this->shut[$id][NetIO::SHUT_RD]) && isset($this->shut[$id][NetIO::SHUT_RDWR])) {
      NetIO::close($sock);
      unset($this->shut[$id]);
    }
  }	

  /**
   * Runs the main loop
   */
  public function run() {
    if (count($this->fds) == 0) {
      throw new Exception('No registered sockets.  Bad configuration');
      return;
    }
    
    $this->running = TRUE;
    while ($this->running) {
      $write = $except = NULL;
      $sockets = [];
      foreach ($this->fds as $id=>$sc) {
	$sockets[] = $sc[0];
      }
      $cnt = NetIO::select($sockets,$write,$except,NULL);
      if ($cnt === FALSE) {
	$this->running = FALSE;
	throw new Exception('select: '.NetIO::strerror());
	return;
      }
      if ($cnt == 0) continue;
      foreach ($sockets as $sock) {
	$id = $this->lookup_socket($sock);
	if ($id === FALSE) {
	  $this->running = FALSE;
	  throw new Exception('Selected non existint socket');
	  return;
	}
	$cb = $this->fds[$id][1];
	$cb($this,$sock);
      }
    }
  }
}



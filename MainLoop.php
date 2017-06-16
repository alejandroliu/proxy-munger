<?php
class MainLoop {
  protected $fds;
  protected $cid;
  protected $shut;
  public $running;
  protected static $instance = NULL;

  protected function __construct() {
    $this->fds = [];
    $this->shut = [];
    $this->cid = 1;
    $this->running = FALSE;
  }
  
  static public function inst() {
    if (self::$instance == NULL) {
      self::$instance = new MainLoop();
    }
    return self::$instance;
  }
  
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
  public function lookup_socket($sock) {
    foreach ($this->fds as $id=>$sc) {
      if ($sc[0] == $sock) return $id;
    }
    return FALSE;
  }
  public function unregister_socket($sock) {
    $id = $this->lookup_socket($sock);
    if ($id === FALSE) return;
    unset($this->fds[$id]);
  }

  protected function get_shut($sock) {
    foreach ($this->shut as $i => $j) {
      if ($j['sock'] == $sock) return $i;
    }
    return FALSE;
  }    
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

  public function run() {
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



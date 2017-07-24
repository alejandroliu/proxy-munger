<?php
class ReverseProxyServer implements ICfgResponder {
  public $target;
  public $port;
  public $rw_host;
  public $rw_path;
  public $http_proxy;

  public function cfg($opts = NULL) {
    if ($opts == NULL) return;
    if (isset($opts['target'])) $this->target = $opts['target'];
    if (isset($opts['port'])) $this->port = $opts['port'];
    if (isset($opts['rw_host'])) $this->rw_host = $opts['rw_host'];
    if (isset($opts['rw_path'])) $this->rw_path = $opts['rw_path'];
    if (isset($opts['http_proxy'])) $this->http_proxy = $opts['http_proxy'];
  }
  public function __construct($opts = NULL) {
     $this->target = '127.0.0.1';
     $this->port = 80;
     $this->rw_host = 'localhost';
     $this->rw_path = FALSE;
     $this->http_proxy = FALSE;
     $this->cfg($opts);
  }
  public function http_response($server,$re,$hdr,$data) {
    if ($this->rw_path) {
      if (!preg_match($re,$hdr,$mv)) die("Internal Error! (RE mismatch)\n");
      $orig_path = $mv[1];
    }
    $hdr = HttpSocket::parse_header($hdr);
    if ($hdr === FALSE) {
      $server->send_error('Invalid request');
      return;
    }
    $ln = $hdr[''];
    $url_path = $ln[1];
    
    if ($this->rw_path) {
      if (substr($url_path,0,strlen($orig_path)) == $orig_path) {
	$url_path = $this->rw_path . substr($url_path,strlen($orig_path));
      }
    }
    $cmd = implode(' ',[
			$ln[0],
			$this->http_proxy ? 'http://'.$this->rw_host.$url_path : $url_path,
			$ln[2],
		    ]);

    $sock = $server->get_socket();
    try {
      $remote = NetIO::new_client(NetIO::host_lookup($this->target),$this->port);
    } catch (Exception $e) {
      Logger::error('Unable to connect to '.$this->target.' '.$e->getMessage().' ('.NetIO::get_peername($sock));
      $server->send_error('500 ERROR','Unable to connect to '.$this->target.' '.$e->getMessage());
      return;
    }
    $hdr['X-Forwarded-Host'] = $hdr['Host'];
    $hdr['Host'] = $this->rw_host;
    $peer = NetIO::get_peername($sock);
    if (substr($peer,0,1) == '[') {
      $peer = '"'.$peer.'"';
    } else {
      $peer = preg_replace('/:\d+$/','',$peer);
    }
    if (isset($hdr['X-Forwarded-For'])) {
      $hdr['X-Forwarded-For'] .= ', '.$peer;
    } else {
      $hdr['X-Forwarded-For'] = $peer;
    }
    //print_r($hdr);
    $req = HttpSocket::prepare_message($cmd,$hdr,$data);
    NetIO::write($remote,$req);
    
    new SocketPump($remote,$sock);
    new SocketPump($sock,$remote);
  }
}

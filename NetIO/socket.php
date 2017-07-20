<?php
/**
 * Basic socket I/O implementation
 * @package proxy_munger\socket
 */
 
require(dirname(realpath(__FILE__)).'/interface.php');

/**
 * This class implements NetIO based on sockets
 */
class NetIO implements INetIO {
  const SHUT_RD = 0;
  const SHUT_WR = 1;
  const SHUT_RDWR = 2;
  static public function shutdown($sock,$how = self::SHUT_RDWR) {
    return @socket_shutdown($sock,$how);
  }
  static public function close($sock) {
    return socket_close($sock);
  }
  static public function select(&$rd,&$wr,&$ex,$timeout = NULL) {
    return socket_select($rd,$wr,$ex,$timeout);
  }
  static public function strerror($mx = NULL) {
    if ($mx === NULL) return socket_strerror(socket_last_error());
    return socket_strerror(socket_last_error($mx));
  }
  static public function accept($sock) {
    $conn = socket_accept($sock);
    socket_set_nonblock($conn);
    return $conn;
  }
  static public function get_peername($sock) {
    if (socket_getpeername($sock,$addr,$port) == FALSE) return FALSE;
    if (filter_var($addr,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6))
      return '['.$addr.']:'.$port;
    return $addr.':'.$port;
  }
  static public function read($sock,$bsz=4096) {
    return socket_read($sock,$bsz);
  }
  static public function write($sock,$data) {
    return socket_write($sock,$data);
  }
  static public function new_server($port,$addr = '0.0.0.0',$ssl=NULL) {
    // Defaults to 0.0.0.0 which is IPv4 only... set $addr to
    // ::0 to allow IPv4 and IPv6.
    if ($ssl) throw new Excpetion('socket implementation does not support SSL');
    $family = self::detect_family($addr);
    if (($sock = socket_create($family,SOCK_STREAM,SOL_TCP)) === FALSE) {
      throw new Exception('Failed to create socket : '.self::strerror().PHP_EOL);
      return FALSE;
    }
    socket_set_option($sock,SOL_SOCKET,SO_REUSEADDR,1);
    if (socket_bind($sock,$addr,$port) === FALSE) {
      $e = self::strerror($sock);
      socket_close($sock);
      throw new Exception('Failed to bind socket : '.$e.PHP_EOL);
      return FALSE;
    }
    if (socket_listen($sock,0) === FALSE) {
      $e = self::strerror($sock);
      socket_close($sock);
      throw new Exception('Failed to listen to socket : '.$e.PHP_EOL);
      return FALSE;
    }
    return $sock;
  }
  static public function new_client($target,$port,$ssl=FALSE) {
    if ($ssl) throw new Excpetion('socket implementation does not support SSL');
    $family = self::detect_family($target);
    if (($sock = socket_create($family,SOCK_STREAM,SOL_TCP)) === FALSE) {
      throw new Exception('Failed to create socket : '.self::strerror().PHP_EOL);
      return FALSE;
    }
    if (socket_connect($sock,$target,$port) === FALSE) {
      $e = self::strerror($sock);
      socket_close($sock);
      throw new Exception('Failed to connect socket : '.$e.PHP_EOL);
      return FALSE;
    }
    socket_set_nonblock($sock);
    return $sock;
  }
  static public function host_lookup($name) {
    if (filter_var($name,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6) ||
	filter_var($name,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)) return $name;
      
    $ip = gethostbyname($name);
    if ($ip == $name) {
      throw new Exception('Unknown host: '.$name);
      return NULL;
    }
    return $ip;  
  }

  static function detect_family($addr) {
    if (filter_var($addr,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)) {
      //fwrite(STDERR,'Selected AF_INET6'.PHP_EOL);//DEBUG
      return AF_INET6;
    }
    if (filter_var($addr,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)) {
      //fwrite(STDERR,'Selected AF_INET'.PHP_EOL);//DEBUG
      return AF_INET;
    }
    return AF_UNIX;
  }
}  



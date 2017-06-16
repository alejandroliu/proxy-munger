<?php
require(dirname(realpath(__FILE__)).'/interface.php');

class NetIO implements INetIO {
  const SHUT_RD = STREAM_SHUT_RD;
  const SHUT_WR = STREAM_SHUT_WR;
  const SHUT_RDWR = STREAM_SHUT_RDWR;
  static public function shutdown($sock,$how = self::SHUT_RDWR) {
    return stream_shutdown($sock,how);
  }
  static public function close($sock) {
    return close($sock);
  }
  static public function select(&$rd,&$wr,&$ex,$timeout = NULL) {
    return stream_select($rd,$wr,$ex,$timeout);
  }
  static public function strerror($mx = NULL) {
    $e = error_get_last();	// Not sure if this works...
    return $e['message'].' ('.$e['file'].','.$e['line'].')';
  }
  static public function accept($sock) {
    $conn = stream_socket_accept($sock);
    self::set_io_options($conn);
    return $conn;
  }
  static public function get_peername($sock) {
    return stream_socket_get_name($sock,TRUE);
  }
  static public function read($sock,$bsz) {
    return fread($sock,$bsz);
  }
  static public function write($sock,$data) {
    return fwrite($sock,$data);
  }
  static function format_addr($addr,$port,$ssl) {
    $proto = $ssl ? 'ssl' : 'tcp';
    if (filter_var($addr,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)) {
      //fwrite(STDERR,'Selected AF_INET6'.PHP_EOL);//DEBUG
      return $proto.'://['.$addr.']:'.$port;
    }
    if (filter_var($addr,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)) {
      //fwrite(STDERR,'Selected AF_INET'.PHP_EOL);//DEBUG
      return $proto.'://'.$addr;
    }
    if ($ssl) trigger_error('UNIX sockets do not support SSL',E_USER_NOTICE);
    return 'unix://'.$addr;
  }
  static public function new_server($port,$addr = '0.0.0.0',$ssl=FALSE) {
    // Defaults to 0.0.0.0 which is IPv4 only... set $addr to
    // ::0 to allow IPv4 and IPv6.
    $sockname = self::format_addr($addr,$port,$ssl);
    
    $sock = stream_socket_server($sockname,$errno,$errstr);
    if ($sock === FALSE) {
      throw new Exception('Failed to create socket : '.$errstr.' ('.$errno.')'.PHP_EOL);
      return FALSE;
    }
    return $sock;
  }
  static public function new_client($target,$port,$ssl=FALSE) {
    $sockname = self::format_addr($target,$port,$ssl);
    $sock = stream_socket_client($sockname,$errno,$errstr);
    if ($sock === FALSE) {
      throw new Exception('Connection failed : '.$errstr.' ('.$errno.')'.PHP_EOL);
      return FALSE;
    }
    return $sock;
    
    if (($sock = socket_create($family,SOCK_STREAM,SOL_TCP)) === FALSE) {
      throw new Exception('Failed to create socket : '.self::strerror().PHP_EOL);
      return FALSE;
    }
    self::set_io_options($sock);
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
  static public function set_io_options($sock) {
    stream_set_blocking($sock,FALSE);
    stream_set_write_buffer($sock,0);
  }
}  



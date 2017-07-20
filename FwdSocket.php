<?php
/**
 * Socket forwarder
 * @package proxy_munger
 */
 
/**
 * Class that implements socket forwarding
 */
abstract class FwdSocket {
  /**
   * Forwards data to a remote socket
   * 
   * Will connect to a remote target:port (using SSL if specified)
   * and after connection, data will be pumped through.
   * 
   * @param rsrc	$conn - Incoming socket
   * @param str	$target - Target host to forward data to
   * @param int	$port - Target port to forward data to
   * @param bool	$ssl - If true, connection will be through SSL
   * @uses SocketPump
   */
  static public function forward($conn,$target,$port,$ssl=FALSE) {
    //echo __FILE__.','.__LINE__.' ('.__CLASS__.'::'.__METHOD__.')'.PHP_EOL;//DEBUG
    try {
      $sock = NetIO::new_client($target,$port,$ssl);
    } catch (Exception $e) {
      Logger::error('Error connecting to  '.$target.','.$port.' ('.$e->getMessage().')');
      return;
    }
    Logger::info('Fwd from '.NetIO::get_peername($conn).' to '.$target.','.$port);    
    new SocketPump($sock,$conn);
    new SocketPump($conn,$sock);
  }
}

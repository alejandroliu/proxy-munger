<?php
abstract class FwdSocket {
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

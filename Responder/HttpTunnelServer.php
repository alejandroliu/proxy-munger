<?php   
class HttpTunnelServer implements IHttpResponder {
  static public function http_response($server,$re,$hdr,$data) {
    if (!preg_match($re,$hdr,$mv)) die("Internal Error! (RE mismatch)\n");
    $target = $mv[1];
    $port = $mv[2];
    
    $sock = $server->get_socket();
    try {
      $remote = NetIO::new_client(NetIO::host_lookup($target),$port);
    } catch (Exception $e) {
      Logger::error('Unable to connect to '.$target.' '.$e->getMessage().' ('.NetIO::get_peername($sock));
      $server->send_error('500 ERROR','Unable to connect to '.$target.' '.$e->getMessage());
      return;
    }
    Logger::info('Proxying '.NetIO::get_peername($sock).' to '.$target.','.$port);
    if (strlen($data) > 0) socket_write($remote,$data);
    $server->send_reply('200 OK',[
		'Content-Type' => 'application/octet-stream',
		'Server' => 'PHP '.phpversion(),
	      ], '');

    new SocketPump($remote,$sock);
    new SocketPump($sock,$remote);
  }
}
    

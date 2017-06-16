<?php
interface IHttpResponder {
  static public function http_response($server,$re,$hdr,$data);
}

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
    Logger::info('Proxying '.NetIO::get_peername($sock).' to '.$target.':'.$port);
    if (strlen($data) > 0) socket_write($remote,$data);
    $server->send_reply('200 OK',[
		'Content-Type' => 'application/octet-stream',
		'Server' => 'PHP '.phpversion(),
	      ], '');

    new SocketPump($main,$remote,$sock);
    new SocketPump($main,$sock,$remote);
  }
}
    
class DefaultResponse implements IHttpResponder {
  static public function http_response($server,$re,$hdr,$data) {
    $server->send_html('<!DOCTYPE html>
<html>
 <meta charset=utf-8>
 <title>It Works</title>
 <h1>It Works</h1>
');
    $server->end();
  }
}

class Erro405Response implements IHttpResponder {
  static public function http_response($server,$re,$hdr,$data) {
    $server->send_error('Access Denied','405 FORBIDDEN');
  }
}

class Erro404Response implements IHttpResponder {
  static public function http_response($server,$re,$hdr,$data) {
    $server->send_error('Resource not found','404 NOT FOUND');
  }
}

class RedirResponse implements IHttpResponder {
  static public function http_response($server,$re,$hdr,$data) {
    $hdr = explode("\r\n",$hdr);
    $main = preg_split('/\s+/',array_shift($hdr));
    
    foreach ($hdr as $h) {
      if (preg_match('/^Host:\s+([^:]+).*$/',$h,$mv)) {
	$host = $mv[1];
	break;
      }
    }
    if (!isset($main[1]) || !isset($host)) {
      $server->send_error('Resource not found','404 NOT FOUND');
      return;
    }
    $server->send_reply('302 REDIRECTED',[
		'Content-Type' => 'text/html;charset=utf-8',
		'Server' => 'PHP '.phpversion(),
		'Location' => 'https://'.$host.$main[1],
	], '<!DOCTYPE html>
<html>
 <meta charset=utf-8>
 <title>Moved</title>
 <h1>Moved</h1>
 This page has moved to <a href="https://'.$host.$main[1].'">'.
 $host
 .'</a>
</html>');
    $server->end();    
  }
}

class DebugResponse implements IHttpResponder {
  static public function http_response($server,$re,$hdr,$data) {
    $server->send_html('<!DOCTYPE html>
<html>
 <meta charset=utf-8>
 <title>ProxyTk Demo</title>
 <h1>ProxyTk Demo</h1>
 <hr/>
 <h2>Header</h2>
 <pre>'.htmlentities($hdr).'</pre>
 <hr/>
 <h2>Data</h2>
 <pre>'.htmlentities($data).'</pre>
 <hr/>
 <form method="post">
  <input type="text" name="data"><br/>
  <input type="submit" value="Submit">
 </form>
</html>');
    $server->end();
  }
}


#!/usr/bin/env php
<?php
error_reporting(E_ALL);
define('CRLF',"\r\n");
define('CMD',array_shift($argv));

class MainLoop {
  protected $fds;
  protected $cid;
  protected $shut;
  public $running;
  
  public function __construct() {
    $this->fds = [];
    $this->shut = [];
    $this->cid = 1;
    $this->running = FALSE;
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
    @socket_shutdown($sock,$how); // Ignore errors...
    
    $id = $this->get_shut($sock);
    if ($how == 2) {
      socket_close($sock);
      if ($id !== FALSE) unset($this->shut[$id]);
      return;
    }
    if ($id === FALSE) {
      $this->shut[$this->cid++] = ['sock'=>$sock,$how=>TRUE];
      return;
    }
    $this->shut[$id][$how] = TRUE;
    if (isset($this->shut[$id][0]) && isset($this->shut[$id][1])) {
      socket_close($sock);
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
      $cnt = socket_select($sockets,$write,$except,NULL);
      //echo "cnt=$cnt ".__FILE__.','.__LINE__.PHP_EOL;//DEBUG

      if ($cnt === FALSE) {
	$this->running = FALSE;
	throw new Exception('select: '.socket_strerror(socket_last_error()));
	//echo __CLASS__.'::'.__METHOD__.' '.__FILE__.','.__LINE__.PHP_EOL;//DEBUG
	return;
      }
      if ($cnt == 0) continue;
      foreach ($sockets as $sock) {
	$id = $this->lookup_socket($sock);
	if ($id === FALSE) {
	  //echo __CLASS__.'::'.__METHOD__.' '.__FILE__.','.__LINE__.PHP_EOL;//DEBUG
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


function detect_family($addr) {
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

function new_server_socket($port, $addr = '0.0.0.0') {
  // Defaults to 0.0.0.0 which is IPv4 only... set $addr to
  // ::0 to allow IPv4 and IPv6.
  $family = detect_family($addr);
  if (($sock = socket_create($family,SOCK_STREAM,SOL_TCP)) === FALSE) {
    throw new Exception('Failed to create socket : '.socket_strerror(socket_last_error()).PHP_EOL);
    return FALSE;
  }
  socket_set_option($sock,SOL_SOCKET,SO_REUSEADDR,1);
  if (socket_bind($sock,$addr,$port) === FALSE) {
    $e = socket_strerror(socket_last_error($sock));
    socket_close($sock);
    throw new Exception('Failed to bind socket : '.$e.PHP_EOL);
    return FALSE;
  }
  if (socket_listen($sock,0) === FALSE) {
    $e = socket_strerror(socket_last_error($sock));
    socket_close($sock);
    throw new Exception('Failed to listen to socket : '.$e.PHP_EOL);
    return FALSE;
  }
  return $sock;
}

function new_client_socket($target,$port) {
  $family = detect_family($target);
  if (($sock = socket_create($family,SOCK_STREAM,SOL_TCP)) === FALSE) {
    throw new Exception('Failed to create socket : '.socket_strerror(socket_last_error()).PHP_EOL);
    return FALSE;
  }
  if (socket_connect($sock,$target,$port) === FALSE) {
    $e = socket_strerror(socket_last_error($sock));
    socket_close($sock);
    throw new Exception('Failed to connect socket : '.$e.PHP_EOL);
    return FALSE;
  }
  socket_set_nonblock($sock);
  return $sock;
}

function lookup_host($target) {
  if (filter_var($target,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6) ||
      filter_var($target,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)) return $target;
    
  $ip = gethostbyname($target);
  if ($ip == $target) {
    throw new Exception('Unknown host: '.$target);
    return NULL;
  }
  fwrite(STDERR,'Lookup: '.$target.' as '.$ip.PHP_EOL);
  return $ip;  
}


abstract class BaseSocket {
  protected $sock;
  protected $main;
  public function __construct($main,$sock) {
    $this->main = $main;
    $this->sock = $sock;
  }
  public function get_socket() {
    return $this->sock;
  }
}

class ServerSocket extends BaseSocket {
  protected $factory;
  public function __construct($main,$factory,$port,$addr = '0.0.0.0') {
    if (!is_callable($factory)) {
      throw new Exception('No callback specified');
      return FALSE;
    }
    $this->factory = $factory;
    $sock = new_server_socket($port,$addr);
    parent::__construct($main,$sock);
    $main->register_socket($sock,[$this,'accept_client']);
    fwrite(STDERR,'Listening on '.$addr.','.$port.PHP_EOL);
  }
  public function accept_client($main,$sock) {
    $conn = socket_accept($sock);
    socket_set_nonblock($conn);

    socket_getpeername($conn,$addr,$port);
    //echo __FILE__.','.__LINE__.PHP_EOL;//DEBUG
    fwrite(STDERR,'New connection from: '.$addr.':'.$port.PHP_EOL);
    $cb = $this->factory;
    $cb($main,$conn);
  }
}

class SocketPump extends BaseSocket {
  protected $wr_socket;
  public function __construct($main,$in,$out) {
    $this->wr_socket = $out;
    parent::__construct($main,$in);
    $main->register_socket($in,[$this,'pump']);
  }
  public function pump($main,$in) {
    //echo __CLASS__.'::'.__METHOD__.' '.__FILE__.','.__LINE__.PHP_EOL;//DEBUG

    if (($data = socket_read($in,1024)) !== FALSE && strlen($data) > 0) {
      //echo __CLASS__.'::'.__METHOD__.' '.__FILE__.','.__LINE__.PHP_EOL;//DEBUG
      socket_write($this->wr_socket,$data);
      return;
    }
    //echo __CLASS__.'::'.__METHOD__.' '.__FILE__.','.__LINE__.PHP_EOL;//DEBUG

    $main->unregister_socket($in);
    $main->shutdown_socket($this->wr_socket,1);
    $main->shutdown_socket($in,0);
  }
}

abstract class HttpSocket extends BaseSocket {
  protected $request;
  
  abstract public function handle_request($main,$conn,$hdr,$data);
  public function __construct($main,$conn) {
    $this->request = '';
    parent::__construct($main,$conn);
    $main->register_socket($conn,[$this,'recv']);
  }
  public function recv($main,$conn) {
    $off = strlen($this->request);
    if ($off > 4) $off - 4;
    $in = socket_read($conn,1024);
    if ($in === FALSE) {
      fwrite(STDERR,'HttpSocket, error reading socket'.PHP_EOL);
      $main->unregister_socket($conn);
      socket_close($conn);
      return;
    }
    $this->request .= $in;
    if (($pos = strpos($this->request,CRLF.CRLF,$off)) === FALSE) return;
    
    $main->unregister_socket($conn);

    $hdr = substr($this->request,0,$pos);
    $data = substr($this->request,$pos+4);
    
    $this->handle_request($main,$conn,$hdr,$data);
  }
  public function send_message($code,$headers,$body = '') {
    $sock = $this->get_socket();
    $resp = $code.CRLF;
    if (strlen($body) > 1 && !isset($headers['Content-Length']))
      $headers['Content-Length'] = strlen($body);
    foreach ($headers as $k => $v) {
      $resp .= $k.': '.$v.CRLF;
    }
    $resp .= CRLF.$body;
    socket_write($sock,$resp);
  }
}

class HttpClient extends HttpSocket {
  protected $request;
  public $routes;
  static public $default_response = NULL;
  
  public function __construct($main,$conn,$fspath) {
    parent::__construct($main,$conn);
    $this->routes = [
      '/^POST\s+'.strtr($fspath,['/' => '\/']).'/' => [ $this, 'tunnel' ],
      '' => [ $this, 'def_index' ],
    ];
  }
  public function handle_request($main,$conn,$hdr,$data) {
    // Evaluate request...
    foreach ($this->routes as $re => $cb) {
      if (!$re || preg_match($re,$hdr)) {
	$cb($main,$conn,$re,$hdr,$data);
	return;
      }
    }
    die('Mis configured routes'.PHP_EOL);
  }
  public function send_reply($code,$headers,$body = '') {
    $this->send_message('HTTP/1.1 '.$code,$headers,$body);
  }
  public function send_error($code,$msg) {
    //echo __FILE__.','.__LINE__.PHP_EOL;//DEBUG
    $this->send_reply($code,[
		'Content-Type' => 'text/html;charset=utf-8',
		'Server' => 'PHP '.phpversion(),
	], '<!DOCTYPE html>
<html>
 <meta charset=utf-8>
 <title>ERROR</title>
 <h1>ERROR</h1><pre>'.htmlentities($msg).'</pre>
</html>');
    //echo __FILE__.','.__LINE__.PHP_EOL;//DEBUG
    socket_close($sock);
    //echo __FILE__.','.__LINE__.PHP_EOL;//DEBUG
    fwrite(STDERR,'ERROR:'.$code.' '.$msg.PHP_EOL);
    //echo __FILE__.','.__LINE__.PHP_EOL;//DEBUG
  }
  public function tunnel($main,$sock,$re,$hdr,$data) {
    //echo __FILE__.','.__LINE__.PHP_EOL;//DEBUG
    $main->unregister_socket($sock);
    list($ln,) = preg_split('/\s+/',preg_replace($re,'',$hdr),2);
    $ln = explode('/',$ln);
    if (count($ln) < 2) $ln[1] = 22;
    list($target,$tport) = $ln;
    //echo __FILE__.','.__LINE__.PHP_EOL;//DEBUG
    try {
      $remote = new_client_socket(lookup_host($target),$tport);
    } catch (Exception $e) {
      $this->send_error('500 ERROR','Unable to connect to '.$target.' '.$e->getMessage());
      return;
    }
    //echo __FILE__.','.__LINE__.PHP_EOL;//DEBUG
    fwrite(STDERR,'Connected to '.$target.','.$tport.PHP_EOL);
    if (strlen($data) > 0) socket_write($remote,$data);
    
    $this->send_reply('200 OK',[
		'Content-Type' => 'text/html;charset=utf-8',
		'Server' => 'PHP '.phpversion(),
	      ], '');

    new SocketPump($main,$remote,$sock);
    new SocketPump($main,$sock,$remote);
  }
  public function def_index($main,$sock,$re,$hdr,$data) {
    $main->unregister_socket($sock);
    $response = self::$default_response;
    if ($response == NULL) $response = [__CLASS__,'def_response'];
    list($code,$hdrs,$body) = $response($hdr,$data);
    $this->send_reply($code,$hdrs,$body);
    socket_close($sock);
  }
  static public function def_response($hdr,$data) {
    $body = '<!DOCTYPE html>
<html>
 <meta charset=utf-8>
 <title>Forbidden</title>
 <h1>Access not Allowed</h1>
 <hr/><h2>Header</h2><pre>'.htmlentities($hdr).'</pre>';
  if ($data) $body .= '<hr/><h2>Body</h2><pre>'.htmlentities($data).'</pre>';
  $body .= '</html>';
    return ['404 FORBIDDEN',[
		'Content-Type' => 'text/html;charset=utf-8',
		'Server' => 'PHP '.phpversion(),
	], $body];
  }
  static public function https_redir_response($hdr,$data) {
    $hdr = explode(CRLF,$hdr);
    $main = preg_split('/\s+/',array_shift($hdr));
    
    foreach ($hdr as $h) {
      if (preg_match('/^Host:\s+([^:]+).*$/',$h,$mv)) {
	$host = $mv[1];
	break;
      }
    }
    if (!isset($main[1]) || !isset($host)) return self::def_response($sock,$hdr,$data);
    return [ '302 REDIRECTED',[
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
</html>' ];
    
  }
  static public function def_response_debug($hdr,$data) {
    $body = <<<EOS
<!DOCTYPE html>
<html>
 <meta charset=utf-8>
 <title>RUNC Server</title>

 <h1>RUNC Server</h1>
EOS;
    $body .= '<hr/><h2>Header</h2><pre>'.htmlentities($hdr).'</pre>';
    if ($data) $body .= '<hr/><h2>Body</h2><pre>'.htmlentities($data).'</pre>';
    $body .= <<<EOS
   <hr/>
   <form method="post">
    <input type="text" name="data"><br/>
    <input type="submit" value="Submit">
   </form>
  </html>
EOS;

    return [ '200 OK',[
		'Content-Type' => 'text/html;charset=utf-8',
		'Server' => 'PHP '.phpversion(),
	      ], $body];
  }

}  

class TunnelHelper extends HttpSocket {
  protected $client;
  public function __construct($main,$conn,$http_proxy,$http_addr,$http_port,$target,$target_port,$fspath) {
    //echo __FILE__.','.__LINE__.' ('.__CLASS__.'::'.__METHOD__.')'.PHP_EOL;//DEBUG
    $this->client = $conn;
    try {
      $proxy = new_client_socket($http_addr,$http_port);
    } catch (Exception $e) {
      fwrite(STDERR,'Error connecting to web proxy '.$http_addr.':'.$http_port.' ('.$e->getMessage().')'.PHP_EOL);
      return;
    }
    parent::__construct($main,$proxy);
    $this->send_message('POST '.$fspath.$target.'/'.$target_port.' HTTP/1.1', [
				'Host' => $http_proxy,
				'Connection' => 'keep-alive',
				'Content-Length' => 0,
				'Cache-Control' => 'no-cache',
				'Content-Type' => 'application/x-www-form-urlencoded',
			]);
    //echo __FILE__.','.__LINE__.' ('.__CLASS__.'::'.__METHOD__.')'.PHP_EOL;//DEBUG
    parent::__construct($main,$proxy);
  }
  public function handle_request($main,$conn,$hdr,$data) {
    // Evaluate request...
    //echo __FILE__.','.__LINE__.' ('.__CLASS__.'::'.__METHOD__.')'.PHP_EOL;//DEBUG
    $tok = preg_split('/\s+/',$hdr,3);
    if (count($tok) >= 2) {
      if ($tok[1]{0} == '2') { // Succesful HTTP request...
	socket_write($this->client,$data);
	new SocketPump($main,$this->client,$conn);
	new SocketPump($main,$conn,$this->client);
	return;
      }
    }
    socket_close($this->client); // Error, abort client connection...
    socket_close($conn);
    fwrite(STDERR,'Upstream server error:'.PHP_EOL.$hdr.PHP_EOL.PHP_EOL.$data.PHP_EOL);
  }
}
###################################################################
// --port-fwd=<port>,<http_proxy>:<port>,</vtun/>,<target-host>:<port>
// --http-server=<port>,</vtun/>

$servers = [];
while (count($argv)) {
  if (preg_match('/^--port-fwd=(\d+),(\S+):(\d+),([^,]+),(\S+):(\d+)$/',$argv[0],$mv)) {
    list(,$port,$http_proxy,$http_port,$fspath,$target,$target_port) = $mv;
    $http_addr = lookup_host($http_proxy);
    
    if (preg_match($re='/^https?:\/\/([^\/]+)/',$fspath,$mv)) {
      // OK, going through an additional http proxy...
      $http_proxy = $mv[1];
    }
    
    $servers[$port] = function ($main,$conn) use ($http_proxy,$http_addr,$http_port,$target,$target_port,$fspath) {
      new TunnelHelper($main,$conn,$http_proxy,$http_addr,$http_port,$target,$target_port,$fspath);
      /*
      echo __FILE__.','.__LINE__.PHP_EOL;//DEBUG
      $proxy = new_client_socket($http_addr,$http_port);
      socket_write($proxy,implode(CRLF,[
			      'POST '.$fspath.$target.'/'.$target_port.' HTTP/1.1',
			      'Host: '.$http_proxy,
			      'Connection: keep-alive',
			      '','']));
      new SocketPump($main,$proxy,$conn);
      new SocketPump($main,$conn,$proxy);
      * */
    };
  } elseif (preg_match('/^--http-server=(\S+),(.+)$/',$argv[0],$mv)) {
    list(,$port,$fspath) = $mv;
    $servers[$port] = function ($main,$conn) use ($fspath) {
      new HttpClient($main,$conn,$fspath);
    };
  } elseif ($argv[0] == '--debug' || $argv[0] == '-d') {
    HttpClient::$default_response = [ 'HttpClient', 'def_response_debug' ];
  } elseif ($argv[0] == '--redir' || $argv[0] == '-r') {
    HttpClient::$default_response = [ 'HttpClient', 'https_redir_response' ];
  } else {
    die('Invalid option: '.$argv[0].PHP_EOL);
  }
  array_shift($argv);
}
if (!count($servers)) die('No services specified'.PHP_EOL);


$main = new MainLoop();
foreach ($servers as $port => $callback) {
  new ServerSocket($main,$callback,$port);
}
//echo __FILE__.','.__LINE__.PHP_EOL;//DEBUG
$main->run();


/*
 * Sample command line options:
 * --port-fwd=7000,localhost:8000,/vtun/,cvm1.localnet:22
 *   Listen on port 7000, forward connections to the webserver running
 *   on local host:8000 and will issue POST /vtun/cvm1.localnet/22
 * --port-fwd=7000,proxyhost:8080,http://somwhere.com:80/vtun/,cvm1.localnet:22
 *   Listen on port 7000, forward connections to the http proxy on
 *   proxyhost:8080 which in turn will connect to the web server at
 *   somewhere.com.
 * --http-server=8000,/vtun/
 *   A web server that listens 
 * --debug|-d
 *   Create a debuggin response
 * --redir|-r
 *   Create a response to redirect connections to https
 * 
 * TODO:
 * - Router should dispatch GET /status to status display
 */

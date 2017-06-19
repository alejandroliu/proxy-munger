#!/usr/bin/env php
<?php
error_reporting(E_ALL);
define('CMD',array_shift($argv));
define('CMDNAME',basename(CMD,'.php'));

set_include_path(get_include_path().PATH_SEPARATOR.dirname(realpath(__FILE__)));
require('MainLoop.php');
require('BaseSocket.php');
require('ServerSocket.php');
require('SocketPump.php');
require('HttpSocket.php');
require('HttpTunnelClient.php');
require('HttpServer.php');
require('Responder/interface.php');
require('Responder/standard.php');
require('Responder/HttpTunnelServer.php');
require('Responder/ReverseProxyServer.php');
require('Logger/basic.php');
require('FwdSocket.php');
require('Acl.php');

// configuration shortcuts
define('ALLOW',Acl::ALLOW);
define('DENY',Acl::DENY);
define('ANY_IP','0.0.0.0');

function acl($opts) {
  return new Acl($opts);
}
function forward_port($opts) {
  foreach (['port','target','target_port'] as $i) {
    if (!isset($opts[$i])) throw new Exception('Missing '.$i.PHP_EOL);
  }
  if (!isset($opts['bind'])) $opts['bind'] = ANY_IP;
  $target = $opts['target'];
  $port = $opts['target_port'];
  $srv = new ServerSocket($opts['port'],function ($main,$conn) use ($target,$port) {
    FwdSocket::forward($conn,$target,$port);
  }, $opts['bind']);
  if (isset($opts['acl'])) $srv->register_acl($opts['acl']);
  return $srv;
}

  public function __construct($conn,$http_host,$proxy_addr,$proxy_port,$target,$target_port,$verb,$ssl=FALSE);

function tunnel_port($opts) {
  foreach (['port','target','target_port','proxy','proxy_port'] as $i) {
    if (!isset($opts[$i])) throw new Exception('Missing '.$i.PHP_EOL);
  }
  if (!isset($opts['bind'])) $opts['bind'] = ANY_IP;
  if (!isset($opts['http_request'])) $opts['http_request'] = 'CONNECT %h:%p';
  if (!isset($opts['http_host'])) $opts['http_host'] = $opts['proxy'];
  
  $target = $opts['target'];
  $target_port = $opts['target_port'];
  $proxy = $opts['proxy'];
  $proxy_port = $opts['proxy_port'];
  $http_request = $opts['http_request'];
  $http_host = $opts['http_host'];
  
  $srv = new ServerSocket($opts['port'],function ($main,$conn) use ($http_host,$target,$target_port,$proxy,$proxy_port) {
      new HttpTunnelClient($conn, $http_host,
				NetIO::host_lookup($proxy),$proxy_port,
				$target,$target_port,
				$http_request);
      }, $opts['bind']);
  if (isset($opts['acl'])) $srv->register_acl($opts['acl']);
  return $srv;
}

function http_server($opts) {
  foreach (['port','routes'] as $i) {
    if (!isset($opts[$i])) throw new Exception('Missing '.$i.PHP_EOL);
  }
  if (!isset($opts['bind'])) $opts['bind'] = ANY_IP;
  $srv = new ServerSocket($opts['port'],function ($main,$conn) use (&$routes) {
      new HttpServer($conn,$routes);
  }, $opts['bind']);
  if (isset($opts['acl'])) $srv->register_acl($opts['acl']);
  return $srv;
}
function http_tunnel() {
  return [ 'HttpTunnelServer','http_response'];
}
function error_404() {
  return ['Erro404Response','http_response'];
}
function error_405() {
  return ['Erro405Response','http_response'];
}
function redir_response() {
  return ['RedirResponse','http_response'];
}
function debug_response() {
  return ['DebugResponse','http_response'];
}
function reverse_proxy($opts) {
  return [new ReverseProxyServer($opts),'http_response'];
}
  
if (count($argv) && file_exists($argv[0])) {
  fwrite(STDERR,'Reading configuration: '.$argv[0].PHP_EOL);
  require(array_shift($argv));
} else {
  foreach ([dirname(CMD), dirname(realpath(CMD)), '.'] as $d) {
    foreach (['/cfg.php','/'.CMDNAME.'-cfg.php'] as $f) {
      echo $d.$f.PHP_EOL;
      if (file_exists($d.$f)) {
	fwrite(STDERR,'Using configuration: '.$d.$f.PHP_EOL);
	require($d.$f);
	$f = NULL;
	break 2;
      }
    }
  }
  if ($f) die('No valid configuration file found!'.PHP_EOL);
}

MainLoop::inst()->run();


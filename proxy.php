#!/usr/bin/env php
<?php
/**
 * Main driver script
 * 
 * The main driver includes a number of standard shortcuts for
 * configuring proxies.
 * 
 * @package proxy_munger
 */
/** Path to proxy script */
define('CMD',array_shift($argv));
/** Basic command name */
define('CMDNAME',basename(CMD,'.php'));
error_reporting(E_ALL);

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
require('FwdSocket.php');
require('Acl.php');

// configuration shortcuts
/** ACL rule to allow */
define('ALLOW',Acl::ALLOW);
/** ACL rule to deny */
define('DENY',Acl::DENY);
/** ACL rule that matches anything */
define('ANY',Acl::ANY);
/** IP bind address for all available interfaces */
define('ANY_IP','0.0.0.0');

/**
 * Configure Access control list
 *
 * @param array $list - Access Control list
 */
function acl($list) {
  return new Acl($list);
}
/**
 * Create a forwarding port.
 *
 * This creates a basic port forwarding rule that forwards connections
 * to a port to a target host:port.  It accepts the following options:
 *
 * * int	$opts['port'] - port number to listen.
 * * str	$opts['target'] - remote host to forward the connection to.
 * * int	$opts['target_port'] - remote port to forward the connection to.
 * * str	$opts['bind'] - IP address to bind to.  Defaults to ANY_IP.
 * * bool	$opts['ssl_out'] - Use SSL for outgoing connections.
 * * mixed	$opts['ssl_in'] - An array with SSL options and/or a path to a PEM certificate.  If an array is given, these are used for the php ssl stream context.
 * * str	$opts['ssl_in']['local_cert'] - path to server certificate in PEM format.
 * * str	$opts['ssl_in']['passphrase'] - Passphrase for encrypted PEM certificate.
 * * str	$opts['ssl_in']['allow_self_signed'] - Defaults to TRUE
 * * str	$opts['ssl_in']['verify_peer'] - Defaults to FALSE
 * * array	$opts['acl'] - Access control list
 * 
 *
 * @param array	$opts - Options.
 */
function forward_port($opts) {
  foreach (['port','target','target_port'] as $i) {
    if (!isset($opts[$i])) throw new Exception('Missing '.$i.PHP_EOL);
  }
  if (!isset($opts['bind'])) $opts['bind'] = ANY_IP;
  if (!isset($opts['ssl_in'])) $opts['ssl_in'] = NULL;
  if (!isset($opts['ssl_out'])) $opts['ssl_out'] = FALSE;
  $target = $opts['target'];
  $port = $opts['target_port'];
  $ssl_out = $opts['ssl_out'];
  $srv = new ServerSocket($opts['port'],function ($main,$conn) use ($target,$port,$ssl_out) {
    FwdSocket::forward($conn,$target,$port,$ssl_out);
  }, $opts['bind'], $opts['ssl_in']);
  if (isset($opts['acl'])) $srv->register_acl($opts['acl']);
  return $srv;
}

/**
 * Forward a port through an http tunnel
 *
 * Forwards a port connection to a target host:port but uses a http
 * proxy.
 * 
 * * int	$opts['port'] - port number to listen.
 * * str	$opts['target'] - remote host to forward the connection to.
 * * int	$opts['target_port'] - remote port to forward the connection to.
 * * str	$opts['proxy'] - http proxy host.
 * * int	$opts['proxy_port'] - htty proxy port.
 * * str	$opts['bind'] - IP address to bind to.  Defaults to ANY_IP.
 * * str	$opts['http_request'] - Defaults to 'CONNECT %h:%p'.
 * * str	$opts['http_host'] - Defaults to $opts['target'].
 * * bool	$opts['ssl_out'] - Use SSL for outgoing connections.
 * * mixed	$opts['ssl_in'] - An array with SSL options and/or a path to a PEM certificate.  If an array is given, these are used for the php ssl stream context.
 * * str	$opts['ssl_in']['local_cert'] - path to server certificate in PEM format.
 * * str	$opts['ssl_in']['passphrase'] - Passphrase for encrypted PEM certificate.
 * * str	$opts['ssl_in']['allow_self_signed'] - Defaults to TRUE
 * * str	$opts['ssl_in']['verify_peer'] - Defaults to FALSE
 * * array	$opts['acl'] - Access control list
 * 
 * @param array	$opts - Options:
 */
function tunnel_port($opts) {
  foreach (['port','target','target_port','proxy','proxy_port'] as $i) {
    if (!isset($opts[$i])) throw new Exception('Missing '.$i.PHP_EOL);
  }
  if (!isset($opts['bind'])) $opts['bind'] = ANY_IP;
  if (!isset($opts['http_request'])) $opts['http_request'] = 'CONNECT %h:%p';
  if (!isset($opts['http_host'])) $opts['http_host'] = $opts['target'];
  if (!isset($opts['ssl_in'])) $opts['ssl_in'] = NULL;
  if (!isset($opts['ssl_out'])) $opts['ssl_out'] = FALSE;
  
  $target = $opts['target'];
  $target_port = $opts['target_port'];
  $proxy = $opts['proxy'];
  $proxy_port = $opts['proxy_port'];
  $http_request = $opts['http_request'];
  $http_host = $opts['http_host'];
  $ssl_out = $opts['ssl_out'];
  
  $srv = new ServerSocket($opts['port'],function ($main,$conn) use ($http_request,$http_host,$target,$target_port,$proxy,$proxy_port,$ssl_out) {
      new HttpTunnelClient($conn, $http_host,
				NetIO::host_lookup($proxy),$proxy_port,
				$target,$target_port,
				$http_request,$ssl_out);
      }, $opts['bind'], $opts['ssl_in']);
  if (isset($opts['acl'])) $srv->register_acl($opts['acl']);
  return $srv;
}

/**
 * Configure an http server
 * 
 * Configure socket server that waits for incoming http connections.
 * The following options are recognized:
 * 
 * * int	$opts['port'] - port to listen to.
 * * array	$opts['routes'] - routes to respond to.
 * * str	$opts['bind'] - Defaults to `ANY_IP`.
 * * mixed	$opts['ssl'] - An array with SSL options and/or a path to a PEM certificate.  If an array is given, these are used for the php ssl stream context.
 * * str	$opts['ssl']['local_cert'] - path to server certificate in PEM format.
 * * str	$opts['ssl']['passphrase'] - Passphrase for encrypted PEM certificate.
 * * str	$opts['ssl']['allow_self_signed'] - Defaults to TRUE
 * * str	$opts['ssl']['verify_peer'] - Defaults to FALSE
 * * array	$opts['acl'] - Access control list
 * 
 * @param array	$opts - options
 */
function http_server($opts) {
  foreach (['port','routes'] as $i) {
    if (!isset($opts[$i])) throw new Exception('Missing '.$i.PHP_EOL);
  }
  if (!isset($opts['bind'])) $opts['bind'] = ANY_IP;
  if (!isset($opts['ssl'])) $opts['ssl'] = NULL;
  $routes = $opts['routes'];
  $srv = new ServerSocket($opts['port'],function ($main,$conn) use ($routes) {
      new HttpServer($conn,$routes);
  }, $opts['bind'], $opts['ssl']);
  if (isset($opts['acl'])) $srv->register_acl($opts['acl']);
  return $srv;
}

/** http_server route for an http tunnel */
function http_tunnel() {
  return [ 'HttpTunnelServer','http_response'];
}
/** http_server route to display a 404 error (not found) */
function error_404() {
  return ['Err404Response','http_response'];
}

/** http_server route to display a 405 error (forbidden) */
function error_405() {
  return ['Err405Response','http_response'];
}

/** http_server route to redirect to a different URL */
function redir_response() {
  return ['RedirResponse','http_response'];
}

/** http_server route useful for debugging */
function debug_response() {
  return ['DebugResponse','http_response'];
}

/**
 * http_server route implementing a reverse proxy 
 *
 * @param array	$opts - Configuration options
 **/
function reverse_proxy($opts) {
  return [new ReverseProxyServer($opts),'http_response'];
}

if (count($argv) && file_exists($argv[0])) {
  //fwrite(STDERR,'Reading configuration: '.$argv[0].PHP_EOL);
  require($c = array_shift($argv));
  Logger::info('Configured from '.$c);
} else {
  foreach ([dirname(CMD), dirname(realpath(CMD)), '.'] as $d) {
    foreach (['/cfg.php','/'.CMDNAME.'-cfg.php'] as $f) {
      //echo $d.$f.PHP_EOL;
      if (file_exists($d.$f)) {
	//fwrite(STDERR,'Using configuration: '.$d.$f.PHP_EOL);
	require($c = $d.$f);
	Logger::info('Configured from '.$c);
	$f = NULL;
	break 2;
      }
    }
  }
  if ($f) die('No valid configuration file found!'.PHP_EOL);
}
try {
  MainLoop::inst()->run();
} catch (Exception $e) {
  Logger::fatal($e->GetMessage());
}


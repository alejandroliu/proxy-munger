#!/usr/bin/env php
<?php
error_reporting(E_ALL);
define('CMD',array_shift($argv));

set_include_path(get_include_path().PATH_SEPARATOR.dirname(realpath(__FILE__)));
//require('NetIO/socket.php');
require('NetIO/stream.php');
require('MainLoop.php');
require('BaseSocket.php');
require('ServerSocket.php');
require('SocketPump.php');
require('HttpSocket.php');
require('HttpTunnelClient.php');
require('HttpServer.php');
require('Responses.php');
require('Logger/basic.php');
require('FwdSocket.php');

new ServerSocket(2201,function ($main,$conn) {
  FwdSocket::forward($conn,'127.0.0.1',22);
});

$routes = [
  '/^POST\s\/vtun\/([^\/]+)\/(\d+)/' => [ 'HttpTunnelServer','http_response'],
  '' => ['DebugResponse','http_response'],
];

new ServerSocket(8000,function ($main,$conn) use ($routes) {
  new HttpServer($conn,$routes);
});
$proxy_host = 'localhost';
$proxy_ip = NetIO::host_lookup($proxy_host);
new ServerSocket(7000,function ($main,$conn) use ($proxy_host,$proxy_ip) {
  $proxy_host = 'localhost';
  new HttpTunnelClient($conn,
			$proxy_host,
			$proxy_ip, 8000,
			'localhost',22,
			'POST /vtun/%h/%p/');
});

MainLoop::inst()->run();


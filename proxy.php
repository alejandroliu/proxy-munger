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

$routes = [
  '/^POST\s\/vtun\/([^\/]+)\/(\d)/' => [ 'HttpTunnelServer','http_response'],
  '' => ['DebugResponse','http_response'],
];

new ServerSocket(8000,function ($main,$conn) use ($routes) {
  new HttpServer($conn,$routes);
});
new ServerSocket(7000,function ($main,$conn) {
  $proxy_host = 'vs1.localnet';
  new HttpTunnelClient($conn,
			$proxy_host,
			NetIO::host_lookup($proxy_host), 8000,
			'cvm1.localnet',22,
			'PORST /vtun/%h/%p/');
});

MainLoop::inst()->run();


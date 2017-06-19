<?php
//require('NetIO/socket.php');
require('NetIO/stream.php');

$def_acl = acl([
  '10.*' => ALLOW,
  '127.*' => DENY,
]);

forward_port([
  'port' => 2201,
  'target' => '127.0.0.1',
  'target_port' => 22,
  'acl' => $def_acl
]);

tunnel_port([
  'port' => 7000,
  'bind' => '0.0.0.0',
  'proxy' => '10.47.142.30',
  'proxy_port' => 8080,
  'target' => 'localhost',
  'target_port' => 22,
  'http_request' => 'CONNECT %h:%p'
]);

$routes = [
  '/^POST\s\/vtun\/([^\/]+)\/(\d+)/' => http_tunnel(),
  '/^GET\s+(\/myip\/)/' => reverse_proxy([
					'target' => '10.47.142.30',
					'port' => 8080,
					'rw_host' => 'apps.0ink.net',
					'rw_path' => '/time_planner/',
					'http_proxy' => TRUE
				]),
  '/^GET\s+(\/xip\/)/' => reverse_proxy([
					'target' => '10.47.142.30',
					'port' => 8080,
					'rw_host' => 'apps.0ink.net',
					'rw_path' => '/myip/',
					'http_proxy' => TRUE
				]),
  '' => debug_response(),
];

http_server([
  'port' => 8000,
  'bind' => '::0',
  'routes' => $routes]);

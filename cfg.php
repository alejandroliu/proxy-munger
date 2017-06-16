<?php
//require('NetIO/socket.php');
require('NetIO/stream.php');

$def_acl = new Acl([
  '10.*' => ALLOW,
  '127.*' => DENY,
]);

(new ServerSocket(2201,function ($main,$conn) {
  FwdSocket::forward($conn,'127.0.0.1',22);
}))->register_acl($def_acl);


$routes = [
  '/^POST\s\/vtun\/([^\/]+)\/(\d+)/' => [ 'HttpTunnelServer','http_response'],
  '/^GET\s+(\/myip\/)/' => [ new ReverseProxyServer([
					'target' => '10.47.142.30',
					'port' => 8080,
					'rw_host' => 'apps.0ink.net',
					'rw_path' => '/time_planner/',
					'http_proxy' => TRUE
				]), 'http_response'],
  '/^GET\s+(\/xip\/)/' => [ new ReverseProxyServer([
					'target' => '10.47.142.30',
					'port' => 8080,
					'rw_host' => 'apps.0ink.net',
					'rw_path' => '/myip/',
					'http_proxy' => TRUE
				]), 'http_response'],
  '' => ['DebugResponse','http_response'],
];

new ServerSocket(8000,function ($main,$conn) use ($routes) {
  new HttpServer($conn,$routes);
},'::0');
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


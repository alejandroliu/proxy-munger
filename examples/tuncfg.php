<?php
//sshc -> [munger](httpTunnelClient,PROXY-POST) -> [corp-proxy] ->
//		[munger:80](router)/HttpTunnelServer(POST) -> sshd?
//			  /redir to https


////////////////// LEFT
tunnel_port([
  'port' => 2201,
  'proxy' => '10.47.142.30',
  'proxy_port' => 8080,
  'http_host' => 'home.0ink.net',
  'http_request' => 'POST http://home.0ink.net/post-data/%h/%p',
  'target' => 'ow1.localnet',
  'target_port' => 23,
]);

////////////////// RIGHT

$routes = [
  '/^POST\s\/post-data\/([^\/]+)\/(\d+)/' => http_tunnel(),
  '' => debug_response(),
];

http_server([
  'port' => 80,
  'routes' => $routes
]);

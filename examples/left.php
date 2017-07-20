<?php
//require('NetIO/socket.php');
require('NetIO/stream.php');

tunnel_port([
  'port' => 2201,
  'bind' => '0.0.0.0',
  'proxy' => '10.47.142.30',
  'proxy_port' => 8080,
  'target' => 'ts3.iliu.net',
  'target_port' => 443,
  'http_request' => 'CONNECT %h:%p'
]);


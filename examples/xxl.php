<?php
//require('NetIO/socket.php');
require('NetIO/stream.php');
require('Logger/basic.php');
//require('Logger/syslog.php');Logger::init(CMDNAME,LOG_PERROR|LOG_PID,LOG_DAEMON);
//require('Logger/file.php');Logger::cfg('log.txt');


http_server([
  'port' => 8000,
  'bind' => '::0',
  'routes' => [
    '' => redir_response(),
  ],
  'ssl' => [
    'local_cert' => dirname(realpath(__FILE__)).'/server.pem',
    'passphrase' => 'comet',
  ],
]);



http_server([
  'port' => 8443,
  'bind' => '::0',
  'routes' => [
    '/^POST\s\/post-data\/([^\/]+)\/(\d+)/' => http_tunnel(),
    '' => debug_response(),
  ],
  'ssl' => [
    'local_cert' => dirname(realpath(__FILE__)).'/server.pem',
    'passphrase' => 'comet',
  ],
]);

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

echo CMD.' '.CMDNAME.PHP_EOL;

require(dirname(CMD).'/cfg.php');


MainLoop::inst()->run();


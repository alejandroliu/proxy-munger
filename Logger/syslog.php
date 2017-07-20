<?php
require(dirname(realpath(__FILE__)).'/interface.php');

class Logger implements ILogger {
  static public function init($ident = 'proxy',$log_opts = LOG_PERROR, $facility = LOG_USER) {
    openlog($ident,$log_opts,$facility);
  }
  static protected function _log($pri,$msg) {
    syslog($pri,$msg);
  }
  static public function fatal($msg) {
    self::_log(LOG_CRIT,$msg);
    exit(1);
  }
  static public function warning($msg) { self::_log(LOG_WARN,$msg); }
  static public function error($msg) { self::_log(LOG_ERR,$msg); }
  static public function info($msg) { self::_log(LOG_INFO,$msg); }
  static public function notice($msg) { self::_log(LOG_NOTICE,$msg); }
  static public function debug($msg) { self::_log(LOG_DEBUG,$msg); }
}



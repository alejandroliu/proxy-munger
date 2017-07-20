<?php
/**
 * Basic syslog Logger implementation
 * @package proxy_munger
 */
 
require(dirname(realpath(__FILE__)).'/interface.php');

/**
 * This class implements Logger for syslog output
 * @package proxy_munger
 */
class SysLogger implements ILogger {
  /**
   * Set-up syslogger
   * @param str $ident - string to identify this daemon
   * @param int $log_opts - Defaults to LOG_PERROR
   * @param int $facility - Defaults to LOG_USER
   */
  static public function init($ident = 'proxy',$log_opts = LOG_PERROR, $facility = LOG_USER) {
    openlog($ident,$log_opts,$facility);
  }
  /** @ignore */
  static protected function _log($pri,$msg) {
    syslog($pri,$msg);
  }
  /**
   * Logs the message and dies 
   * @param str $msg - Message to show
   */
  static public function fatal($msg) {
    self::_log(LOG_CRIT,$msg);
    exit(1);
  }
  /**
   * Logs a warning 
   * @param str $msg - Message to show
   */
  static public function warning($msg) { self::_log(LOG_WARN,$msg); }
  /**
   * Logs an error
   * @param str $msg - Message to show
   */
  static public function error($msg) { self::_log(LOG_ERR,$msg); }
  /**
   * Logs an informational message
   * @param str $msg - Message to show
   */
  static public function info($msg) { self::_log(LOG_INFO,$msg); }
  /**
   * Logs a notice
   * @param str $msg - Message to show
   */
  static public function notice($msg) { self::_log(LOG_NOTICE,$msg); }
  /**
   * Debug message
   * @param str $msg - Message to show
   */
  static public function debug($msg) { self::_log(LOG_DEBUG,$msg); }
}


/** @ignore */
class Logger extends SysLogger {
}

<?php
/**
 * Basic Logger implementation
 * @package proxy_munger
 */
 
require(dirname(realpath(__FILE__)).'/interface.php');

/**
 * This class implements Logger for STDERR output
 * @package proxy_munger
 */
class BasicLogger implements ILogger {
  /** @ignore */
  static public function out($msg) {
    fwrite(STDERR,$msg.PHP_EOL);
  }
  /**
   * Logs the message and dies 
   * @param str $msg - Message to show
   */
  static public function fatal($msg) {
    self::out($msg);
    exit(1);
  }

  /**
   * Logs a warning 
   * @param str $msg - Message to show
   */
  static public function warning($msg) { self::out($msg); }
  /**
   * Logs an error
   * @param str $msg - Message to show
   */
  static public function error($msg) { self::out($msg); }
  /**
   * Logs an informational message
   * @param str $msg - Message to show
   */
  static public function info($msg) { self::out($msg); }
  /**
   * Logs a notice
   * @param str $msg - Message to show
   */
  static public function notice($msg) { self::out($msg); }
  /**
   * Debug message
   * @param str $msg - Message to show
   */
  static public function debug($msg) { self::out($msg); }
}

/** @ignore */
class Logger extends BasicLogger {
}



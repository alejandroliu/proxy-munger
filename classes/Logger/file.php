<?php
/**
 * File  based Logger implementation
 * @package proxy_munger
 */
 
require(dirname(realpath(__FILE__)).'/interface.php');

/**
 * This class implements Logger for file output
 * @package proxy_munger
 */
class FileLogger implements ILogger {
  /** Out log file */
  static $fpath = '/dev/null';
  /**
   * Configure Logger
   * @param str $npath - Path to log file
   */
  static public function cfg($npath) {
    self::$fpath = $npath;
  }
  /** @ignore */
  static public function out($msg) {
    $fp = fopen(self::$fpath,'a');
    if ($fp === FALSE) return;
    fwrite($fp,$msg.PHP_EOL);
    fclose($fp);
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
class Logger extends FileLogger {
}

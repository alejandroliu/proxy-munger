<?php
/**
 * Logger interface
 * @package proxy_munger
 */
 
/**
 * This interface defines the basic logging operations
 */
interface ILogger {
  /**
   * Logs the message and dies 
   * @param str $msg - Message to show
   */
  static public function fatal($msg);
  /**
   * Logs a warning 
   * @param str $msg - Message to show
   */
  static public function warning($msg);
  /**
   * Logs an error
   * @param str $msg - Message to show
   */
  static public function error($msg);
  /**
   * Logs an informational message
   * @param str $msg - Message to show
   */
  static public function info($msg);
  /**
   * Logs a notice
   * @param str $msg - Message to show
   */
  static public function notice($msg);
  /**
   * Debug message
   * @param str $msg - Message to show
   */
  static public function debug($msg);
}


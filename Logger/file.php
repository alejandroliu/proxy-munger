<?php
require(dirname(realpath(__FILE__)).'/interface.php');

class Logger implements ILogger {
  static $fpath = '/dev/null';
  static public function cfg($npath) {
    self::$fpath = $npath;
  }
  static public function out($msg) {
    $fp = fopen(self::$fpath,'a');
    if ($fp === FALSE) return;
    fwrite($fp,$msg.PHP_EOL);
    fclose($fp);
  }
  static public function fatal($msg) {
    self::out($msg);
    exit(1);
  }
  static public function warning($msg) { self::out($msg); }
  static public function error($msg) { self::out($msg); }
  static public function info($msg) { self::out($msg); }
  static public function notice($msg) { self::out($msg); }
  static public function debug($msg) { self::out($msg); }
}



<?php
require(dirname(realpath(__FILE__)).'/interface.php');

class Logger implements ILogger {
  static public function out($msg) {
    fwrite(STDERR,$msg.PHP_EOL);
  }
  static public function fatal($msg) {
    self::out($msg);
    exit(1);
  }
  static public function warning($msg) { self::out($msg); }
  static public function error($msg) { self::out($msg); }
  static public function info($msg) { self::out($msg); }
  static public function notice($msg) { self::out($msg); }
}



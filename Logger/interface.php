<?php
interface ILogger {
  static public function fatal($msg);
  static public function warning($msg);
  static public function error($msg);
  static public function info($msg);
  static public function notice($msg);
  static public function debug($msg);
}


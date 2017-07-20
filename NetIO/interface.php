<?php
/**
 * NetIO interface
 * @package proxy_munger
 */
 
/**
 * This interface defines the basic network I/O operations
 */
interface INetIO {
  static public function shutdown($sock,$how);
  static public function close($sock);
  static public function select(&$rd,&$wr,&$ex,$timeout = NULL);
  static public function strerror($mx = NULL);
  static public function accept($sock);
  static public function get_peername($sock);
  static public function read($sock,$bsz = 4096);
  static public function write($sock,$data);
  
  static public function new_server($port,$addr = '0.0.0.0',$ssl=NULL);
  static public function new_client($target,$port,$ssl=FALSE);
  static public function host_lookup($name);
}  

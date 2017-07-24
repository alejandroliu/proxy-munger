<?php
/**
 * Basic socket class
 * @package proxy_munger
 */
 
/** Basic socket class */
abstract class BaseSocket {
  /** Resource socket */
  protected $sock;
  /**
   * Constructor
   * @param rsrc	$sock - Socket resource
   */
  public function __construct($sock) {
    $this->sock = $sock;
  }
  /**
   * Returns the socket resource
   * @return rsrc
   */
  public function get_socket() {
    return $this->sock;
  }
}

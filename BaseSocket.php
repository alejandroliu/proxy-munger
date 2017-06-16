<?php
abstract class BaseSocket {
  protected $sock;
  public function __construct($sock) {
    $this->sock = $sock;
  }
  public function get_socket() {
    return $this->sock;
  }
}

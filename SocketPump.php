<?php
/**
 * Basic data pump
 * @package proxy_munger
 */
 
/**
 * Class that implements a data pump from the MainLoop
 */
class SocketPump extends BaseSocket {
  /** Output socket */
  protected $wr_socket;
  /**
   * Constructor
   * 
   * @param rsrc	$in - Input socket
   * @param rsrc	$out - Output socket
   */
  public function __construct($in,$out) {
    $this->wr_socket = $out;
    parent::__construct($in);
    MainLoop::inst()->register_socket($in,[$this,'pump']);
  }
  /**
   * Data pump
   * 
   * This is the MainLoop callback, will copy data from input to output
   * 
   * @param MainLoop	$main - MainLoop object
   * @param rsrc	$in - Input socket
   */
  public function pump($main,$in) {
    if (($data = NetIO::read($in)) !== FALSE && strlen($data) > 0) {
      //echo($data);//DEBUG
      NetIO::write($this->wr_socket,$data);
      return;
    }
    $main->unregister_socket($in);
    $main->shutdown_socket($this->wr_socket,NetIO::SHUT_WR);
    $main->shutdown_socket($in,NetiO::SHUT_RD);
  }
}

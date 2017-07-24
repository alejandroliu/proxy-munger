<?php
interface IHttpResponder {
  static public function http_response($server,$re,$hdr,$data);
}
interface ICfgResponder {
  public function http_response($server,$re,$hdr,$data);
}

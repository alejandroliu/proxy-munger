<?php
class Acl {
  const ALLOW = TRUE;
  const DENY = FALSE;
  const NONE = '';
  protected $rules;
  public function evaluate($address) {
    foreach ($this->rules as $re => $result) {
      if ($re == self::NONE) return $result;
      if (substr($re,0,1) == '/' && substr($re,-1,1) == '/') {
	if (preg_match($re,$address)) return $result;
	continue;
      }
      if (fnmatch($re,$address)) return $result;
    }
    return self::DENY; // This is the default...
  }
  public function __construct($list = NULL) {
    if ($list === NULL) $list = [];
    $this->rules = $list;
  }
}

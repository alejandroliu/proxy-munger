<?php
/**
 * Access Control List class
 * 
 * @package proxy_munger
 */
 
/**
 * Access Control List class
 *
 * This class contains rules used to control IP level access to
 * resources.  Rules are of the form:
 *
 * * _'match-specification'_ => ALLOW|DENY
 * 
 * Match specifications can be regular expressions (any string
 * surrounded by `/`) or shell wildcard expressions.
 * 
 */
class Acl {
  /** This match is allowed */
  const ALLOW = TRUE;
  /** This match is denied */
  const DENY = FALSE;
  /** Will match anything */
  const ANY = '';
  /** Array containing rules */
  protected $rules;
  /**
   * Evaluate an address against this ACL
   * 
   * Given an address (as IPv4 or IPv6 string), it will evaluate
   * ACL rules.
   * 
   * @param str	$address - IPv4 or IPv6 in string format
   * @return ALLOW|DENY
   */
  public function evaluate($address) {
    foreach ($this->rules as $re => $result) {
      if ($re == self::ANY) return $result;
      if (substr($re,0,1) == '/' && substr($re,-1,1) == '/') {
	if (preg_match($re,$address)) return $result;
	continue;
      }
      if (fnmatch($re,$address)) return $result;
    }
    return self::DENY; // This is the default...
  }
  /**
   * Constructor
   * 
   * @param array	$list - Access control list rules
   */
  public function __construct($list = NULL) {
    if ($list === NULL) $list = [];
    $this->rules = $list;
  }
}

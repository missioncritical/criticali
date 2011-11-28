<?php
// Copyright (c) 2008-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package controller */

/**
 * Utilities for protection against cross site request forgeries.
 *
 * This class is used by {@link Controller_Base} to implement the
 * <var>protect_from_forgery</var> filter and the
 * <var>form_authenticity_token</var> method.
 */
class Controller_ForgeryProtection {
  
  const KEY = 'CSRFToken';
  const PARAMETER = 'request_token';

  /**
   * Return the current session's request token
   * @return string
   */
  public static function authenticity_token() {
    if (!isset($_SESSION[self::KEY]))
      $_SESSION[self::KEY] = base64_encode(self::random_bytes(32));
    return $_SESSION[self::KEY];
  }
  
  /**
   * Test the current request for authenticity.  Returns true if the
   * request has been verified as authentic or does not require
   * verification (i.e. GET requests).
   * @return boolean
   */
  public static function is_request_verified() {
    return ( (strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') ||
      (isset($_POST[self::PARAMETER]) && ($_POST[self::PARAMETER] == self::authenticity_token())) );
  }
  
  /**
   * Return a string of random bytes
   *
   * @param int $size  The number of random bytes
   * @return string
   */
  public static function random_bytes($size) {
    if (function_exists('openssl_random_pseudo_bytes')) {
      return openssl_random_pseudo_bytes($size);
    }
    
    $bytes = '';
    while (strlen($bytes) < $size) {
      $bytes .= chr(mt_rand(0, 255));
    }
    return $bytes;
  }
  
  /**
   * Controller_ForgeryProtection may not be instantiated
   */
  private function __construct() {
    throw new Exception("Cannot instantiate Controller_ForgeryProtection");
  }
}

?>
<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * Utility to log PHP errors when they occur.  Requires log_error to be
 * registered with set_error_handler.
 */
class Support_ErrorLogger {
  /** Set to the previously registered handler which will be invoked after logging the error */
  protected static $previousHandler;
  
  /**
   * Constructor -- cannot be instantiated
   */
  private function __construct() {
    throw new Exception("Cannot instantiate Support_ErrorLogger");
  }
  
  /**
   * Register the logger
   */
  public static function register() {
    self::$previousHandler = set_error_handler(array('Support_ErrorLogger', 'handle_error'));
  }
  
  /**
   * Invoked when errors occur.  Respects the error_reporting setting.
   */
  public static function handle_error($errno, $errstr, $file = null, $line = null, $ctx = null) {
    // respect error_reporting
    if (!(error_reporting() & $errno))
      return self::default_error_handler($errno, $errstr, $file, $line, $ctx);
    
    // assemble information to include with the message
    $info = '';
    if ($file) {
      $info = " - $file";
      if ($line) $info .= ":$line";
    }
    $info = $errno . $info;
    
    
    // log it
    $levels = array(
      E_ERROR => 'error',
      E_WARNING => 'warn',
      E_CORE_ERROR => 'error',
      E_CORE_WARNING => 'warn',
      E_COMPILE_ERROR => 'error',
      E_COMPILE_WARNING => 'warn',
      E_USER_ERROR => 'error',
      E_USER_WARNING => 'warn'
    );
    
    $method = isset($levels[$errno]) ? $levels[$errno] : 'info';
    
    $logger = Support_Resources::logger('System');
    $logger->$method("$errstr [$info]");
  
    // pass on the error itself
    return self::default_error_handler($errno, $errstr, $file, $line, $ctx);
  }
  
  /**
   * Passes on the actual error to the previous handler or to the default handler
   */
  protected static function default_error_handler($errno, $errstr, $file, $line, $ctx) {
    if (self::$previousHandler)
      return call_user_func(self::$previousHandler, $errno, $errstr, $file, $line, $ctx);
    else
      return false;
  }
  
}

?>
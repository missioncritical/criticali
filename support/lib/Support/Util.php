<?php
// Copyright (c) 2008-2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * Shared utility functions for common tasks
 */
class Support_Util {
  
  protected static $cached_models;
  
  /**
   * Constructor -- no, you can't instantiate this class; it's just
   * helper methods
   */
  private function __construct() {
    throw new Exception("Instantiation not allowed.");
  }

  /**
   * Redirect to a given URL
   *
   * @param string $url  The URL or fragment to redirect to
   * @param bool   $exit If true (default), exists after returning the redirect
   */
  static public function redirect($url, $exit = true) {
    // redirect must contain a full path
    $fullPath = $url;
    // schema
    if ( ('http:' != substr($url, 0, 5)) && ('https:' != substr($url, 0, 6)) ) {
      // host
      if ('//' != substr($url, 0, 2)) {
        // directory
        if ('/' != substr($url, 0, 1)) {
          $dir = dirname($_SERVER['PHP_SELF']);
          // for windows, make sure forward slashes are used in URLs
          $dir = str_replace('\\', '/', $dir);
          if ($dir != '/')
            $dir .= '/';
          $fullPath = $dir . $fullPath;
        }

        $fullPath = '//' . $_SERVER['HTTP_HOST'] . $fullPath;
      }

      if (isset($_SERVER['HTTPS']) and ($_SERVER['HTTPS'] == 'on'))
        $fullPath = 'https:' . $fullPath;
      else
        $fullPath = 'http:' . $fullPath;
    }

    if (!headers_sent())
      header("Location: " . $fullPath);
    else
      echo "<script>window.location = '" . addslashes($fullPath)
        . "';</script>\n";

    if ($exit)
      exit;
  }
  
  /**
   * Validate an options array against a set of permitted options.  The
   * permitted options are specified as an array whose keys are the
   * allowable option keys (e.g. <code>array('a'=>1, 'b'=>1)</code> would
   * allow option keys of <code>'a'</code> and <code>'b'</code> but not
   * <code>'c'</code>.  If any invalid option is specified in the test
   * array, a Support_UnknownOptionError is thrown.
   *
   * @param array $test  The options array to test
   * @param array $allowed The list of permitted options (see description)
   */
  public static function validate_options($test, $allowed) {
    foreach ($test as $key=>$value) {
      if (!array_key_exists($key, $allowed))
        throw new Support_UnknownOptionError($key);
    }
  }
  
  /**
   * Return the options, if any, provided in a list of arguments and
   * remove the options from the arguments. This function assumes that
   * the last argument in the list is an array of options if that
   * argument is an associative array (see 
   * Support_ArrayHelper::is_associative()). If no options are found,
   * an empty array is returned.
   *
   * @param array &$arguments The argument list to test/modify
   * @return array
   */
  public static function options_from_argument_list(&$arguments) {
    $lastIdx = count($arguments) - 1;

    if ($lastIdx >= 0 && is_array($arguments[$lastIdx]) &&
        Support_ArrayHelper::is_associative($arguments[$lastIdx]))
      return array_pop($arguments);
    else
      return array();
  }
  
  /**
   * Returns an instance of the named class. This is a convenience method
   * intended for performing class-level operations on models. Constructed
   * instances are cached and returned to any future callers requesting
   * the same class. If the class provides a method named set_readonly,
   * it is invoked with a value of true.
   *
   * In other words, this utility is a simple way to grab an immutable
   * instance of a model class without littering up memory with lots of
   * short-lived objects not associated with data records.
   *
   * @param string $className The name of the class to instantiate
   * @return object
   */
  public static function model($className) {
    if (!self::$cached_models)
      self::$cached_models = array();
    
    if (!isset(self::$cached_models[$className])) {
      if (!class_exists($className))
        throw new Support_UnknownClassError($className);
      
      $model = new $className();
      if (method_exists($model, 'set_readonly'))
        $model->set_readonly(true);
      
      self::$cached_models[$className] = $model;
    }
    
    return self::$cached_models[$className];
  }
  
}

?>
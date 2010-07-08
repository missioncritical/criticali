<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Utilities for working with classes and files containing them
 */
class CriticalI_ClassUtils {
  
  /**
   * Construtor -- may not be instantiated
   */
  private function __construct() {
    throw new Exception("CriticalI_ClassUtils may not be instantiated.");
  }
  
  /**
   * Convert a path into the class name that should be contained within the file
   *
   * @param string $filename  The filename to convert
   * @param string $base      The optional base path to remove from the beginning
   */
  public static function class_name($filename, $base = '') {
    // normalize the paths
    $filename = str_replace("\\", '/', $filename);
    $base = empty($base) ? '' : str_replace("\\", '/', $base);
    
    // strip the base
    if (substr($filename, 0, strlen($base)) == $base)
      $filename = substr($filename, strlen($base));
      
    // no absolute paths or other prefixes for our purposes
    $matches = array();
    if (preg_match("/^[^:\\/]+:(.*)$/", $filename, $matches))
      $filename = $matches[1];
    if (preg_match("/^\\/\\/[^\\/]+(\\/.*)$/", $filename, $matches))
      $filename = $matches[1];
    if (substr($filename, 0, 1) == '/')
      $filename = substr($filename, 1);
    
    // split it
    $parts = explode('/', $filename);
    
    // folders without an initial capital are not part of the name
    while ($parts && (!preg_match('/^[A-Z]/', $parts[0]))) {
      array_shift($parts);
    }
    
    // no extension for the last element
    if ($parts) {
      $last = array_pop($parts);
      if (($pos = strrpos($last, '.')) !== false)
        $last = substr($last, 0, $pos);
      if (!empty($last)) $parts[] = $last;
    }
    
    // assemble the class name
    return ($parts ? implode('_', $parts) : '');
  }
  
  /**
   * Convert a class name into the path and file that should contain it
   *
   * @param string $class_name  The class name to convert
   * @return string
   */
  public static function file_name($class_name) {
    $parts = explode('_', $class_name);
    return implode('/', $parts) . ".php";
  }
}

?>
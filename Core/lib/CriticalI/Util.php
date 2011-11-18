<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * A generic collection of utilities used by multiple classes.
 */
class CriticalI_Util {
  
  /**
   * Create a temporary file and return its name. The file is
   * automatically cleaned up at the end of the script.
   *
   * @param string $prefix Optional prefix to use for the file name
   * @return string
   */
  public static function tempfile($prefix = null) {
    // pick a temporary directory
    if (function_exists('sys_get_temp_dir'))
      $dir = sys_get_temp_dir();
    elseif ($dir = getenv('TMP'))
      ;
    elseif ($dir = getenv('TEMP'))
      ;
    elseif ($dir = getenv('TMPDIR'))
      ;
    else
      $dir = '/tmp';
    
    // must have a prefix
    if (!$prefix) $prefix = 'criticali';
    
    $filename = @tempnam($dir, $prefix);
    
    if ($filename == false)
      throw new Exception("Failed to create temporary file");
    
    register_shutdown_function(create_function('',
      "if (file_exists('$filename')) unlink('$filename');"));
    
    return $filename;
  }
  
}

?>
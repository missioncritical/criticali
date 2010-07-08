<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Autoloader used by the command line tool.
 */
function __autoload($classname) {
  global $CRITICALI_SEARCH_DIRECTORIES;
  
  $parts = explode('_', $classname);
  $filename = implode('/', $parts) . ".php";
  
  foreach ($CRITICALI_SEARCH_DIRECTORIES as $dir) {
    if (file_exists("$dir/$filename")) {
      require_once($filename);
      return;
    }
  }
}

?>
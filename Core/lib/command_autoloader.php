<?php

/**
 * Autoloader used by the command line tool.
 */
function __autoload($classname) {
  global $VULTURE_SEARCH_DIRECTORIES;
  
  $parts = explode('_', $classname);
  $filename = implode('/', $parts) . ".php";
  
  foreach ($VULTURE_SEARCH_DIRECTORIES as $dir) {
    if (file_exists("$dir/$filename")) {
      require_once($filename);
      return;
    }
  }
}

?>
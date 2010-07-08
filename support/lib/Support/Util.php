<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * Shared utility functions for common tasks
 */
class Support_Util {
  
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
  
}

?>
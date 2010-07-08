<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * A utility for searching the file system for filenames matching a
 * pattern.  The only significant difference between this utility and the
 * glob() function is that multiple patterns can be passed in if they are
 * separated by a comma and then matched against a single starting
 * directory.  Whitespace is also trimmed from patterns, allowing for
 * easier creation of long pattern sets.
 */
class CriticalI_Globber {
  /**
   * Return an array of pathnames matching the provided pattern and
   * origin directory.  Returned filenames are complete paths (not
   * relative to the origin).  The returned filenames are not sorted.
   *
   * @param string $origin The origin directory (or an empty string)
   * @param string $pattern The pattern to match
   * @return array
   */
  public static function match($origin, $pattern) {
    $prefix = empty($origin) ? '' : "$origin/";
    $matches = array();
    foreach(explode(',', $pattern) as $thisPattern) {
      $matches = array_merge($matches, glob($prefix . trim($thisPattern), GLOB_NOSORT));
    }
    return $matches;
  }
}

?>
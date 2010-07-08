<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Array helper functions.
 *
 * @package support
 */

/**
 * Additional helper functions for working with arrays
 */
class Support_ArrayHelper {
  /**
   * Constructor -- not you can't instantiate this class; it's just
   * helper methods
   */
  private function __construct() {
    throw new Exception("Instantiation not allowed.");
  }

  /**
   * Perform a basic two-way merge on pre-sorted input arrays.
   *
   * This function will reset the pointer for both arrays.
   *
   * @param array $array1  The first array to merge
   * @param array $array2  The second array to merge
   *
   * @return array  An array containing the unique list of values from both arrays
   */
  public static function merge_sorted(&$array1, &$array2) {
    $output = array();
    $count1 = count($array1);
    $count2 = count($array2);
    $val1 = reset($array1);
    $val2 = reset($array2);

    while (($count1 > 0) || ($count2 > 0)) {
      $cmp = ($count1 > 0 && $count2 > 0) ? strcmp($val1, $val2) : 0;

      if (($count2 == 0) || ($cmp < 0)) {
        $output[] = $val1;
        $count1--;
        $val1 = next($array1);
      } elseif (($count1 == 0) || ($cmp > 0)) {
        $output[] = $val2;
        $count2--;
        $val2 = next($array2);
      } else {
        $output[] = $val1;
        $count1--;
        $count2--;
        $val1 = next($array1);
        $val2 = next($array2);
      }
    }

    reset($array1);
    reset($array2);

    return $output;
  }

  /**
   * Returns a new array containing all elements from source except
   * any that are also present in exclude.  Both input arrays must be
   * sorted.
   *
   * This function will reset the pointer for both arrays.
   *
   * @param array $source  The source array
   * @param array $exclude The collection of elements to exclude
   *
   * @return array
   */
  public static function exclude_sorted(&$source, &$exclude) {
    $output = array();
    $countSrc = count($source);
    $countExl = count($exclude);
    $valSrc = reset($source);
    $valExl = reset($exclude);

    while (($countSrc > 0) || ($countExl > 0)) {
      $cmp = ($countSrc > 0 && $countExl > 0) ? strcmp($valSrc, $valExl) : 0;

      if (($countExl == 0) || ($cmp < 0)) {
        $output[] = $valSrc;
        $countSrc--;
        $valSrc = next($source);
      } elseif (($countSrc == 0) || ($cmp > 0)) {
        $countExl--;
        $valExl = next($exclude);
      } else {
        $countSrc--;
        $countExl--;
        $valSrc = next($source);
        $valExl = next($exclude);
      }
    }

    reset($source);
    reset($exclude);

    return $output;
  }

  /**
   * Similar to exlude_sorted, only the keys in source are compared
   * to the values in exclude and only those key/value pairs whose
   * keys are not in exlude are returned. The source array must be
   * sorted by keys and the exclude array must be sorted by value.
   *
   * This function will reset the pointer for the exclude array.
   *
   * @param array $source  The source array
   * @param array $exclude The collection of elements to exclude
   *
   * @return array
   */
  public static function kexclude_sorted(&$source, &$exclude) {
    $output = array();
    $count = count($exclude);
    $valExl = reset($exclude);

    foreach ($source as $key=>$value) {
      $cmp = $count > 0 ? strcmp($key, $valExl) : 0;
      while ($cmp > 0) {
        $count--;
        $valExl = next($exclude);
        $cmp = $count > 0 ? strcmp($key, $valExl) : 0;
      }

      if (($count == 0) || ($cmp < 0)) {
        $output[$key] = $value;
      } else {
        $count--;
        $valExl = next($exclude);
      }
    }

    reset($exclude);

    return $output;
  }

  /**
   * Returns a new array containing only those values from source
   * which are also present in incl.  Both input arrays must be
   * sorted.
   *
   * This function will reset the pointer for both arrays.
   *
   * @param array $source  The source array
   * @param array $incl    The collection of elements to include
   *
   * @return array
   */
  public static function intersect_sorted(&$source, &$incl) {
    $output = array();
    $countSrc = count($source);
    $countInc = count($incl);
    $valSrc = reset($source);
    $valInc = reset($incl);

    while (($countSrc > 0) || ($countInc > 0)) {
      $cmp = ($countSrc > 0 && $countInc > 0) ? strcmp($valSrc, $valInc) : 0;

      if (($countInc == 0) || ($cmp < 0)) {
        $countSrc--;
        $valSrc = next($source);
      } elseif (($countSrc == 0) || ($cmp > 0)) {
        $countInc--;
        $valInc = next($incl);
      } else {
        $output[] = $valSrc;
        $countSrc--;
        $countInc--;
        $valSrc = next($source);
        $valInc = next($incl);
      }
    }

    reset($source);
    reset($incl);

    return $output;
  }

  /**
   * Similar to intersect_sorted, only the keys in source are
   * compared to the value in incl and only those key/value pairs
   * whose keys are in incl are returned. The source array must be
   * sorted by keys and the incl array must be sorted by value.
   *
   * This function will reset the pointer for the incl array.
   *
   * @param array $source  The source array
   * @param array $incl    The collection of elements to include
   *
   * @return array
   */
  public static function kintersect_sorted(&$source, &$incl) {
    $output = array();
    $count = count($incl);
    $valInc = reset($incl);

    foreach ($source as $key=>$value) {
      $cmp = $count > 0 ? strcmp($key, $valInc) : 0;
      while ($cmp < 0) {
        $count--;
        $valInc = next($incl);
        $cmp = $count > 0 ? strcmp($key, $valInc) : 0;
      }

      if (($count == 0) || ($cmp > 0)) {
        // no-op
      } else {
        $output[$key] = $value;
        $count--;
        $valInc = next($incl);
      }
    }

    reset($incl);

    return $output;
  }

};

?>

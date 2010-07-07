<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Abstraction layer and cache for accessing class-level meta data in
 * ActiveRecord.
 *
 * @copyright Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
 */

/**
 * ActiveRecord_InfoMgr is a helper class to manage meta information
 * for active record classes.  This allows information such as column
 * mappings, associations, etc. to be gathered once per class and
 * shared between instances.
 */
class ActiveRecord_InfoMgr {
  /**
   * Associative array containing meta information keyed by class name
   */
  protected static $cache = array();

  /**
   * Retrieve the meta information for a class instance.
   *
   * @param ActiveRecord $inst  An instance of the ActiveRecord class
   *                            to retrieve the information for.  If no
   *                            information is known for the class,
   *                            load_meta_info is invoked on the instance.
   *
   * @return ActiveRecord_MetaInfo
   */
  public static function meta_info($inst) {
    $className = get_class($inst);
    if (isset(self::$cache[$className]))
      return self::$cache[$className];

    $info = $inst->load_meta_info();
    self::$cache[$className] = $info;
    return $info;
  }

  /**
   * Constructor may not be called
   */
  private function __construct() {
    throw new Exception("Instantiation of ActiveRecord_InfoMgr is prohibited.");
  }
}

?>
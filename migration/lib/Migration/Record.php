<?php
// Copyright (c) 2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package migration */

class Migration_Record extends ActiveRecord_Base {
  // non-persistent values
  public $performed = false;
  public $missing = false;
  
  protected function init_class () {
    $this->set_table_name(Cfg::get('migration_table_name', 'migrations'));
    $this->validates_presence_of(array('name'));
    $this->validates_uniqueness_of('name', array('scope'=>'scope'));
  }
  
  /**
   * Return the version number for this migration.
   *
   * Migration files are named <version>_<class_name>.php, so the first
   * portion is the version number.
   */
  public function version() {
    $parts = explode('_', $this->name, 2);
    return $parts[0];
  }

  /**
   * Return the class name for this migration.
   *
   * Migration files are named <version>_<class_name>.php, so the second
   * portion is the class name.
   */
  public function class_name() {
    $parts = explode('_', $this->name, 2);
    return $parts[1];
  }
}

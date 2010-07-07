<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * ActiveRecord_Association is the base class from which all association
 * implementations inherit.
 */
abstract class ActiveRecord_Association {
  protected $name;
  protected $class_name;
  protected $foreign_key;
  
  /**
   * Return the name of this association
   * @return string
   */
  public function name() { return $this->name; }
  
  /**
   * Return the class name for this association
   * @return string
   */
  public function class_name() { return $this->class_name; }
  
  /**
   * Return the foreign key name for this association
   * @return string
   */
  public function foreign_key() { return $this->foreign_key; }
  
  /**
   * Implements the 'include' behavior for a find operation
   *
   * @param array $results The result set to process
   */
  public abstract function do_include(&$results);
}

?>
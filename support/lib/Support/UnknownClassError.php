<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * Thrown if an unknown/undeclared class is referenced
 */
class Support_UnknownClassError extends Exception {
  protected $className;
  
  /**
   * Constructor
   */
  public function __construct($className) {
    parent::__construct("No such class \"$className\"");
    $this->className = $className;
  }
  
  /**
   * Accessor for the class name
   */
  public function className() { return $this->className; }
}

?>
<?php
// Copyright (c) 2008-2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package helper */

/**
 * Helper_MethodInfo is a structure containing information about a helper method
 */
class Helper_MethodInfo {
  
  const STANDARD_FUNCTION = 1;
  const MODIFIER_FUNCTION = 2;
  const BLOCK_FUNCTION = 3;
  
  public $name;
  public $type;
  public $class_name;
  public $method_name;
  public $parameter_names;
  public $defaults;
  public $callback;
  
  public function __construct($name = null, $type = Helper_MethodInfo::STANDARD_FUNCTION,
    $class_name = null, $method_name = null, $parameter_names = array(), $defaults = array(),
    $callback = null) {
      
    $this->name            = $name;
    $this->type            = $type;
    $this->class_name      = $class_name;
    $this->method_name     = $method_name;
    $this->parameter_names = $parameter_names;
    $this->defaults        = $defaults;
    $this->callback        = $callback;
  }

}

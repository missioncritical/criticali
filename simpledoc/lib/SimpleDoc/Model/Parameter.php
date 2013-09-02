<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * The documentation-related information for a parameter of a method or
 * function.
 *
 * @package simpledoc
 */
class SimpleDoc_Model_Parameter {
  
  /** The name of the parameter */
  public $name;
  
  /** The default value of the parameter (as a string of PHP code) */
  public $default;
  
  /** The type of the parameter (as a string) */
  public $type;
  
  /** Is the parameter passed by reference? */
  public $is_byref;
  
  /** The parameter description */
  public $description;
  
  /**
   * Constructor
   *
   * @param string $name The name of the parameter
   * @param string $default The default value of the parameter (if any)
   * @param string $type The type of the parameter
   * @param boolean $is_byref Flag indicating if the parameter is passed by reference
   * @param string $description The parameter description
   */
  public function __construct($name = null, $default = null, $type = null, $is_byref = false,
                              $description = null) {

    $this->name = $name;
    $this->default = $default;
    $this->type = $type;
    $this->is_byref = (boolean)$is_byref;
    $this->description = $description;
  }
  
}

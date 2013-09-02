<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * The documentation-related information for a function declared in
 * the code.
 *
 * @package simpledoc
 */
class SimpleDoc_Model_Function extends SimpleDoc_Model_Commentable {
  
  /** The name of the function */
  public $name;
  
  /** The return type of the function (as a string) */
  public $type;
  
  /** Does the function return by reference? */
  public $is_byref;
  
  /** An array of SimpleDoc_Model_Parameter objects for this function */
  public $parameters;
  
  /** An optional description of the return value of this function */
  public $return_description;
  
  /**
   * Constructor
   *
   * @param string $name The name of the method
   * @param string $type The type of the method
   * @param boolean $is_byref Flag indicating if the method returns by reference
   * @param SimpleDoc_Model_Comment Any doc comment associated with the method
   */
  public function __construct($name = null, $type = null, $is_byref = false, $comment = null) {
    parent::__construct($comment);

    $this->name = $name;
    $this->type = $type;
    $this->is_byref = (boolean)$is_byref;
    $this->parameters = array();
    $this->return_description = null;
  }
  
  /**
   * Return the named parameter declaration, or false if not found.
   *
   * @param string $name The name of the parameter to find
   * @return SimpleDoc_Model_Parameter
   */
  public function parameter_with_name($name) {
    foreach ($this->parameters as $param) {
      if ($param->name == $name)
        return $param;
    }
    
    return false;
  }

  /**
   * Add a parameter declaration to the function
   *
   * @param string $name The name of the parameter
   * @param string $default The default value of the parameter (if any)
   * @param string $type The type of the property
   * @param boolean $is_byref Flag indicating if the parameter is passed by reference
   * @param string $description The parameter description
   */
  public function add_parameter($name = null, $default = null, $type = null, $is_byref = null,
                                $description = null) {

    $existing = $this->parameter_with_name($name);
    if ($existing === false) {

      $this->parameters[] = new SimpleDoc_Model_Parameter($name, $default, $type, $is_byref, $description);

    } else {
      if (is_string($default)) $existing->default = $default;
      if (is_string($type)) $existing->type = $type;
      if (is_bool($is_byref)) $existing->is_byref = $is_byref;
      if (is_string($description)) $existing->description = $description;
    }
  }

}

<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * The documentation-related information for a class method declared in
 * the code.
 *
 * @package simpledoc
 */
class SimpleDoc_Model_Method extends SimpleDoc_Model_Commentable {
  
  /** The name of the method */
  public $name;
  
  /** The return type of the method (as a string) */
  public $type;
  
  /** Does the method return by reference? */
  public $is_byref;
  
  /** Is the method public? */
  public $is_public;
  
  /** Is the method protected? */
  public $is_protected;

  /** Is the method private? */
  public $is_private;

  /** Is the method abstract? */
  public $is_abstract;
  
  /** Is the method final? */
  public $is_final;
  
  /** Is the method static? */
  public $is_static;
  
  /** True if this method was not declared in code, but comes from tags in the class documentation */
  public $is_synthetic;
  
  /** An array of SimpleDoc_Model_Parameter objects for this method */
  public $parameters;
  
  /** An optional description of the return value of this method */
  public $return_description;
  
  /**
   * Constructor
   *
   * @param string $name The name of the method
   * @param string $type The type of the method
   * @param boolean $is_byref Flag indicating if the method returns by reference
   * @param boolean $is_public Flag indicating if the method is public
   * @param boolean $is_protected Flag indicating if the method is protected
   * @param boolean $is_private Flag indicating if the method is private
   * @param boolean $is_abstract Flag indicating if the method is abstract
   * @param boolean $is_final Flag indicating if the method is final
   * @param boolean $is_static Flag indicating if the method is static
   * @param boolean $is_synthetic Flag indicating if the method was declared by tags (synthetically)
   * @param SimpleDoc_Model_Comment Any doc comment associated with the method
   */
  public function __construct($name = null, $type = null, $is_byref = false, $is_public = false,
                              $is_protected = false, $is_private = false, $is_abstract = false,
                              $is_final = false, $is_static = false, $is_synthetic = false,
                              $comment = null) {
    parent::__construct($comment);

    $this->name = $name;
    $this->type = $type;
    $this->is_byref = (boolean)$is_byref;
    $this->is_public = (boolean)$is_public;
    $this->is_protected = (boolean)$is_protected;
    $this->is_private = (boolean)$is_private;
    $this->is_abstract = (boolean)$is_abstract;
    $this->is_final = (boolean)$is_final;
    $this->is_static = (boolean)$is_static;
    $this->is_synthetic = (boolean)$is_synthetic;
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
   * Add a parameter declaration to the method
   *
   * @param string $name The name of the parameter
   * @param string $default The default value of the parameter (if any)
   * @param string $type The type of the parameter
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
  
  /**
   * Return the visibility of this method as a string
   * @return string
   */
  public function visibility() {
    if ($this->is_public)
      return 'public';
    if ($this->is_protected)
      return 'protected';
    if ($this->is_private)
      return 'private';

    return '';
  }

  /**
   * Return the parameters as a string matching how they would be declared in PHP
   * @return string
   */
  public function parameter_declaration() {
    $p = array();
    
    foreach ($this->parameters as $param) {
      $str = '';
      
      if ($param->is_byref) $str .= '&';
      
      $str .= '$' . $param->name;
      
      if ($param->default) $str .= ' = ' . $param->default;
      
      $p[] = $str;
    }
    
    return implode(', ', $p);
  }
  
}

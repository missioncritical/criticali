<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * The documentation-related information for a class declared in
 * the code.
 *
 * @package simpledoc
 */
class SimpleDoc_Model_Class extends SimpleDoc_Model_Commentable {
  
  /** The name of the class */
  public $name;
  
  /** The name of the file the class was delcared in */
  public $filename;
  
  /** The name of the package associated with the class */
  public $package_name;
  
  /** Indicates if the class is final */
  public $is_final;
  
  /** Indicates if the class is abstract */
  public $is_abstract;
  
  /** The name of the parent class */
  public $parent_class_name;
  
  /** An array of interface names this class implements */
  public $interface_names;
  
  /** An array of SimpleDoc_Model_Constant objects, one object for each constant declared in the class */
  public $constants;
  
  /** An array of SimpleDoc_Model_Property objects, one object for each property declared in the class */
  public $properties;
  
  /** An array of SimpleDoc_Model_Method objects, one object for each method declared in the class */
  public $methods;

  /**
   * Constructor
   *
   * @param string $name The name of the class
   * @param string $filename The name of the file the class was declared in
   * @param string $package_name The name of the package associated with the class
   */
  public function __construct($name, $filename, $package_name) {
    parent::__construct();

    $this->name = $name;
    $this->filename = $filename;
    $this->package_name = $package_name;
    $this->is_final = false;
    $this->is_abstract = false;
    $this->parent_class_name = null;
    $this->interface_names = array();
    $this->constants = array();
    $this->properties = array();
    $this->methods = array();
  }
  
  /**
   * Add the name of an interface this class implements
   *
   * @param string $interface_name The name of the interface the class implements
   */
  public function add_implemented_interface($interface_name) {
    if (array_search($interface_name, $this->interface_names) === false)
      $this->interface_names[] = $interface_name;
  }
  
  /**
   * Add a constant declaration to the class
   *
   * @param string $name The name of the constant
   * @param string $value The value of the constant
   * @param SimpleDoc_Model_Comment Any doc comment associated with the constant
   */
  public function add_constant($name, $value, $comment) {
    // nodoc constants are ignored
    if ($comment && $comment->tags['nodoc'])
      return;
    
    $this->constants[] = new SimpleDoc_Model_Constant($name, $value, $comment);
  }

  /**
   * Add a property declaration to the class
   *
   * @param string $name The name of the property
   * @param string $default The default value of the constant (if any)
   * @param string $type The type of the property
   * @param boolean $is_public Flag indicating if the property is public
   * @param boolean $is_protected Flag indicating if the property is protected
   * @param boolean $is_private Flag indicating if the property is private
   * @param boolean $is_static Flag indicating if the property is static
   * @param boolean $is_synthetic Flag indicating if the property was declared by tags (synthetically)
   * @param string  $rw The value for the `$rw` property.
   * @param SimpleDoc_Model_Comment Any doc comment associated with the property
   */
  public function add_property($name = null, $default = null, $type = null, $is_public = null,
                              $is_protected = null, $is_private = null, $is_static = null,
                              $is_synthetic = null, $rw = null, $comment = null) {
    // nodoc properties are ignored
    if ($comment && $comment->tags['nodoc'])
      return;
    
    $existing = $this->property_with_name($name);
    if ($existing === false) {

      if (!$rw) $rw = 'rw';
      
      $this->properties[] = new SimpleDoc_Model_Property($name, $default, $type, $is_public,
        $is_protected, $is_private, $is_static, $is_synthetic, $rw, $comment);

    } else {
      if (is_string($default)) $existing->default = $default;
      if (is_string($type)) $existing->type = $type;
      if (is_bool($is_public)) $existing->is_public = $is_public;
      if (is_bool($is_protected)) $existing->is_protected = $is_protected;
      if (is_bool($is_private)) $existing->is_private = $is_private;
      if (is_bool($is_static)) $existing->is_static = $is_static;
      if (is_bool($is_synthetic)) $existing->is_synthetic = $is_synthetic;
      if ($rw === 'r' || $rw === 'w' || $rw === 'rw') $existing->rw = $rw;
      if ($comment instanceof SimpleDoc_Model_Comment) $existing->comment = $comment;
    }
  }
  
  /**
   * Return the named property declaration, or false if not found.
   *
   * @param string $name The name of the property to find
   * @return SimpleDoc_Model_Property
   */
  public function property_with_name($name) {
    foreach ($this->properties as $prop) {
      if ($prop->name == $name)
        return $prop;
    }
    
    return false;
  }
  
  /**
   * Add a method declaration to the class
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
   * @param SimpleDoc_Model_Comment Any doc comment associated with the property
   * @return SimpleDoc_Model_Method The added method object
   */
  public function add_method($name = null, $type = null, $is_byref = false, $is_public = null,
                              $is_protected = null, $is_private = null, $is_abstract = null,
                              $is_final = null, $is_static = null, $is_synthetic = null, $comment = null) {
    // nodoc methods are ignored
    if ($comment && $comment->tags['nodoc'])
      return;
    
    $method = $this->method_with_name($name);
    if ($method === false) {

      $method = new SimpleDoc_Model_Method($name, $type, $is_byref, $is_public,
        $is_protected, $is_private, $is_abstract, $is_final, $is_static, $is_synthetic, $comment);

      $this->methods[] = $method;

    } else {
      if (is_string($type)) $existing->type = $type;
      if (is_bool($is_byref)) $existing->is_byref = $is_byref;
      if (is_bool($is_public)) $existing->is_public = $is_public;
      if (is_bool($is_protected)) $existing->is_protected = $is_protected;
      if (is_bool($is_private)) $existing->is_private = $is_private;
      if (is_bool($is_abstract)) $existing->is_abstract = $is_final;
      if (is_bool($is_final)) $existing->is_abstract = $is_final;
      if (is_bool($is_static)) $existing->is_static = $is_static;
      if (is_bool($is_synthetic)) $existing->is_synthetic = $is_synthetic;
      if ($comment instanceof SimpleDoc_Model_Comment) $existing->comment = $comment;
    }
    
    return $method;
  }
  
  /**
   * Return the named method declaration, or false if not found.
   *
   * @param string $name The name of the method to find
   * @return SimpleDoc_Model_Method
   */
  public function method_with_name($name) {
    foreach ($this->methods as $method) {
      if ($method->name == $name)
        return $method;
    }
    
    return false;
  }
  
  /**
   * Test if this is an interface
   */
  public function is_interface() {
    return false;
  }

}

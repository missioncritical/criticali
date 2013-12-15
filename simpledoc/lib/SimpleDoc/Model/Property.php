<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * The documentation-related information for a class property declared in
 * the code.
 *
 * @package simpledoc
 */
class SimpleDoc_Model_Property extends SimpleDoc_Model_Commentable {
  
  /** The name of the property */
  public $name;
  
  /** The default value of the property (as a string of PHP code) */
  public $default;
  
  /** The type of the property (as a string) */
  public $type;
  
  /** Is the property public? */
  public $is_public;
  
  /** Is the property protected? */
  public $is_protected;

  /** Is the property private? */
  public $is_private;

  /** Is the property static? */
  public $is_static;
  
  /** True if this property was not declared in code, but comes from tags in the class documentation */
  public $is_synthetic;
  
  /** A string value that is 'r' for read-only properties, 'w', for write-only properties, or 'rw' for read write properties */
  public $rw;

  /**
   * Constructor
   *
   * @param string $name The name of the property
   * @param string $default The default value of the property (if any)
   * @param string $type The type of the property
   * @param boolean $is_public Flag indicating if the property is public
   * @param boolean $is_protected Flag indicating if the property is protected
   * @param boolean $is_private Flag indicating if the property is private
   * @param boolean $is_static Flag indicating if the property is static
   * @param boolean $is_synthetic Flag indicating if the property was declared by tags (synthetically)
   * @param string  $rw The value for the `$rw` property.
   * @param SimpleDoc_Model_Comment Any doc comment associated with the property
   */
  public function __construct($name = null, $default = null, $type = null, $is_public = false,
                              $is_protected = false, $is_private = false, $is_static = false,
                              $is_synthetic = false, $rw = null, $comment = null) {
    parent::__construct($comment);

    $this->name = $name;
    $this->default = $default;
    $this->type = $type;
    $this->is_public = (boolean)$is_public;
    $this->is_protected = (boolean)$is_protected;
    $this->is_private = (boolean)$is_private;
    $this->is_static = (boolean)$is_static;
    $this->is_synthetic = (boolean)$is_synthetic;
    $this->rw = $rw;
  }
  
  /**
   * Return the visibility of this property as a string
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
  
}

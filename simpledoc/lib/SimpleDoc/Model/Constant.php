<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * The documentation-related information for a constant declared in
 * the code.
 *
 * @package simpledoc
 */
class SimpleDoc_Model_Constant extends SimpleDoc_Model_Commentable {
  
  /** The name of the constant */
  public $name;
  
  /** The value of the constant (as a string of PHP code) */
  public $value;
  
  /**
   * Constructor
   *
   * @param string $name The name of the constant
   * @param string $value The value of the constant
   * @param SimpleDoc_Model_Comment Any doc comment associated with the constant
   */
  public function __construct($name = null, $value = null, $comment = null) {
    parent::__construct($comment);

    $this->name = $name;
    $this->value = $value;
  }
  
}

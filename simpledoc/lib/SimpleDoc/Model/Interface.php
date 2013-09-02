<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * The documentation-related information for an interface declared in
 * the code. From a documentation perspective, an interface is very
 * similar to a class with the exception that it will not have any
 * properties. Instead it contains only constants and abstract methods.
 *
 * @package simpledoc
 */
class SimpleDoc_Model_Interface extends SimpleDoc_Model_Class {

  /**
   * Test if this is an interface
   */
  public function is_interface() {
    return true;
  }

}

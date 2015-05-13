<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * A package represents a high-level grouping of items to document,
 * essentially a category. Packages may not be nested, they are top-level
 * objects.
 *
 * @package simpledoc
 */
class SimpleDoc_Model_Package {
  
  /** The name of the package */
  public $name;
  
  /** An array of SimpleDoc_Model_Files contained in this package */
  public $files;
  
  /** An array of SimpleDoc_Model_Classes contained in this package */
  public $classes;
  
  /** An array of SimpleDoc_Model_Functions contained in this package */
  public $functions;
  
  /** An array of SimpleDoc_Model_Guides contained in this package */
  public $guides;
  
  /**
   * Constructor
   *
   * @param string $name The name of the package
   */
  public function __construct($name) {
    $this->name = $name;
    $this->files = array();
    $this->classes = array();
    $this->functions = array();
    $this->guides = array();
  }
  
  /**
   * Return the index guide, if any, for this package
   * @return SimpleDoc_Model_Guide
   */
  public function index_guide() {
    foreach ($this->guides as $guide) {
      if ($guide->is_index)
        return $guide;
    }
    
    return null;
  }

}
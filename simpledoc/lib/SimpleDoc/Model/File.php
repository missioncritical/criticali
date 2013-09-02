<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * The documentation-related information for a scanned file.
 *
 * @package simpledoc
 */
class SimpleDoc_Model_File extends SimpleDoc_Model_Commentable {
  
  /** The full path to the file including the file name */
  public $path;
  
  /** The name of the file, excluding any path prefix */
  public $filename;
  
  /** An array of class names declared in the file */
  public $class_names;
  
  /** An array of functions declared in the file */
  public $function_names;
  
  /** An an array of SimpleDoc_Model_Constants (defines) declared in the file */
  public $consts;
  
  /** An array of variable names declared or assigned to outside of any function */
  public $variable_names;
  
  /**
   * Constructor
   *
   * @param string $filename The full path to the file
   * @param string $prefix An optional prefix to omit from the file name
   */
  public function __construct($filename, $prefix = null) {
    parent::__construct();

    $this->path = $filename;
    if ($prefix && substr($filename, 0, strlen($prefix)) == $prefix)
      $this->filename = substr($filename, strlen($prefix));
    else
      $this->filename = $filename;
    
    $this->class_names = array();
    $this->function_names = array();
    $this->consts = array();
    $this->variable_names = array();
  }
}

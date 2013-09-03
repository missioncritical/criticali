<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * A guide is a file containing no code, but only additional
 * documentation that should be included with the set of generated
 * documentation.
 *
 * @package simpledoc
 */
class SimpleDoc_Model_Guide {
  
  /** The name of the guide */
  public $name;

  /** The guide text */
  public $text;
  
  /** The collection of tags found within the guide */
  public $tags;
  
  /** Flag that is true if this guide should be used as the package index */
  public $is_index;
  
  /**
   * Constructor
   *
   * @param string $name The guide name
   * @param string $text The guide text
   */
  public function __construct($name = null, $text = null) {
    $this->name = $name;
    $this->tags = array();
    $this->text = null;
    $this->is_index = false;
    
    if ($text)
      $this->set_text($text);
  }
  
  /**
   * Set the text of the guide and extract any tags found
   *
   * @param string $text The text of the guide
   */
  public function set_text($text) {
    $this->text = SimpleDoc_TagReader::parse_tags($text, $this->tags);
  }
  
}

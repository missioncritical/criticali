<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * A base class for commentable/documentable items in a file.
 *
 * @package simpledoc
 */
class SimpleDoc_Model_Commentable {
  
  /** The comment associated with this item */
  public $comment;
  
  /**
   * Constructor
   *
   * @param string $comment    The item's comment
   */
  public function __construct($comment = null) {
    $this->comment = $comment;
  }
}

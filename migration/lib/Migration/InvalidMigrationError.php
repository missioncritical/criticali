<?php
// Copyright (c) 2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

class Migration_InvalidMigrationError extends Exception {
  public $filename;
  
  /**
   * Constructor
   *
   * @param string $filename  The name of the invlalid migration
   */
  public function __construct($filename, $errorMsg) {
    $this->filename = $filename;
    
    parent::__construct("The migration file \"$filename\" $errorMsg.");
  }
}

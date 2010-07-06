<?php

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

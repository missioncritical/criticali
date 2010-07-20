<?php

/**
 * Thrown if an invalid option key is supplied
 */
class Support_UnknownOptionError extends Exception {
  protected $optionName;
  
  /**
   * Constructor
   */
  public function __construct($optionName) {
    parent::__construct("Unrecognized option \"$optionName\"");
    $this->optionName = $optionName;
  }
  
  /**
   * Accessor for the option name
   */
  public function optionName() { return $this->optionName; }
}

?>
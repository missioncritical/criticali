<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

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
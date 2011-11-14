<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * List command
 */
class CriticalI_Command_PropSet extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('prop-set', 'Set the value of a property in the repository', <<<DESC
  criticali prop-set property_name value
  
Set the property property_name in the repository to value.
DESC
, array() );
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    if (count($this->args) != 2) {
      fwrite(STDERR, $this->help_string());
      exit(1);
    }
    
    CriticalI_Property::set($this->args[0], $this->args[1]);
  }
  
}

?>
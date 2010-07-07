<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Rebuild command
 */
class CriticalI_Command_Rebuild extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('rebuild', 'Rebuild the repository package listing', <<<DESC
  criticali rebuild
  
Rebuilds the package listing in the repository from the
packages that can be discovered there.  If the package
listing file is missing or has been damaged try
'criticali --skip-packages rebuild' to get around any
errors related to the file.
DESC
);
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    CriticalI_Package_List::rebuild();
  }
    
}

?>
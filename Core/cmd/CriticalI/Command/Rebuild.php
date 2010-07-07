<?php

/**
 * Rebuild command
 */
class Vulture_Command_Rebuild extends Vulture_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('rebuild', 'Rebuild the repository package listing', <<<DESC
  vulture rebuild
  
Rebuilds the package listing in the repository from the
packages that can be discovered there.  If the package
listing file is missing or has been damaged try
'vulture --skip-packages rebuild' to get around any
errors related to the file.
DESC
);
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    Vulture_Package_List::rebuild();
  }
    
}

?>
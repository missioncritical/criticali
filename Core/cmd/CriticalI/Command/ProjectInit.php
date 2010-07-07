<?php

/**
 * ProjectInit command
 */
class CriticalI_Command_ProjectInit extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('project-init', 'Initialize a new project', <<<DESC
  criticali project-init [options] name
  
Initializes a new project in the directory 'name'.
DESC
, array(
  new CriticalI_OptionSpec('inside-public', CriticalI_OptionSpec::NONE, null, 'Place application files in a private folder inside the public web directory.'),
  new CriticalI_OptionSpec('outside-public', CriticalI_OptionSpec::NONE, null, 'Place application files outside of the public web directory.')));
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    if (count($this->args) !== 1) {
      fwrite(STDERR, $this->help_string());
      exit(1);
    }
    
    $type = isset($this->options['outside-public']) ?
      CriticalI_Project::OUTSIDE_PUBLIC :
      CriticalI_Project::INSIDE_PUBLIC;
    
    CriticalI_Project_Manager::init($this->args[0], $type);
  }

}

?>
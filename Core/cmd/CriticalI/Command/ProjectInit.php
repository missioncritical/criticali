<?php

/**
 * ProjectInit command
 */
class Vulture_Command_ProjectInit extends Vulture_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('project-init', 'Initialize a new project', <<<DESC
  vulture project-init [options] name
  
Initializes a new project in the directory 'name'.
DESC
, array(
  new Vulture_OptionSpec('inside-public', Vulture_OptionSpec::NONE, null, 'Place application files in a private folder inside the public web directory.'),
  new Vulture_OptionSpec('outside-public', Vulture_OptionSpec::NONE, null, 'Place application files outside of the public web directory.')));
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
      Vulture_Project::OUTSIDE_PUBLIC :
      Vulture_Project::INSIDE_PUBLIC;
    
    Vulture_Project_Manager::init($this->args[0], $type);
  }

}

?>
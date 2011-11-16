<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

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
    
    if (isset($this->options['outside-public']))
      $type = CriticalI_Project::OUTSIDE_PUBLIC;
    elseif (isset($this->options['inside-public']))
      $type = CriticalI_Project::INSIDE_PUBLIC;
    else
      $type = $this->get_default_project_type();
    
    CriticalI_Project_Manager::init($this->args[0], $type);
  }
  
  /**
   * Return the default project type
   */
  protected function get_default_project_type() {
    $str = CriticalI_Property::get('project.default_type', 'inside-public');
    
    if (strtolower(trim($str)) == 'outside-public')
      return CriticalI_Project::OUTSIDE_PUBLIC;
    else
      return CriticalI_Project::INSIDE_PUBLIC;
  }
  
}

?>
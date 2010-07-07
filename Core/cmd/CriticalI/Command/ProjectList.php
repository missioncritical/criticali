<?php

/**
 * ProjectInit command
 */
class CriticalI_Command_ProjectList extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('project-list', 'List packages in a project', <<<DESC
  criticali project-list [project_root]
  
List the packages installed in a project.  If no project
root directory is given, assumes the current working
directory.
DESC
);
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    if (count($this->args) > 1) {
      fwrite(STDERR, $this->help_string());
      exit(1);
    }

    $prj = CriticalI_Project_Manager::load(count($this->args) > 0 ? $this->args[0] : null);
    
    $pkgs = $prj->packages();
    ksort($pkgs);
    
    foreach ($pkgs as $pkg=>$ver) {
      print "$pkg ($ver)\n";
    }
  }

}

?>
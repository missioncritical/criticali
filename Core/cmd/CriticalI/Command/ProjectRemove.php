<?php

/**
 * ProjectRemove command
 */
class CriticalI_Command_ProjectRemove extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('project-remove', 'Add a package to a project', <<<DESC
  criticali project-remove [options] package1 [...packageN]
  
Removes one or more packages from a project.  If any other
packages in the project depend on the one being removed,
the operation will fail unless the --force option has been
specified. 
DESC
, array(
  new CriticalI_OptionSpec('project', CriticalI_OptionSpec::REQUIRED, 'directory', 'Specify the project directory.  Defaults to the current working directory.'),
  new CriticalI_OptionSpec('force', CriticalI_OptionSpec::NONE, null, 'Skips dependency handling and forcibly removes the package regardless of any installed packages which may depend on it.')));
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    if (count($this->args) < 1) {
      fwrite(STDERR, $this->help_string());
      exit(1);
    }
    
    // load the project
    $prj = CriticalI_Project_Manager::load(isset($this->options['project']) ?
                                          $this->options['project'] : null);
    // remove the packages
    foreach ($this->args as $pkg) {
      $prj->remove($pkg, (isset($this->options['force']) ? false : true));
    }

  }

}

?>
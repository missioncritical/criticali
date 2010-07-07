<?php

/**
 * ProjectMigrate command
 */
class Migration_Command_ProjectMigrate extends CriticalI_Command_ProjectEnvironment {
  
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('project-migrate', 'Run database migrations', <<<DESC
  criticali project-migrate [options]
  
Run database migrations to reach the specified version
(the default is the most current version).
DESC
, array(
  new CriticalI_OptionSpec('project', CriticalI_OptionSpec::REQUIRED, 'directory', 'Specify the project directory.  Defaults to the current working directory.'),
  new CriticalI_OptionSpec('version', CriticalI_OptionSpec::REQUIRED, 'version_number', 'Specify the target version number.  Defaults to the most current version.')
  ));
  }
  
  /**
   * Run the command
   */
  protected function run_command() {
    if (count($this->args) < 1) {
      fwrite(STDERR, $this->help_string());
      exit(1);
    }
    
    // set up the project environment
    $this->init_environment(isset($this->options['project']) ? $this->options['project'] : null);
    
    $runner = new Migration_Runner($this->project_private_dir() . "/migrations");
    $runner->run(isset($this->options['version']) ? $this->options['version'] : null);
    
    fwrite(STDERR, "Database at version ".$runner->version()."\n");
  }

}

?>
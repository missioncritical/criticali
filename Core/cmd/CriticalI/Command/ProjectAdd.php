<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Listener for installation status
 */
class CriticalI_Command_ProjectAdd_Listener implements CriticalI_Project_StatusListener {
  public $showDebug = false;
  
/**
 * Normal informational message
 *
 * @param CriticalI_Project $project  The project the operation is occurring on
 * @param CriticalI_Package $package  The package the operation is occurring on (may be null)
 * @param string          $message  The message
 */
  public function info($project, $package, $message) {
    fwrite(STDERR, $message . "\n");
  }

/**
 * Debug-level message
 *
 * @param CriticalI_Project $project  The project the operation is occurring on
 * @param CriticalI_Package $package  The package the operation is occurring on (may be null)
 * @param string          $message  The message
 */
  public function debug($project, $package, $message) {
    if ($this->showDebug)
      fwrite(STDERR, $message . "\n");
  }
}

/**
 * ProjectAdd command
 */
class CriticalI_Command_ProjectAdd extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('project-add', 'Add a package to a project', <<<DESC
  criticali project-add [options] package1 [...packageN]
  
Adds one or more packages to a project.  Only packages which exist in the
repository may be added.  Automatically adds any additional packages
required by the ones being added.  This can be forcibly disabled through
the use of the --ignore-dependencies option. 
DESC
, array(
  new CriticalI_OptionSpec('project', CriticalI_OptionSpec::REQUIRED, 'directory', 'Specify the project directory.  Defaults to the current working directory.'),
  new CriticalI_OptionSpec('version', CriticalI_OptionSpec::REQUIRED, 'number', 'Specify the version number of the package to install.  Defaults to the most current version in the repository.'),
  new CriticalI_OptionSpec('ignore-dependencies', CriticalI_OptionSpec::NONE, null, 'Skips dependency handling and installs the package whether its dependencies are satisfied or not.  This may result in a non-functional package if dependencies are missing from the project.'),
  new CriticalI_OptionSpec('verbose', CriticalI_OptionSpec::NONE, null, 'Displays lots of information as the installation progresses.'),
  new CriticalI_OptionSpec('quiet', CriticalI_OptionSpec::NONE, null, 'Limits output to error messages and any required prompts for information.'),
  new CriticalI_OptionSpec('no', CriticalI_OptionSpec::NONE, null, 'Assumes no as the answer to any prompts for information.'),
  new CriticalI_OptionSpec('yes', CriticalI_OptionSpec::NONE, null, 'Assumes yes as the answer to any prompts for information.')
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
    
    // load the project
    $prj = CriticalI_Project_Manager::load(isset($this->options['project']) ?
                                          $this->options['project'] : null);
    
    $status = new CriticalI_Command_ProjectAdd_Listener();
    if ($this->options['verbose'])
      $status->showDebug = true;
    if (! $this->options['quiet'])
      $prj->set_status_listener($status);
    
    // plan what is to be installed
    $planner = new CriticalI_Project_ChangePlanner($prj);
    
    $plan = $planner->install_plan(
                  $this->args,
                  isset($this->options['version']) ? $this->options['version'] : null,
                  isset($this->options['ignore-dependencies']) ? false : true
            );
    
    if ( (!$this->options['quiet']) || ((!$this->options['yes']) && (!$this->options['no'])) )
      $this->display_plan($plan);
    
    $proceed = false;
    
    if ($this->options['yes'] || $this->options['no'])
      $proceed = $this->options['no'] ? false : true;
    else
      $proceed = $this->prompt_confirm("Install these packages?", true);

    if ($proceed)
      $prj->perform($plan);
  }
  
  /**
   * Display the plan for confirmation
   */
  protected function display_plan($plan) {
    $table = new CriticalI_Command_TableFormatter(array('border-cell'=>'  '));
    $table->set_header(array('Package', 'Version'));
    
    foreach ($plan->add_list() as $item) {
      $table->add_row(array($item->package()->name(), $item->version_string()));
    }
    
    print "\nThe following packages will be installed:\n\n";
    print $table->to_string();
    print "\n";
  }

}

?>
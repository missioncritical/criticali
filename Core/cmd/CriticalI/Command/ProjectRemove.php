<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Listener for removal status
 */
class CriticalI_Command_ProjectRemove_Listener implements CriticalI_Project_StatusListener {
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
 * ProjectRemove command
 */
class CriticalI_Command_ProjectRemove extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('project-remove', 'Add a package to a project', <<<DESC
  criticali project-remove [options] package1 [...packageN]
  
Removes one or more packages from a project.  If any other packages in the
project depend on the one being removed, the operation will fail unless the
--force option has been specified. 
DESC
, array(
  new CriticalI_OptionSpec('project', CriticalI_OptionSpec::REQUIRED, 'directory', 'Specify the project directory.  Defaults to the current working directory.'),
  new CriticalI_OptionSpec('force', CriticalI_OptionSpec::NONE, null, 'Skips dependency handling and forcibly removes the package regardless of any installed packages which may depend on it.'),
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
    $status = new CriticalI_Command_ProjectRemove_Listener();
    if (! $this->options['quiet'])
      $prj->set_status_listener($status);
    
    // plan what is to be installed
    $planner = new CriticalI_Project_ChangePlanner($prj);
    
    try {
      $plan = $planner->remove_plan(
                    $this->args,
                    null,
                    isset($this->options['force']) ? false : true
              );
    } catch (CriticalI_ChangeManager_HasDependentError $e) {
      $this->show_dependency_errors($prj, $this->args);
      exit(1);
    }
    
    if ( (!$this->options['quiet']) || ((!$this->options['yes']) && (!$this->options['no'])) )
      $this->display_plan($plan);
    
    $proceed = false;
    
    if ($this->options['yes'] || $this->options['no'])
      $proceed = $this->options['no'] ? false : true;
    else
      $proceed = $this->prompt_confirm("Remove these packages?", true);

    if ($proceed)
      $prj->perform($plan);
  }

  /**
   * Display the plan for confirmation
   */
  protected function display_plan($plan) {
    $table = new CriticalI_Command_TableFormatter(array('border-cell'=>'  '));
    $table->set_header(array('Package', 'Version'));
    
    $list = $plan->remove_list();
    usort($list,
      create_function('$a,$b', 'return strcmp($a->package()->name(), $b->package()->name());'));
    
    foreach ($list as $item) {
      $table->add_row(array($item->package()->name(), $item->version_string()));
    }
    
    print "\nThe following packages will be removed:\n\n";
    print $table->to_string();
    print "\n";
  }
  
  /**
   * Display complete information about dependency errors
   */
  protected function show_dependency_errors($prj, $packages) {
    $s = count($this->args) == 1 ? '' : 's';
    
    fwrite(STDERR, "\n" .
      wordwrap("The package$s cannot be removed because of the requirements " .
      "of other packages in the project. Conflicting dependencies are " .
      "shown below:") . "\n\n");
    
    $what = $this->calculate_dependency_conflicts($prj, $packages);
    ksort($what);
    
    $table = new CriticalI_Command_TableFormatter(array('border-cell'=>'  '));
    $table->set_header(array('Package', 'Required By'));
    
    foreach ($what as $name=>$requiredBy) {
      sort($requiredBy);
      $table->add_row( array($name, implode(', ', $requiredBy)) );
    }
    
    fwrite(STDERR, $table->to_string() . "\n");
  }

  /**
   * Calculate the dependencies that are causing conflicts
   */
  protected function calculate_dependency_conflicts($prj, $packages) {
    $conflicts = array();
    
    $map = array();
    foreach ($packages as $name) { $map[$name] = 1; }
    
    $list = $prj->package_list();
    
    foreach ($list as $package) {
      $pkg = $package->newest();
      
      if (!isset($map[$package->name()])) {
        foreach ($pkg->property('dependencies', array()) as $name=>$version) {
          if (isset($map[$name])) {
            if (!isset($conflicts[$name])) $conflicts[$name] = array();
            
            $conflicts[$name][] = $package->name();
          }
        }
      }
    }
    
    return $conflicts;
  }

}

?>

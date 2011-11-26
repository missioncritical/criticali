<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Remove command
 */
class CriticalI_Command_Remove extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('remove', 'Remove a package from the repository', <<<DESC
  criticali remove [options] package1 [...packageN]
  
Removes one or more packages from the repository.  If any other packages
in the repository depend on the one being removed, the operation will fail
unless the --force option has been specified.
DESC
, array(
  new CriticalI_OptionSpec('force', CriticalI_OptionSpec::NONE, null, 'Skips dependency handling and forcibly removes the package regardless of any installed packages which may depend on it.'),
  new CriticalI_OptionSpec('no', CriticalI_OptionSpec::NONE, null, 'Assumes no as the answer to any prompts for information.'),
  new CriticalI_OptionSpec('quiet', CriticalI_OptionSpec::NONE, null, 'Limits output to error messages and any required prompts for information.'),
  new CriticalI_OptionSpec('version', CriticalI_OptionSpec::REQUIRED, 'number', 'Specify the version number of the package to remove.  Defaults to the most current version installed.'),
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
    
    // plan what is to be removed
    $planner = new CriticalI_ChangeManager_RepositoryPlanner();
    
    try {
      $plan = $planner->remove_plan(
                    $this->args,
                    isset($this->options['version']) ? $this->options['version'] : null,
                    isset($this->options['force']) ? false : true
              );
    } catch (CriticalI_ChangeManager_HasDependentError $e) {
      $this->show_dependency_errors($this->args);
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
      CriticalI_Package_List::perform($plan);
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
  protected function show_dependency_errors($packages) {
    $s = count($this->args) == 1 ? '' : 's';
    
    fwrite(STDERR, "\n" .
      wordwrap("The package$s cannot be removed because of the requirements " .
      "of other packages in the repository. Conflicting dependencies are " .
      "shown below:") . "\n\n");
    
    $what = $this->calculate_dependency_conflicts($packages);
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
  protected function calculate_dependency_conflicts($packages) {
    $conflicts = array();
    
    $list = CriticalI_Package_List::get();
    
    $ver = isset($this->options['version']) ?
      implode('.', CriticalI_Package_Version::canonify_version($this->options['version'])) :
      false;
    
    $map = array();
    foreach ($packages as $name) {
      if ($ver && isset($list[$name]) && isset($list[$name][$ver])) {
        $map[$name] = $list[$name][$ver];
      } elseif (isset($list[$name]))
        $map[$name] = $list[$name]->newest();
    }
        
    foreach ($list as $package) {
      
      if (!isset($map[$package->name()])) {

        foreach ($package as $pkg) {
          foreach ($pkg->property('dependencies', array()) as $name=>$version) {
            if (isset($map[$name])) {
              $satisfiedBy = isset($list[$name]) ? $list[$name]->satisfy_dependency($version) : null;
              
              if ((!$satisfiedBy) || ($satisfiedBy->version_string() == $map[$name]->version_string())) {
                if (!isset($conflicts[$name]))
                  $conflicts[$name] = array();
                $conflicts[$name][] = $package->name();
              }
            }
          }
        }
        
      }
    }
    
    return $conflicts;
  }

}

?>
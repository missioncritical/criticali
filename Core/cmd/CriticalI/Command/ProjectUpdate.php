<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Listener for installation status
 */
class CriticalI_Command_ProjectUpdate_Listener implements CriticalI_Project_StatusListener {
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
 * ProjectUpdate command
 */
class CriticalI_Command_ProjectUpdate extends CriticalI_Command {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct('project-update', 'Update a package to a project', <<<DESC
  criticali project-update [options] package1 [...packageN]

Updates one or more packages in a project.  Only packages which have been
added to a project may be updated. Any new packages required as part of
the update are automatically added. This can be forcibly disabled through
the use of the --ignore-dependencies option. Note that using the
--ignore-dependencies will also cause the operation to proceed even if
other packages in the project may be broken as a result of the version
change.
DESC
, array(
  new CriticalI_OptionSpec('project', CriticalI_OptionSpec::REQUIRED, 'directory', 'Specify the project directory.  Defaults to the current working directory.'),
  new CriticalI_OptionSpec('version', CriticalI_OptionSpec::REQUIRED, 'number', 'Specify the version number of the package to upgrade to.  Defaults to the most current version in the repository.'),
  new CriticalI_OptionSpec('ignore-dependencies', CriticalI_OptionSpec::NONE, null, 'Skips dependency handling and upgrades the package whether its dependencies are satisfied or not.  This may result in a non-functional package if dependencies are missing from the project.'),
  new CriticalI_OptionSpec('verbose', CriticalI_OptionSpec::NONE, null, 'Displays lots of information as the upgrade progresses.'),
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
    
    $status = new CriticalI_Command_ProjectUpdate_Listener();
    if ($this->options['verbose'])
      $status->showDebug = true;
    if (! $this->options['quiet'])
      $prj->set_status_listener($status);
    
    // plan what is to be installed
    $planner = new CriticalI_Project_ChangePlanner($prj);
    
    try {
      $plan = $planner->upgrade_plan(
                    $this->args,
                    isset($this->options['version']) ? $this->options['version'] : null,
                    isset($this->options['ignore-dependencies']) ? false : true
              );
    } catch (CriticalI_ChangeManager_HasDependentError $e) {
      $s = count($this->args) == 1 ? '' : 's';
      $a = count($this->args) == 1 ? ' a' : '';
      $s2 = count($this->args) == 1 ? ' s' : '';

      fwrite(STDERR,
        wordwrap("Although$a higher version$s of the package$s exist$s2, no " .
        "suitable upgrade option could be found. If possible, try upgrading " .
        "additional packages in the project. This may solve dependency " .
        "issues. If you still encounter errors, a combination of the " .
        "remaining packages in the project and the versions available from " .
        "the repository may simply not make an upgrade possible.") . "\n");
      exit(1);
    }
    
    if (count($plan->add_list()) == 0 && count($plan->remove_list()) == 0) {
      if (!$this->options['quiet'])
        print("No package is available to update.\n");
      return;
    }

    if ( (!$this->options['quiet']) || ((!$this->options['yes']) && (!$this->options['no'])) )
      $this->display_plan($plan);
    
    $proceed = false;
    
    if ($this->options['yes'] || $this->options['no'])
      $proceed = $this->options['no'] ? false : true;
    else
      $proceed = $this->prompt_confirm("Proceed with the changes?", true);

    if ($proceed)
      $prj->perform($plan);
  }
  
  /**
   * Display the plan for confirmation
   */
  protected function display_plan($plan) {
    $table = new CriticalI_Command_TableFormatter(array('border-cell'=>'  '));
    $table->set_header(array('Remove Package', 'Version', 'Add Package', 'Version'));
    
    $psort = create_function('$a,$b', 'return strcmp($a->package()->name(), $b->package()->name());');

    $rlist = $plan->remove_list();
    usort($rlist, $psort);

    $alist = $plan->add_list();
    usort($alist, $psort);
    
    $max = max(count($rlist), count($alist));
    for ($i = 0; $i < $max; $i++) {
      $row = array();

      if (count($rlist) > $i) {
        $row[0] = $rlist[$i]->package()->name();
        $row[1] = $rlist[$i]->version_string();
      } else {
        $row[0] = '';
        $row[1] = '';
      }

      if (count($alist) > $i) {
        $row[2] = $alist[$i]->package()->name();
        $row[3] = $alist[$i]->version_string();
      } else {
        $row[2] = '';
        $row[3] = '';
      }

      $table->add_row($row);
    }
    
    print "\nThe following package changes will be made:\n\n";
    print $table->to_string();
    print "\n";
  }

}

?>
<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

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
  
List the packages installed in a project.  If no project root directory is
given, assumes the current working directory.
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
    
    $table = new CriticalI_Command_TableFormatter(array('border-cell'=>'  '));
    $table->set_header(array('Package', 'Installed Version'));
    
    foreach ($pkgs as $pkg=>$ver) {
      $table->add_row(array($pkg, $ver));
    }

    print $table->to_string();
  }

}

?>
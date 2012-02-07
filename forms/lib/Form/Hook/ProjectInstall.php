<?php
// Copyright (c) 2012, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package forms */

/**
 * Custom install tasks for the controller package
 */
class Form_Hook_ProjectInstall implements CriticalI_Project_InstallHook {
  /**
   * Invoked when the package is installed in a project
   *
   * @param CriticalI_Package_Version $pkg  The package being installed
   * @param CriticalI_Project $project  The project it is being installed into
   * @param CriticalI_Project_InstallOperation $install The installation instance
   */
  public function install($pkg, $project, $install) {
    $prefix = ($project->type() == CriticalI_Project::INSIDE_PUBLIC) ? 'private/' : '';
    
    // add the forms directory
    if (!file_exists($project->private_directory() . '/forms'))
      $install->mkdir($prefix . 'forms');
  }
  
}

?>
<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Custom install tasks for the controller package
 */
class Controller_Hook_ProjectInstall implements CriticalI_Project_InstallHook {
  /**
   * Invoked when the package is installed in a project
   *
   * @param CriticalI_Package_Version $pkg  The package being installed
   * @param CriticalI_Project $project  The project it is being installed into
   * @param CriticalI_Project_InstallOperation $install The installation instance
   */
  public function install($pkg, $project, $install) {
    $pkgDir = $GLOBALS['CRITICALI_ROOT'] . '/' . $pkg->installation_directory();
    $projectFolder = ($project->type() == CriticalI_Project::INSIDE_PUBLIC) ? '' : 'public/';
    $dispatchName = ($project->type() == CriticalI_Project::INSIDE_PUBLIC) ? 'inside' : 'outside';
    
    // add the dispatch script
    $install->copy("${pkgDir}/dispatch/dispatch_{$dispatchName}_public.php",
      "{$projectFolder}dispatch.php");
    
    // and the .htaccess file (unless it would conflict)
    if (file_exists($project->directory() . '/' . $projectFolder . '.htaccess')) {
      trigger_error("Skipping installation of .htaccess due to existing file.", E_USER_WARNING);
    } else {
      $install->copy("${pkgDir}/dispatch/htaccess", "{$projectFolder}.htaccess");
    }
  }
}

?>
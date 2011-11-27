<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package controller */

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
    
    // add the default routes.php if there is none
    if (!file_exists($project->directory() . '/' . $projectFolder . 'config/routes.php')) {
      // the config folder must exist
      if (!file_exists($project->directory() . '/' . $projectFolder . 'config'))
        $install->mkdir("{$projectFolder}config");
      
      // this is not done with $install->copy because it should never be uninstalled
      copy("${pkgDir}/dispatch/routes.php",
        $project->directory() . '/' . $projectFolder . 'config/routes.php');
    }
  }
}

?>
<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * Custom install tasks for the support package
 */
class Support_Hook_ProjectInstall implements CriticalI_Project_InstallHook {
  /**
   * Invoked when the package is installed in a project
   *
   * @param CriticalI_Package_Version $pkg  The package being installed
   * @param CriticalI_Project $project  The project it is being installed into
   * @param CriticalI_Project_InstallOperation $install The installation instance
   */
  public function install($pkg, $project, $install) {
    $pkgDir = $GLOBALS['CRITICALI_ROOT'] . '/' . $pkg->installation_directory();
    $projectFolder = ($project->type() == CriticalI_Project::INSIDE_PUBLIC) ? 'private/' : '';
    
    // add the init script
    $install->copy("${pkgDir}/init/init.php", "{$projectFolder}init.php");
  }
}

?>
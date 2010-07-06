<?php

/**
 * Custom install tasks for the support package
 */
class Support_Hook_ProjectInstall implements Vulture_Project_InstallHook {
  /**
   * Invoked when the package is installed in a project
   *
   * @param Vulture_Package_Version $pkg  The package being installed
   * @param Vulture_Project $project  The project it is being installed into
   * @param Vulture_Project_InstallOperation $install The installation instance
   */
  public function install($pkg, $project, $install) {
    $pkgDir = $GLOBALS['VULTURE_ROOT'] . '/' . $pkg->installation_directory();
    $projectFolder = ($project->type() == Vulture_Project::INSIDE_PUBLIC) ? 'private/' : '';
    
    // add the init script
    $install->copy("${pkgDir}/init/init.php", "{$projectFolder}init.php");
  }
}

?>
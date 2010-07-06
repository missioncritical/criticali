<?php

/**
 * Custom install tasks for the Smarty package
 */
class Smarty_Hook_ProjectInstall implements Vulture_Project_InstallHook {
  /**
   * Invoked when the package is installed in a project
   *
   * @param Vulture_Package_Version $pkg  The package being installed
   * @param Vulture_Project $project  The project it is being installed into
   * @param Vulture_Project_InstallOperation $install The installation instance
   */
  public function install($pkg, $project, $install) {
    $projectFolder = ($project->type() == Vulture_Project::INSIDE_PUBLIC) ? 'private/' : '';
    
    // add the plugins directory
    $install->mkdir("{$projectFolder}helper_plugins");
    // and the template compile directory
    $install->mkdir("{$projectFolder}var/templates_c");
  }
}

?>
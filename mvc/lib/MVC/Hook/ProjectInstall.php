<?php

/**
 * Custom install tasks for the mvc package
 */
class MVC_Hook_ProjectInstall implements Vulture_Project_InstallHook {
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
    
    // default directory structure
    $install->mkdir("{$projectFolder}controllers");
    $install->mkdir("{$projectFolder}config");
    $install->mkdir("{$projectFolder}lib");
    $install->mkdir("{$projectFolder}models");
    $install->mkdir("{$projectFolder}var");
    $install->mkdir("{$projectFolder}var/cache");
    $install->mkdir("{$projectFolder}var/log");
    $install->mkdir("{$projectFolder}views");
    $install->mkdir("{$projectFolder}views/layouts");
    
    // exception layout
    $install->copy("${pkgDir}/views/layouts/exception.tpl",
      "{$projectFolder}views/layouts/exception.tpl");
    $install->copy("${pkgDir}/views/layouts/_exception_message.tpl",
      "{$projectFolder}views/layouts/_exception_message.tpl");
  }
}

?>
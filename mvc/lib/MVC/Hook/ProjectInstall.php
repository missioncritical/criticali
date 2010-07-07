<?php

/**
 * Custom install tasks for the mvc package
 */
class MVC_Hook_ProjectInstall implements CriticalI_Project_InstallHook {
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
<?php
/** @package logger4php */

/**
 * Custom install tasks for the log4php package
 */
class Logger4PHP_Hook_ProjectInstall implements CriticalI_Project_InstallHook {
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
    
    // add the default log configuration file
    $install->copy("${pkgDir}/config/log4php.xml", "{$projectFolder}config/log4php.xml");
  }
}

?>
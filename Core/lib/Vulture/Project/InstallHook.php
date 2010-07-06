<?php

/**
 * The interface that must be implemented by a project install hook.
 */
interface Vulture_Project_InstallHook {
  /**
   * Invoked when the package is installed in a project
   *
   * @param Vulture_Package_Version $pkg  The package being installed
   * @param Vulture_Project $project  The project it is being installed into
   * @param Vulture_Project_InstallOperation $install The installation instance
   */
  public function install($pkg, $project, $install);
}

?>
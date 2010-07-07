<?php

/**
 * The interface that must be implemented by a project uninstall hook.
 */
interface Vulture_Project_UninstallHook {
  /**
   * Invoked when the package is uninstalled from a project
   *
   * @param Vulture_Project $project  The project it is being uninstalled from
   * @param string $pkg  The name of the package being uninstalled
   */
  public function uninstall($project, $pkg);
}

?>
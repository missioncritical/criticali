<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * The interface that must be implemented by a project uninstall hook.
 */
interface CriticalI_Project_UninstallHook {
  /**
   * Invoked when the package is uninstalled from a project
   *
   * @param CriticalI_Project $project  The project it is being uninstalled from
   * @param string $pkg  The name of the package being uninstalled
   */
  public function uninstall($project, $pkg);
}

?>
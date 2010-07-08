<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * The interface that must be implemented by a project install hook.
 */
interface CriticalI_Project_InstallHook {
  /**
   * Invoked when the package is installed in a project
   *
   * @param CriticalI_Package_Version $pkg  The package being installed
   * @param CriticalI_Project $project  The project it is being installed into
   * @param CriticalI_Project_InstallOperation $install The installation instance
   */
  public function install($pkg, $project, $install);
}

?>
<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * The interface that must be implemented by a repository install hook.
 */
interface CriticalI_InstallHook {
  /**
   * Invoked when the package is installed in the repository
   *
   * @param CriticalI_Package_Version $pkg  The package being installed
   */
  public function install($pkg);
}

?>
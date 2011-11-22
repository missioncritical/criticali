<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * The interface that must be implemented by a repository uninstall hook.
 */
interface CriticalI_UninstallHook {
  /**
   * Invoked when the package is uninstalled from the repository
   *
   * @param CriticalI_Package_Version $pkg  The package being uninstalled
   */
  public function uninstall($pkg);
}

?>
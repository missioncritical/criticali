<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Repository install hook for criticali.
 */
class CriticalI_Hook_Install extends CriticalI_InstallHook {

  /**
   * Invoked when the package is installed in the repository
   *
   * @param CriticalI_Package_Version $pkg  The package being installed
   */
  public function install($pkg) {
    // $pkg may or may not exist for us, so proceed as if bootstrapping
    
    $windoze = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');

    // posix systems only
    if (!$windoze) {
      // set the permissions on the shell script
      $script = $GLOBALS['CRITICALI_ROOT'] . '/Core/bin/criticali';
      if (!chmod($script, 0777 &~ umask()))
        trigger_error("Could not make $script executable", E_USER_WARNING);
    }
  }

}

?>
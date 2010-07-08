<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Thrown if an installation fails due to the package already being installed.
 */
class CriticalI_Project_AlreadyInstalledError extends Exception {
  public function __construct($packageName) {
    parent::__construct("Package \"$packageName\" is already installed.");
  }
}

?>
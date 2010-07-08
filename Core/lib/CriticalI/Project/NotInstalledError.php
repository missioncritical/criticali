<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Thrown if a removal fails due to the package not being installed.
 */
class CriticalI_Project_NotInstalledError extends Exception {
  public function __construct($packageName) {
    parent::__construct("Package \"$packageName\" is not installed.");
  }
}

?>
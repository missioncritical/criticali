<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Indicates an attempt to operate on an unknown/uninstalled package
 */
class CriticalI_ChangeManager_InvalidUpgradeError extends Exception {
  public function __construct($packageName, $version) {
    parent::__construct("Cannot upgrade package \"$packageName\" to version \"$version\".");
  }
}

?>
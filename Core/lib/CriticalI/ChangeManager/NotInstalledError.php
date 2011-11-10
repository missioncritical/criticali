<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Indicates an attempt to operate on an unknown/uninstalled package
 */
class CriticalI_ChangeManager_NotInstalledError extends Exception {
  public function __construct($packageName, $version = null) {
    parent::__construct(is_null($version) ?
      "Unknown package \"$packageName\"" :
      "Version \"$version\" of package \"$packageName\" is not installed.");
  }
}

?>
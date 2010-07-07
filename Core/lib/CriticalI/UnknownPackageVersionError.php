<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Indicates an attempt to operate on an unknown/uninstalled package
 * version number
 */
class CriticalI_UnknownPackageVersionError extends Exception {
  public function __construct($packageName, $version) {
    parent::__construct("Version \"$version\" of package \"$packageName\" is not present in the repository.");
  }
}

?>
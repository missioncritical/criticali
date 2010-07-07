<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Indicates a required package version could not be found
 */
class CriticalI_MissingPackageVersionError extends Exception {
  /**
   * Constructor
   *
   * @param string $packageName  The name of the missing package version
   * @param string $versionNumber The missing version number
   */
  public function __construct($packageName, $packageVersion) {
    parent::__construct("Could not find required version \"$packageVersion\" for package \"$packageName\".  Required version is not installed.");
  }
}

?>
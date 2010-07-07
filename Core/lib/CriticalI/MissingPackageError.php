<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Indicates a required package could not be found
 */
class CriticalI_MissingPackageError extends Exception {
  /**
   * Constructor
   *
   * @param string $packageName  The name of the missing package
   */
  public function __construct($packageName) {
    parent::__construct("Could not find required package \"$packageName\".  Package is not installed.");
  }
}

?>
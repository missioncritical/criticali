<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Thrown if an installation fails due to a missing dependency.
 */
class CriticalI_Project_MissingDependencyError extends Exception {
  public function __construct($packageName, $packageVersion) {
    parent::__construct("Failed to satisfy required dependency \"$packageName ($packageVersion)\"");
  }
}

?>
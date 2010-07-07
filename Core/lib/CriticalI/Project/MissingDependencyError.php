<?php

/**
 * Thrown if an installation fails due to a missing dependency.
 */
class CriticalI_Project_MissingDependencyError extends Exception {
  public function __construct($packageName, $packageVersion) {
    parent::__construct("Failed to satisfy required dependency \"$packageName ($packageVersion)\"");
  }
}

?>
<?php

/**
 * Indicates a package remove operation failed due to an existing
 * dependency
 */
class CriticalI_Project_ExistingDependencyError extends Exception {
  public function __construct($package, $depends) {
    parent::__construct("Failed to remove package \"$package\" due to an existing dependency in \"$depends\".");
  }
}

?>
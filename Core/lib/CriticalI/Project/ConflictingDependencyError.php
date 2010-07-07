<?php

/**
 * Thrown if an installation fails due to a dependency that conflicts
 * with an installed package.
 */
class CriticalI_Project_ConflictingDependencyError extends Exception {
  public function __construct($packageName, $requiredPackageVersion, $existingVersion) {
    parent::__construct("Cannot install version \"$requiredPackageVersion\" of \"$packageName\".  Version \"$existingVersion\" is already installed.");
  }
}

?>
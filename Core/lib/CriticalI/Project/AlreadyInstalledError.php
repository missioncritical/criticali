<?php

/**
 * Thrown if an installation fails due to the package already being installed.
 */
class Vulture_Project_AlreadyInstalledError extends Exception {
  public function __construct($packageName) {
    parent::__construct("Package \"$packageName\" is already installed.");
  }
}

?>
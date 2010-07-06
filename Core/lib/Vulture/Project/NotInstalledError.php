<?php

/**
 * Thrown if a removal fails due to the package not being installed.
 */
class Vulture_Project_NotInstalledError extends Exception {
  public function __construct($packageName) {
    parent::__construct("Package \"$packageName\" is not installed.");
  }
}

?>
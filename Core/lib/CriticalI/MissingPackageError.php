<?php

/**
 * Indicates a required package could not be found
 */
class Vulture_MissingPackageError extends Exception {
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
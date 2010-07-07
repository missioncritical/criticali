<?php

/**
 * Indicates an attempt to operate on an unknown/uninstalled package
 * version number
 */
class Vulture_UnknownPackageVersionError extends Exception {
  public function __construct($packageName, $version) {
    parent::__construct("Version \"$version\" of package \"$packageName\" is not present in the repository.");
  }
}

?>
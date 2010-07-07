<?php

/**
 * Indicates an attempt to operate on an unknown/uninstalled package
 */
class CriticalI_UnknownPackageError extends Exception {
  public function __construct($name) {
    parent::__construct("Unknown package \"$name\".");
  }
}

?>
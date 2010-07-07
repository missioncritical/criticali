<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Indicates an attempt to operate on an unknown/uninstalled package
 */
class CriticalI_UnknownPackageError extends Exception {
  public function __construct($name) {
    parent::__construct("Unknown package \"$name\".");
  }
}

?>
<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Indicates a proposed change cannot be made due to unfulfilled
 * dependencies.
 */
class CriticalI_ChangeManager_HasDependentError extends Exception {
  /**
   * Constructor
   */
  public function __construct() {
    parent::__construct("Cannot remove the requested package(s) because one or more other packages depend on them.");
  }
}

?>
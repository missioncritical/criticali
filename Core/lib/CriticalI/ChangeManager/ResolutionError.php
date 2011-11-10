<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Indicates a set of packages and correct dependencies could not be
 * resolved from the available set. This is due either to missing
 * packages or conflicts.
 */
class CriticalI_ChangeManager_ResolutionError extends Exception {
  /**
   * Constructor
   *
   * @param array $errors  An array of encountered error messages
   */
  public function __construct($errors) {
    parent::__construct("Could not resolve an acceptable list of packages. " .
      "This may be due to missing packages or conflicts. All encountered errors " .
      "from attempted combinations are shown:\n" . implode("\n", $errors));
  }
}

?>
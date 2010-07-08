<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Indicates an exception generated by a ConfigFile operation.
 */
class CriticalI_ConfigFileError extends Exception {
  /**
   * Constructor
   * @param string $msg  The error message
   */
  public function __construct($msg) {
    parent::__construct($msg);
  }
}

?>
<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * General usage exceptions
 */
class CriticalI_UsageError extends Exception {
  public function __construct($msg) {
    parent::__construct($msg);
  }
}

?>
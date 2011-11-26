<?php
// Copyright (c) 2009-2011, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package criticali */

/**
 * Receives informational messages from a package-related operation on
 * the repository
 */
interface CriticalI_Package_StatusListener {
  /**
   * Normal informational message
   *
   * @param CriticalI_Package $package  The package the operation is occurring on (may be null)
   * @param string            $message  The message
   */
  public function info($package, $message);

  /**
   * Debug-level message
   *
   * @param CriticalI_Package $package  The package the operation is occurring on (may be null)
   * @param string            $message  The message
   */
  public function debug($package, $message);
}

?>
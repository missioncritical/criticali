<?php
// Copyright (c) 2009-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * Receives informational messages from a project operation
 */
interface CriticalI_Project_StatusListener {
/**
 * Normal informational message
 *
 * @param CriticalI_Project $project  The project the operation is occurring on
 * @param CriticalI_Package $package  The package the operation is occurring on (may be null)
 * @param string          $message  The message
 */
  public function info($project, $package, $message);

/**
 * Debug-level message
 *
 * @param CriticalI_Project $project  The project the operation is occurring on
 * @param CriticalI_Package $package  The package the operation is occurring on (may be null)
 * @param string          $message  The message
 */
  public function debug($project, $package, $message);
}

?>
<?php

/**
 * Receives informational messages from a project operation
 */
interface Vulture_Project_StatusListener {
/**
 * Normal informational message
 *
 * @param Vulture_Project $project  The project the operation is occurring on
 * @param Vulture_Package $package  The package the operation is occurring on (may be null)
 * @param string          $message  The message
 */
  public function info($project, $package, $message);

/**
 * Debug-level message
 *
 * @param Vulture_Project $project  The project the operation is occurring on
 * @param Vulture_Package $package  The package the operation is occurring on (may be null)
 * @param string          $message  The message
 */
  public function debug($project, $package, $message);
}

?>
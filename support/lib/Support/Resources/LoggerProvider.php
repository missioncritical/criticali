<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * Interface for providing loggers for Support_Resources
 */
interface Support_Resources_LoggerProvider {
  /**
   * Return a logger instance for the given name.
   *
   * A logger instance must provide the following methods: debug, info,
   * warn, error, fatal.  Each must accept a message to log.  The method
   * name indicates the logging level.
   *
   * @param string $name  The logger name to return
   * @return object
   */
  public function get($name);
}

?>
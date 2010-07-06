<?php
/**
 * This file is Copyright 2009 Jeffrey Hunter.  It is provided under the
 * same license as log4php, Apache License 1.1.  See LICENSE file for
 * details.
 */

if (Cfg::exists('logging/config') && (!defined('LOG4PHP_CONFIG_FILENAME')))
  define('LOG4PHP_CONFIG_FILENAME', Cfg::get('logging/config'));

require_once('log4php/LoggerManager.php');

/**
 * A logger provider for Support_Resources that provides log4php.
 */
class Logger4PHP_Provider implements Support_Resources_LoggerProvider {
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
  public function get($name) {
    return LoggerManager::getLogger($name);
  }
}

?>
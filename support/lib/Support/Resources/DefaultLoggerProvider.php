<?php
// Copyright (c) 2008-2010, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.
/** @package support */

/**
 * The default logger provider returns uses the
 * Support_Resources_DefaultLogger
 */
class Support_Resources_DefaultLoggerProvider implements Support_Resources_LoggerProvider {
  protected $loggers;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->loggers = array();
  }

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
    if (!isset($this->loggers[strtolower($name)]))
      $this->loggers[strtolower($name)] = new Support_Resources_DefaultLogger($name);
    return $this->loggers[strtolower($name)];
  }
}

/**
 * A simplistic logger that passes message to error_log and does
 * rudimentary filtering based on the current error reporting setting.
 */
class Support_Resources_DefaultLogger {
  protected $level;
  protected $name;
  
  /**
   * Constructor
   */
  public function __construct($name) {
    $this->name = $name;
    $this->level = error_reporting();
  }
  
  /**
   * Log a debug message.
   *
   * This message is logged if error reporting has any value greater than
   * E_STRICT (e.g. if E_ALL is set).
   *
   * @param string $msg The message to log
   */
  public function debug($msg) {
    if ($this->level > E_STRICT) $this->log_message('DEBUG', $msg);
  }

  /**
   * Log an info message.
   *
   * This message is logged if error reporting includes E_NOTICE
   *
   * @param string $msg The message to log
   */
  public function info($msg) {
    if ($this->level & E_NOTICE) $this->log_message('INFO', $msg);
  }

  /**
   * Log a warning message.
   *
   * This message is logged if error reporting includes E_WARNING
   *
   * @param string $msg The message to log
   */
  public function warn($msg) {
    if ($this->level & E_WARNING) $this->log_message('WARN', $msg);
  }

  /**
   * Log an error message.
   *
   * This message is logged if error reporting includes E_ERROR
   *
   * @param string $msg The message to log
   */
  public function error($msg) {
    if ($this->level & E_ERROR) $this->log_message('ERROR', $msg);
  }

  /**
   * Log a fatal error message.
   *
   * This message is always passed to logging
   *
   * @param string $msg The message to log
   */
  public function fatal($msg) {
    $this->log_message('FATAL', $msg);
  }
  
  /**
   * Send a message to the error log
   *
   * @param string $level Descriptive tag for the logging level
   * @param string $msg   The message to log
   */
  protected function log_message($level, $msg) {
    error_log("[".$this->timestamp()."] [$level] " .
      (empty($this->name) ? '' : ('['.$this->name.'] ')) .
      $msg);
  }
  
  /**
   * Return a formatted timestamp
   */
  protected function timestamp() {
    $tm = gettimeofday();
    $ms = floor($tm['usec'] / 1000);
    return strftime("%Y-%m-%d %H:%M:%S") . sprintf('.%03d', $ms);
  }
}

?>
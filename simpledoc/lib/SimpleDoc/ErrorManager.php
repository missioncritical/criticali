<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * ErrorManager tracks errors and warnings emitted during processing.
 *
 * @package simpledoc
 */
class SimpleDoc_ErrorManager {
  
  const INFO = 4;
  const WARN = 8;
  const ERROR = 16;
  
  static protected $events;
  
  /**
   * Constructor
   */
  private function __construct() {
    throw new Exception("SimpleDoc_ErrorManager may not be instantiated");
  }
  
  /**
   * Track an information event
   */
  public static function info($message) {
    self::track_event(self::INFO, $message);
  }
  
  /**
   * Track a warning
   */
  public static function warn($message) {
    self::track_event(self::WARN, $message);
  }

  /**
   * Track an error
   */
  public static function error($message) {
    self::track_event(self::ERROR, $message);
  }
  
  /**
   * Reset all events
   */
  public static function reset() {
    self::$events = array();
  }
  
  /**
   * Return the set of all events
   * @return array
   */
  public static function events() {
    return self::$events;
  }

  protected static function track_event($level, $message) {
    if (!self::$events)
      self::$events = array();
    
    self::$events[] = new SimpleDoc_ErrorManagerEvent($level, microtime(true), $message);
  }
}

/**
 * An event tracked by the ErrorManager
 */
class SimpleDoc_ErrorManagerEvent {
  public $level;
  public $ts;
  public $message;
  
  public function __construct($level, $ts, $message) {
    $this->level = $level;
    $this->ts = $ts;
    $this->message = $message;
  }
}

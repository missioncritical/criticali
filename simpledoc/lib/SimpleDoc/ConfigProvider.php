<?php
// Copyright (c) 2013, Jeffrey Hunter and Mission Critical Labs, Inc.
// See the LICENSE file distributed with this work for restrictions.

/**
 * SimpleDoc_ConfigProvider manages a simple config setup that allows the
 * doc command to run without a config.php file present in the repository.
 *
 * @package simpledoc
 */
class SimpleDoc_ConfigProvider {
  /** Static access only */
  private function __construct() {
    throw new Exception("SimpleDoc_ConfigProvider may not be instantiated");
  }
  
  /**
   * Perform registration tasks
   */
  public static function register() {
    Support_Resources::register_config_data(new SimpleDoc_ConfigProviderImpl(),
                                            'doc',
                                             true);
  }
}

/**
 * Implements the Support_Resources_ConfigProvider interface
 */
class SimpleDoc_ConfigProviderImpl implements Support_Resources_ConfigProvider {
  protected $data;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->data = array();
  }

  /**
   * Return the application configuration data.
   *
   * @return array
   */
  public function get() {
    return $this->data;
  }
}

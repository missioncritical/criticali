<?php

/**
 * The default config data provider includes a file named config.php
 * (from anywhere in the include path) which is expected to populate a
 * global array named APP_CONFIG.
 */
class Support_Resources_DefaultConfigProvider implements Support_Resources_ConfigProvider {
  protected $included;
  
  /**
   * Constructor
   */
  public function __construct() {
    $this->included = false;
  }

  /**
   * Return the application configuration data.
   *
   * @return array
   */
  public function get() {
    global $APP_CONFIG;
    if (!$this->included) {
      include_once('config.php');
      $this->included = true;
    }
    return $APP_CONFIG;
  }
}

?>
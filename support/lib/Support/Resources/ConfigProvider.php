<?php

/**
 * Interface for providing config data for Support_Resources
 */
interface Support_Resources_ConfigProvider {
  /**
   * Return the array of application configuration data.
   *
   * @return array
   */
  public function get();
}

?>